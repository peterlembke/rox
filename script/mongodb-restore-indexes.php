<?php
// Recreates the MongoDB indexes that the Laravel index migrations define, for one database, after a local import.
//
// A local import restores the MongoDB database from a dump, with the indexes of the server the dump came from, and
// the imported migrations table says every migration has run, so the index migrations never run again (AT-13147).
// This script runs the up() of every migration whose file name has "index" in it and whose RESOURCE_MODEL_NAME model
// lives in the imported MongoDB database. Those up() methods only create the indexes that are missing.
//
// Runs in the appserver container: php mongodb-restore-indexes.php <laravel project folder> <database name>
// Exit code: 0 when every migration ran, 1 when one failed or the arguments are wrong.
declare(strict_types=1);

/**
 * Prints one line
 */
function printLine(
    string $text
): void {
    echo $text . PHP_EOL;
}

/**
 * Boots the Laravel application in the project folder, as artisan does
 */
function bootLaravel(
    string $projectFolder
): void {
    require $projectFolder . '/vendor/autoload.php';
    $app = require $projectFolder . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
}

/**
 * The model class in the migration's RESOURCE_MODEL_NAME, or empty when it has none
 */
function findResourceModelName(
    object $migration
): string {
    $resourceModelName = '';

    // Works for named and anonymous migration classes
    $reflectionClass = new ReflectionClass($migration);
    $hasConstant = $reflectionClass->hasConstant('RESOURCE_MODEL_NAME');
    if ($hasConstant === true) {
        $resourceModelName = (string) $reflectionClass->getConstant('RESOURCE_MODEL_NAME');
    }

    return $resourceModelName;
}

/**
 * Prints the index names of each collection, so the result can be checked
 */
function printIndexes(
    array $collectionConnectionLookup
): void {
    foreach ($collectionConnectionLookup as $collectionName => $connection) {
        $indexNameArray = [];
        foreach ($connection->getCollection($collectionName)->listIndexes() as $index) {
            $indexNameArray[] = $index->getName();
        }
        printLine('- ' . $collectionName . ': ' . count($indexNameArray) . ' indexes: ' . implode(', ', $indexNameArray));
    }
}

$projectFolder = (string) ($argv[1] ?? '');
$databaseName = (string) ($argv[2] ?? '');

$isProjectFolder = is_file($projectFolder . '/artisan') === true;
if ($isProjectFolder === false) {
    printLine('Not a Laravel project folder: "' . $projectFolder . '". Usage: php mongodb-restore-indexes.php <project folder> <database name>');
    exit(1);
}
if ($databaseName === '') {
    printLine('Missing the database name. Usage: php mongodb-restore-indexes.php <project folder> <database name>');
    exit(1);
}

bootLaravel(
    projectFolder: $projectFolder
);

$migrator = app('migrator');
$pathArray = array_merge(
    [database_path('migrations')],
    $migrator->paths()
);
$migrationFileLookup = $migrator->getMigrationFiles($pathArray);

// resolvePath() is how the migrator itself loads a migration, named or anonymous
$resolvePathMethod = new ReflectionMethod($migrator, 'resolvePath');

$ranCount = 0;
$failedArray = [];
$collectionConnectionLookup = [];
foreach ($migrationFileLookup as $migrationName => $migrationPath) {
    $isIndexMigration = stripos($migrationName, 'index') !== false;
    if ($isIndexMigration === false) {
        continue;
    }

    $migration = $resolvePathMethod->invoke($migrator, $migrationPath);
    $resourceModelName = findResourceModelName(
        migration: $migration
    );
    if ($resourceModelName === '') {
        continue;
    }

    $model = resolve($resourceModelName);
    $connection = $model->getConnection();
    $isImportedMongoDatabase = $connection->getDriverName() === 'mongodb'
        && $connection->getDatabaseName() === $databaseName;
    if ($isImportedMongoDatabase === false) {
        continue;
    }

    try {
        $migration->up();
        $ranCount++;
        $collectionConnectionLookup[$model->getTable()] = $connection;
        printLine('ran ' . $migrationName);
    } catch (Throwable $exception) {
        $failedArray[] = $migrationName . ': ' . $exception->getMessage();
    }
}

printLine('Index migrations run for ' . $databaseName . ': ' . $ranCount . ', failed: ' . count($failedArray));
printIndexes(
    collectionConnectionLookup: $collectionConnectionLookup
);

$hasFailed = count($failedArray) > 0;
if ($hasFailed === true) {
    foreach ($failedArray as $failedText) {
        printLine('FAILED ' . $failedText);
    }
    exit(1);
}
exit(0);
