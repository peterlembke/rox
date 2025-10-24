#!/usr/bin/env php
<?php
/**
 * Main PHP script for managing Docker containers and various services
 * This is a PHP implementation of the main.sh bash script
 * PHP version 8.1+
 */

class Main
{
    // Configuration variables
    protected string $phpVersion = "8.3";
    protected string $dockerBin;
    protected string $composeBin;
    protected string $composeDir;
    protected string $projectName = "rox";
    protected string $composeProjectName = "rox";
    protected string $hostUid = "1000";
    protected string $hostGid = "1000";
    protected string $roxBaseDir = "/var/www/rox";
    protected string $roxDbUser = "root";
    protected string $roxDbPass = "";
    protected string $roxDbName = "rox";
    protected string $roxCacheBackendRedisPort = "6379";
    protected string $roxCacheBackendRedisDb = "0";
    protected string $roxPageCacheRedisPort = "6379";
    protected string $roxPageCacheRedisDb = "1";
    protected string $roxSessionSaveRedisPort = "6379";
    protected string $roxSessionSaveRedisDb = "2";
    protected string $dbIp = "127.0.0.1";
    protected string $dbUrl = "localhost";
    protected string $hostUrl = "localhost";
    protected string $osType; // linux or mac. No support for Windows

    protected bool $isLinux = false;
    protected bool $isMacOS = false;
    protected bool $isOtherOS = true;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dockerBin = trim(shell_exec('which docker'));
        $this->composeBin = $this->dockerBin;
        $this->composeDir = dirname(__FILE__);

        $osType = strtolower(PHP_OS);
        $this->osType = 'linux';
        if (str_contains($osType, 'darwin') === true) {
            $this->osType = 'mac';
        }
        if ($this->osType === 'linux') {
            $this->isLinux = true;
        }
        if ($this->osType === 'mac') {
            $this->isMacOS = true;
        }
        $this->isOtherOS = $this->isLinux === false && $this->isMacOS === false;

        $this->loadConfigFiles();

        $this->setEnvironmentVariables();
    }

    /**
     * Load configuration files
     */
    protected function loadConfigFiles(): array
    {
        $defaultConfPath = "{$this->composeDir}/default.conf";
        if (file_exists($defaultConfPath)) {
            $this->parseConfigFile($defaultConfPath);
        }

        $distConfPath = "{$this->composeDir}/project.conf.dist.". $this->osType;
        $confPath = "{$this->composeDir}/project.conf.". $this->osType;

        if (file_exists($distConfPath) === true) {
            $this->parseConfigFile($distConfPath);
        }

        if (file_exists($confPath) === true) {
            $this->parseConfigFile($confPath);
        }

        return [
            'answer' => true,
            'message' => 'Configuration files loaded successfully'
        ];
    }

    /**
     * Parse a configuration file and set variables
     */
    protected function parseConfigFile(
        string $filePath
    ): array
    {
        $content = file_get_contents($filePath);
        $lineArray = explode("\n", $content);

        foreach ($lineArray as $line) {

            $line = trim($line);

            $isEmptyLine = $line === '';
            if ($isEmptyLine === true) {
                continue;
            }

            $isComment = str_starts_with($line, '#') === true || str_starts_with($line, '//') === true;
            if ($isComment === true) {
                continue;
            }

            $line = str_replace("export ", "", $line);

            // Match variable assignments like VAR_NAME=value
            $patternToFindVariableAssignments = '/^\s*([A-Za-z0-9_]+)=(.*)$/';

            preg_match(
                pattern: $patternToFindVariableAssignments,
                subject: $line,
                matches: $matchArray
            );

            $isMatch = empty($matchArray) === false && count($matchArray) === 3;
            if ($isMatch === false) {
                continue;
            }

            $varName = $matchArray[1];
            $varValue = trim($matchArray[2], '"\'');

            $isSuccess = putenv("{$varName}={$varValue}");

            // Convert bash variable names to camelCase for PHP
            $camelCaseVar = $this->bashToCamelCase($varName);

            // Set the property if it exists in the class
            if (property_exists($this, $camelCaseVar)) {
                $this->$camelCaseVar = $varValue;
            } elseif (property_exists($this, strtolower($varName))) {
                $lowerVar = strtolower($varName);
                $this->$lowerVar = $varValue;
            }

            // Special case for PROJECT_NAME
            if ($varName === 'PROJECT_NAME') {
                $this->projectName = $varValue;
                $this->composeProjectName = $varValue;
            }
        }

        return [
            'answer' => true,
            'message' => "Parsed config file: $filePath"
        ];
    }

    /**
     * Convert bash variable name to camelCase
     */
    protected function bashToCamelCase(string $bashVar): string
    {
        $parts = explode('_', strtolower($bashVar));
        $camelCase = array_shift($parts);

        foreach ($parts as $part) {
            $camelCase .= ucfirst($part);
        }

        return $camelCase;
    }

    /**
     * Set environment variables
     */
    protected function setEnvironmentVariables(): array
    {
        putenv("COMPOSE_PROJECT_NAME={$this->composeProjectName}");
        putenv("HOST_UID={$this->hostUid}");
        putenv("HOST_GID={$this->hostGid}");

        // Set HOST_UID and HOST_GID on Linux
        if ($this->isLinux === true) {
            $uid = trim(shell_exec('id -u'));
            $gid = trim(shell_exec('id -g'));

            if (empty($uid) === false) {
                $this->hostUid = $uid;
                putenv("HOST_UID=$uid");
            }

            if (empty($gid) === false) {
                $this->hostGid = $gid;
                putenv("HOST_GID=$gid");
            }
        }

        return [
            'answer' => true,
            'message' => 'Environment variables set successfully'
        ];
    }

    /**
     * Print colored text
     */
    protected function printc(
        string $text,
        string $color
    ): array {
        $finalText = trim($text);

        $isTty = posix_isatty(STDOUT);
        if ($isTty === true) {
            $finalText = "\033[" . $color . "m" . $text . "\033[0m";
        }

        echo $finalText;

        return [
            'answer' => true,
            'message' => 'Text printed successfully'
        ];
    }

    /**
     * Print notice message
     */
    protected function notice(
        string $text
    ): array {

        $response = $this->printc(text: $text, color: "1;34");

        return [
            'answer' => $response['answer'],
            'message' => $response['message']
        ];
    }

    /**
     * Print success message
     */
    protected function success(
        string $text
    ): array {

        $response = $this->printc(text: $text, color: "0;32");

        return [
            'answer' => $response['answer'],
            'message' => $response['message']
        ];
    }

    /**
     * Print error message
     */
    protected function error(
        string $text
    ): array {

        $result = $this->printc(text: $text, color: "1;31");

        return [
            'answer' => $result['answer'],
            'message' => $result['message']
        ];
    }

    /**
     * Print warning message
     */
    protected function warning(
        string $text
    ): array {

        $response = $this->printc(text: $text, color: "1;33");

        return [
            'answer' => $response['answer'],
            'message' => $response['message']
        ];
    }

    /**
     * Wrapper for echoing a new line
     * Do not want a lot of echo in the code
     * 
     * @return void
     */
    protected function newLine(): void
    {
        echo PHP_EOL;
    }

    /**
     * Run docker-compose command
     */
    protected function composeCmd(
        array $args = []
    ): array {

        $commandStringArray = [
            $this->composeBin,
            'compose',
            '--file', "{$this->composeDir}/docker-compose.yml",
            '--project-name', $this->composeProjectName,
            '--file', "{$this->composeDir}/docker-compose.{$this->osType}.yml"
        ];

        $fullCommand = array_merge($commandStringArray, $args);
        $commandString = implode(' ', array_map('escapeshellarg', $fullCommand));

        $output = [];
        $returnVar = 0;
        exec($commandString, $output, $returnVar);

        $isSuccess = $returnVar === 0;

        return [
            'answer' => $isSuccess,
            'message' => implode("\n", $output),
            'command' => $commandString
        ];
    }

    /**
     * Execute command in container
     */
    protected function containerExec(
        string $containerName,
        string $userName,
        array $commandStringArray
    ): array
    {
        $isTty = posix_isatty(STDIN);
        if ($isTty === true) {

            $args = [
                'exec',
                '--user',
                $userName,
                $containerName,
                ...$commandStringArray
            ];

            $response = $this->composeCmd(args: $args);

            return $response;
        }

        $args = [
            'ps', 
            '-q', 
            $containerName
        ];
        
        $response = $this->composeCmd(args: $args);
        if ($response['answer'] === false) {
            return [
                'answer' => false,
                'message' => "Failed to get container ID for {$containerName}, message: " . $response['message'],
            ];
        }

        $containerId = trim($response['message']);
        $dockerCommand = [
            $this->dockerBin,
            'exec',
            '-i',
            '--user', $userName,
            $containerId,
            ...$commandStringArray
        ];

        $commandString = implode(' ', array_map('escapeshellarg', $dockerCommand));

        $output = [];
        $returnVar = 0;
        exec($commandString, $output, $returnVar);

        $isSuccess = $returnVar === 0;

        return [
            'answer' => $isSuccess,
            'message' => implode("\n", $output),
            'command' => $commandString
        ];
    }

    /**
     * Run Laravel CLI command
     */
    protected function laravelCmd(array $args = []): array
    {
        $commandStringArray = [
            'php',
            '-f',
            "{$this->roxBaseDir}/artisan",
            '--',
            ...$args
        ];

        $response = $this->containerExec(
            containerName: 'appserver',
            userName: 'dockerhost',
            commandStringArray: $commandStringArray
        );

        return $response;
    }

    /**
     * Run Composer command
     */
    protected function composerCmd(
        array $args = []
    ): array {
        
        $commandStringArray = [
            'composer',
            '--working-dir=' . $this->roxBaseDir,
            ...$args
        ];

        $response = $this->containerExec(
            containerName: 'appserver',
            userName: 'dockerhost',
            commandStringArray: $commandStringArray
        );

        return $response;
    }

    /**
     * Run PHPDOC command to render documentation for a folder
     */
    protected function phpdocCmd(
        string $folder = '.',
        string $destination = 'phpdoc'
    ): array
    {
        $pwd = trim(shell_exec('pwd'));
        $command = "docker run --rm -v {$pwd}:/data phpdoc/phpdoc -d \"{$folder}\" -t \"{$destination}\"";

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        $isSuccess = $returnVar === 0;

        return [
            'answer' => $isSuccess,
            'message' => implode("\n", $output),
            'command' => $command
        ];
    }

    /**
     * Run MySQL command
     */
    protected function mysqlCmd(
        array $args = []
    ): array
    {
        $cmd = 'mysql';
        $firstArg = $args[0] ?? '';

        if ($firstArg === 'admin') {
            $cmd = 'mysqladmin';
            array_shift($args);
        }

        if ($firstArg === 'dump') {
            $cmd = 'mysqldump';
            array_shift($args);
        }

        $commandStringArray = [
            $cmd,
            '--user=' . $this->roxDbUser,
            '--password=' . $this->roxDbPass
        ];

        if ($cmd !== 'mysqladmin') {
            $commandStringArray[] = $this->roxDbName;
        }

        $commandStringArray = array_merge($commandStringArray, $args);

        $response = $this->containerExec(
            containerName: 'dbserver',
            userName: 'root',
            commandStringArray: $commandStringArray
        );

        return $response;
    }

    /**
     * Run MySQL admin command
     */
    protected function mysqlAdmin(array $args = []): array
    {
        $commandStringArray = [
            'mysqladmin',
            '--user=' . $this->roxDbUser,
            '--password=' . $this->roxDbPass,
            ...$args
        ];

        $response = $this->containerExec(
            containerName: 'dbserver',
            userName: 'root',
            commandStringArray: $commandStringArray
        );

        return $response;
    }

    /**
     * Dump MySQL database
     */
    protected function mysqlDump(
        string $db = ''
    ): array 
    {
        if (empty($db) === true) {
            $db = $this->roxDbName;
        }

        $date = date('Ymd-His');
        $filename = "{$db}-{$date}.sql.bz2";

        $commandStringArray = [
            'mysqldump',
            '--user=' . $this->roxDbUser,
            '--password=' . $this->roxDbPass,
            $db
        ];

        $response = $this->containerExec(
            containerName: 'dbserver',
            userName: 'root',
            commandStringArray: $commandStringArray
        );

        if ($response['answer'] === false) {
            return [
                'answer' => false,
                'message' => "Failed to dump database {$db}: " . $response['message']
            ];
        }

        file_put_contents("php://temp", $response['message']);
        $compressCommand = "bzip2 > {$filename}";

        $output = [];
        $returnVar = 0;
        exec($compressCommand, $output, $returnVar);

        $isSuccess = $returnVar === 0;

        return [
            'answer' => $isSuccess,
            'message' => "Database dumped to {$filename}",
            'filename' => $filename
        ];
    }

    /**
     * Set the .env file and clear the env cache
     */
    protected function setEnv(
        string $envName = 'local'
    ): array
    {
        $baseDir = dirname($this->composeDir);

        $commandStringArray = [
            'sh',
            '-c',
            "cp {$this->roxBaseDir}/{$envName} {$this->roxBaseDir}/.env"
        ];

        $response = $this->containerExec(
            containerName: 'appserver',
            userName: 'root',
            commandStringArray: $commandStringArray
        );

        if ($response['answer'] === false) {
            return [
                'answer' => false,
                'message' => "Failed to copy .env file: " . $response['message']
            ];
        }

        $this->notice("cp {$this->roxBaseDir}/{$envName} {$this->roxBaseDir}/.env");
        $this->newLine();

        $response = $this->laravelCmd(['config:clear']);
        if ($response['answer'] === false) {
            return [
                'answer' => false,
                'message' => "Failed to clear config cache: " . $response['message']
            ];
        }

        $this->success('[DONE] ');
        $this->notice("Using {$envName} as .env file");
        $this->newLine();

        return [
            'answer' => true,
            'message' => "Environment set to {$envName}"
        ];
    }

    /**
     * Create symlink
     */
    protected function setSymlink(): array
    {
        $baseDir = dirname($this->composeDir);

        $command = ['sh', '-c', "ln -s {$this->roxBaseDir}/storage {$this->roxBaseDir}/public/storage"];
        $response = $this->containerExec('appserver', 'root', $command);

        if ($response['answer'] === false) {
            return [
                'answer' => false,
                'message' => "Failed to create symlink: " . $response['message']
            ];
        }

        $this->success('[DONE] ');
        $this->notice("Created symlink from public/storage to storage");
        $this->newLine();

        return [
            'answer' => true,
            'message' => "Symlink created successfully"
        ];
    }

    /**
     * Run Redis command
     */
    protected function redisCmd(array $args = []): array
    {
        $port = $this->roxCacheBackendRedisPort;
        $db = $this->roxCacheBackendRedisDb;
        $firstArg = $args[0] ?? '';

        if ($firstArg === 'fpc') {
            $port = $this->roxPageCacheRedisPort;
            $db = $this->roxPageCacheRedisDb;
            array_shift($args);
        }

        if ($firstArg === 'session') {
            $port = $this->roxSessionSaveRedisPort;
            $db = $this->roxSessionSaveRedisDb;
            array_shift($args);
        }

        $commandStringArray = [
            'redis-cli',
            '-p',
            $port,
            '-n',
            $db,
            ...$args
        ];

        $response = $this->containerExec(
            containerName: 'cacheserver',
            userName: 'root',
            commandStringArray: $commandStringArray
        );

        return  $response;
    }

    /**
     * Run PHP-FPM command
     */
    protected function fpmCmd(string $action): array
    {
        $commandStringArray = [
            'service',
            "php{$this->phpVersion}-fpm",
            $action
        ];

        $response = $this->containerExec(
            containerName: 'appserver',
            userName: 'root',
            commandStringArray: $commandStringArray
        );

        return  $response;
    }

    /**
     * Run PhpStan command
     */
    protected function phpstanCmd(
        array $args = []
    ): array 
    {
        $cmd = 'vendor/bin/phpstan';

        if (empty($args) === true) {
            $commandStringArray = [
                'php',
                '-f',
                "{$this->roxBaseDir}/{$cmd}",
                'analyse',
                '-c',
                "{$this->roxBaseDir}/phpstan.neon.dist"
            ];

            goto leave;
        }

        if ($args[0] === '--level' && isset($args[1]) === true) {
            $commandStringArray = [
                'php',
                '-f',
                "{$this->roxBaseDir}/{$cmd}",
                'analyse',
                '-c',
                "{$this->roxBaseDir}/phpstan.neon.dist",
                '--level',
                $args[1]
            ];

            goto leave;
        }

        $commandStringArray = [
            'php',
            '-f',
            "{$this->roxBaseDir}/{$cmd}",
            'analyse',
            ...$args
        ];

        leave:

        $response = $this->containerExec(
            containerName: 'appserver',
            userName: 'root',
            commandStringArray: $commandStringArray
        );

        return $response;
    }

    /**
     * Run BASH shell in container
     */
    protected function execShell(
        string $containerName = 'appserver',
        string $userName = ''
    ): array 
    {
        if (empty($containerName) === true) {
            $containerName = 'appserver';
        }

        if (str_contains($containerName, 'server') === false) {
            $containerName .= 'server';
        }

        if (empty($userName) === true) {
            $userName = 'root';
            if ($containerName === 'appserver' || $containerName === 'webserver') {
                $userName = 'dockerhost';
            }
        }

        $response = $this->containerExec(
            containerName: $containerName,
            userName: $userName,
            commandStringArray: ['bash']
        );

        return $response;
    }

    /**
     * Get the docker box IP number
     */
    protected function getIp(
        string $container = 'app'
    ): array
    {
        if (empty($container) === true) {
            $container = 'app';
        }

        $fullContainerName = "{$this->projectName}-{$container}";

        $command = "docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' {$fullContainerName}";

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        $isSuccess = ($returnVar === 0);
        $message = $isSuccess ? 'IP address retrieved successfully' : 'Failed to retrieve IP address';
        $ip = trim(implode("\n", $output));

        return [
            'answer' => $isSuccess,
            'message' => $message,
            'ip' => $ip
        ];
    }

    /**
     * Update hosts file
     */
    protected function updateHostsFile(): array
    {
        // Database IP and URL
        $ipHost = "{$this->dbIp} {$this->dbUrl}";

        $commandStringArray = ['sudo', 'sh', '-c', "grep -qxF \"{$ipHost}\" /etc/hosts || echo \"{$ipHost}\" >> /etc/hosts"];

        $response = $this->containerExec('appserver', 'root', $commandStringArray);
        if ($response['answer'] === false) {
            return [
                'answer' => false,
                'message' => "Failed to update hosts file: " . $response['message']
            ];
        }

        $this->success('[DONE] ');
        $this->notice($ipHost);
        $this->newLine();

        // Cache server
        $response = $this->getIp('cache');
        if ($response['answer'] === false) {
            return [
                'answer' => false,
                'message' => "Failed to get cache server IP: " . $response['message']
            ];
        }

        $ip = $response['ip'];
        $ipHost = "{$ip} cache";

        $commandStringArray = ['sudo', 'sh', '-c', "grep -qxF \"{$ipHost}\" /etc/hosts || echo \"{$ipHost}\" >> /etc/hosts"];
        $response = $this->containerExec(containerName: 'appserver', userName: 'root', commandStringArray: $commandStringArray);
        if ($response['answer'] === false) {
            return [
                'answer' => false,
                'message' => "Failed to update hosts file for cache server: " . $response['message']
            ];
        }

        $this->success('[DONE] ');
        $this->notice($ipHost);
        $this->newLine();

        $response = $this->getIp('web');
        if ($response['answer'] === false) {
            return [
                'answer' => false,
                'message' => "Failed to get web server IP: " . $response['message']
            ];
        }

        $ip = $response['ip'];
        $ipHost = "{$ip} {$this->hostUrl}";

        $commandStringArray = ['sudo', 'sh', '-c', "grep -qxF \"{$ipHost}\" /etc/hosts || echo \"{$ipHost}\" >> /etc/hosts"];
        $response = $this->containerExec('appserver', 'root', $commandStringArray);

        if ($response['answer'] === false) {
            return [
                'answer' => false,
                'message' => "Failed to update hosts file for web server: " . $response['message']
            ];
        }

        $this->success('[DONE] ');
        $this->notice($ipHost);
        $this->newLine();

        return [
            'answer' => true,
            'message' => 'Hosts file updated successfully'
        ];
    }

    /**
     * Clean all known cache layers in an independent setup
     */
    protected function purgeAllIndependent(): array
    {
        $this->notice('Flushing Redis Cache');
        echo ' .. ';

        $redisResult = $this->redisCmd(['FLUSHDB']);

        if ($redisResult['answer'] && trim($redisResult['message']) === 'OK') {
            $this->success('[DONE]');
            $this->newLine();
        } else {
            $this->error('[ERROR]');
            $this->newLine();
            echo $redisResult['message'];
            return $redisResult;
        }

        $this->notice('Clearing PHP OPCache');
        echo ' .. ';

        $fpmResult = $this->fpmCmd('reload');

        if ($fpmResult['answer']) {
            $this->success('[DONE]');
            $this->newLine();
        } else {
            $this->error('[ERROR]');
            $this->newLine();
            echo $fpmResult['message'];
            return $fpmResult;
        }

        return [
            'answer' => true,
            'message' => 'All independent caches purged successfully'
        ];
    }

    /**
     * Clean all known cache layers for laravel
     */
    protected function purgeAllLaravel(): array
    {
        // Flush Redis Cache
        $this->notice('Flushing Redis Cache');
        echo ' .. ';

        $redisResult = $this->redisCmd(['FLUSHDB']);

        if ($redisResult['answer'] && trim($redisResult['message']) === 'OK') {
            $this->success('[DONE]');
            $this->newLine();
        } else {
            $this->error('[ERROR]');
            $this->newLine();
            echo $redisResult['message'];
            return $redisResult;
        }

        // Clear Laravel Application Cache
        $this->notice('Clearing Laravel Application Cache');
        echo ' .. ';

        $cacheResult = $this->laravelCmd(['cache:clear']);

        if ($cacheResult['answer']) {
            $this->success('[DONE]');
            $this->newLine();
        } else {
            $this->error('[ERROR]');
            $this->newLine();
            echo $cacheResult['message'];
            return $cacheResult;
        }

        // Clear Laravel Route Cache
        $this->notice('Clearing Laravel Route Cache');
        echo ' .. ';

        $routeResult = $this->laravelCmd(['route:cache']);

        if ($routeResult['answer']) {
            $this->success('[DONE]');
            $this->newLine();
        } else {
            $this->error('[ERROR]');
            $this->newLine();
            echo $routeResult['message'];
            return $routeResult;
        }

        // Clear Laravel Config Cache
        $this->notice('Clearing Laravel Config Cache');
        echo ' .. ';

        $configResult = $this->laravelCmd(['config:clear']);

        if ($configResult['answer']) {
            $this->success('[DONE]');
            $this->newLine();
        } else {
            $this->error('[ERROR]');
            $this->newLine();
            echo $configResult['message'];
            return $configResult;
        }

        // Clear Laravel compiled view files
        $this->notice('Clearing Laravel compiled view files');
        echo ' .. ';

        $viewResult = $this->laravelCmd(['view:clear']);

        if ($viewResult['answer']) {
            $this->success('[DONE]');
            $this->newLine();
        } else {
            $this->error('[ERROR]');
            $this->newLine();
            echo $viewResult['message'];
            return $viewResult;
        }

        // Clear all cached events and listeners
        $this->notice('Clearing all cached events and listeners');
        echo ' .. ';

        $eventResult = $this->laravelCmd(['event:clear']);

        if ($eventResult['answer']) {
            $this->success('[DONE]');
            $this->newLine();
        } else {
            $this->error('[ERROR]');
            $this->newLine();
            echo $eventResult['message'];
            return $eventResult;
        }

        // Clear Laravel Lighthouse GraphQL schema cache
        $this->notice('Clearing Laravel Lighthouse GraphQL schema cache');
        echo ' .. ';

        $lighthouseResult = $this->laravelCmd(['lighthouse:clear-cache']);

        if ($lighthouseResult['answer']) {
            $this->success('[DONE]');
            $this->newLine();
        } else {
            $this->error('[ERROR]');
            $this->newLine();
            echo $lighthouseResult['message'];
            return $lighthouseResult;
        }

        // Clear PHP OPCache
        $this->notice('Clearing PHP OPCache');
        echo ' .. ';

        $fpmResult = $this->fpmCmd('reload');

        if ($fpmResult['answer']) {
            $this->success('[DONE]');
            $this->newLine();
        } else {
            $this->error('[ERROR]');
            $this->newLine();
            echo $fpmResult['message'];
            return $fpmResult;
        }

        // Publish all vendor files
        $this->notice('Publish all vendor files');
        echo ' .. ';

        $vendorResult = $this->laravelCmd(['vendor:publish', '--all', '--force']);

        if ($vendorResult['answer']) {
            $this->success('[DONE]');
            $this->newLine();
        } else {
            $this->error('[ERROR]');
            $this->newLine();
            echo $vendorResult['message'];
            return $vendorResult;
        }

        // Publish all LiveWire files
        $this->notice('Publish all LiveWire files');
        echo ' .. ';

        $livewireResult = $this->laravelCmd(['livewire:publish']);

        if ($livewireResult['answer']) {
            $this->success('[DONE]');
            $this->newLine();
        } else {
            $this->error('[ERROR]');
            $this->newLine();
            echo $livewireResult['message'];
            return $livewireResult;
        }

        return [
            'answer' => true,
            'message' => 'All Laravel caches purged successfully'
        ];
    }

    /**
     * Run PHPUnit test in app container
     */
    protected function testUnitLaravel(
        array $args = []
    ): array
    {
        $subjects = [];
        $nsubs = 0;
        $bd = dirname($this->composeDir);
        $cwd = getcwd();
        $phpunit = "{$this->roxBaseDir}/vendor/phpunit/phpunit/phpunit";
        $config = 'phpunit.xml';
        $config = "{$this->roxBaseDir}/{$config}";

        // Find number of arguments until first option (if any)
        foreach ($args as $index => $arg) {
            if (strpos($arg, '-') === 0) {
                break;
            }
            $nsubs++;
        }

        if ($nsubs === 0) {
            // No test subjects given - run full suite
            $command = ['php', '-f', $phpunit, '--', '-c', $config, ...array_slice($args, $nsubs)];
            return $this->containerExec('appserver', 'dockerhost', $command);
        }

        $results = ['answer' => true, 'message' => ''];

        for ($i = 0; $i < $nsubs; $i++) {
            $subject = $args[$i];

            if (!file_exists($subject)) {
                $this->error("Cannot access '$subject': No such file or directory");
                $this->newLine();
                $results['answer'] = false;
                $results['message'] .= "Cannot access '$subject': No such file or directory\n";
                continue;
            }

            $this->notice('Testing ');
            $this->success($subject);
            echo ' ..' . PHP_EOL;

            $hostpath = "{$cwd}/{$subject}";
            $guestpath = "{$this->roxBaseDir}/" . str_replace("{$bd}/", '', $hostpath);

            $command = ['php', '-f', $phpunit, '--', '-c', $config, ...array_slice($args, $nsubs), $guestpath];
            $result = $this->containerExec('appserver', 'dockerhost', $command);

            if (!$result['answer']) {
                $results['answer'] = false;
            }

            $results['message'] .= $result['message'] . "\n";
        }

        return $results;
    }

    /**
     * Run PHPUnit test with paratest in app container
     */
    protected function testUnitParatest(
        array $args = []
    ): array
    {
        $subjects = [];
        $nsubs = 0;
        $bd = dirname($this->composeDir);
        $cwd = getcwd();
        $phpunit = "{$this->roxBaseDir}/vendor/bin/paratest";
        $config = 'phpunit.xml';
        $config = "{$this->roxBaseDir}/{$config}";

        // Find number of arguments until first option (if any)
        foreach ($args as $index => $arg) {
            if (strpos($arg, '-') === 0) {
                break;
            }
            $nsubs++;
        }

        if ($nsubs === 0) {
            // No test subjects given - run full suite
            $command = ['./var/www/rox/script/paratest'];
            return $this->containerExec('appserver', 'dockerhost', $command);
        }

        $results = ['answer' => true, 'message' => ''];

        for ($i = 0; $i < $nsubs; $i++) {
            $subject = $args[$i];

            if (!file_exists($subject)) {
                $this->error("Cannot access '$subject': No such file or directory");
                $this->newLine();
                $results['answer'] = false;
                $results['message'] .= "Cannot access '$subject': No such file or directory\n";
                continue;
            }

            $this->notice('Testing ');
            $this->success($subject);
            echo ' ..' . PHP_EOL;

            $hostpath = "{$cwd}/{$subject}";
            $guestpath = "{$this->roxBaseDir}/" . str_replace("{$bd}/", '', $hostpath);

            $command = ['php', '-f', $phpunit, '--', '-c', $config, ...array_slice($args, $nsubs), $guestpath];
            $result = $this->containerExec('appserver', 'dockerhost', $command);

            if (!$result['answer']) {
                $results['answer'] = false;
            }

            $results['message'] .= $result['message'] . "\n";
        }

        return $results;
    }

    /**
     * Run PHPUnit test with coverage in app container
     */
    protected function testUnitCoverage(
        array $args = []
    ): array
    {
        $subjects = [];
        $nsubs = 0;
        $bd = dirname($this->composeDir);
        $cwd = getcwd();
        $phpunit = "{$this->roxBaseDir}/vendor/phpunit/phpunit/phpunit";
        $config = 'phpunit.xml';
        $config = "{$this->roxBaseDir}/{$config}";

        // Find number of arguments until first option (if any)
        foreach ($args as $index => $arg) {
            if (strpos($arg, '-') === 0) {
                break;
            }
            $nsubs++;
        }

        if ($nsubs === 0) {
            // No test subjects given - run full suite
            $command = [
                'php',
                '-dxdebug.mode=coverage',
                '-f',
                $phpunit,
                '--',
                '-c',
                $config,
                '--coverage-html',
                '/var/www/public/reports/',
                ...array_slice($args, $nsubs)
            ];

            return $this->containerExec('appserver', 'dockerhost', $command);
        }

        $results = ['answer' => true, 'message' => ''];

        for ($i = 0; $i < $nsubs; $i++) {
            $subject = $args[$i];

            if (!file_exists($subject)) {
                $this->error("Cannot access '$subject': No such file or directory");
                $this->newLine();
                $results['answer'] = false;
                $results['message'] .= "Cannot access '$subject': No such file or directory\n";
                continue;
            }

            $this->notice('Testing ');
            $this->success($subject);
            echo ' ..' . PHP_EOL;

            $hostpath = "{$cwd}/{$subject}";
            $guestpath = "{$this->roxBaseDir}/" . str_replace("{$bd}/", '', $hostpath);

            $command = [
                'php',
                '-dxdebug.mode=coverage',
                '-f',
                $phpunit,
                '--',
                '-c',
                $config,
                '--coverage-html',
                '/var/www/public/reports/',
                ...array_slice($args, $nsubs),
                $guestpath
            ];

            $result = $this->containerExec('appserver', 'dockerhost', $command);

            if (!$result['answer']) {
                $results['answer'] = false;
            }

            $results['message'] .= $result['message'] . "\n";
        }

        return $results;
    }

    /**
     * Display usage information
     */
    protected function showUsage(): array
    {
        $scriptName = basename(__FILE__);

        echo <<<EOT
Usage:
  ./{$scriptName} <command>
--------------------------------------
  laravel                   Run Laravel Artisan CLI command in app container
  artisan                   Run Laravel Artisan CLI command in app container
  phpdoc                    Render documentation of the named folder into a phpdoc folder
  composer                  Run composer command in app container
  shell <container> <user>  Run bash in given container
  db                        Open MySQL CLI
    db dump <database>      Output MySQL dump. Database = main or any existing name
    db admin                Run mysqladmin command
    db nuke                 Drop and re-create empty database
    db local                Sets the env file for local database in docker
    db dev                  Sets the env file for dev database. Use VPN.
    db live                 Sets the env file for live database You get read only. Use VPN.
  cache                     Open Redis CLI
    cache fpc               Open Page Cache Redis CLI
    cache session           Open Session Redis CLI
  httpd                     Run apachectl command
  xdebug {on|off}           Enable/disable xdebug
  jit {on|off}              Enable/disable JIT compiler
  opcache {on|off|full}     Enable/disable opcache. Full=empty blacklist
  npm                       Run npm command
  grunt                     Run Grunt command
  purge                     Clean all cache layers for a neutral platform
    laravel                 Clean all cache layers for Laravel
  container                 Container commands
    ip {app/web/cache/db}   Get IP for a container
    url                     Set web server URL in app HOSTS file
  data-sync                 Synchronize with remote data
    data-sync db            Download and import a remote database
    data-sync media         Download remote media files
  unit <path> <options>     Run PHPUnit tests
  unit {laravel|paratest|coverage}  Run PHPUnit tests
  analyse                   Analyse the PHP code with PHPStan

All unrecognized command are passed on to docker-compose:
Ex. './{$scriptName} up' will call 'docker-compose up'

EOT;

        return ['answer' => true, 'message' => 'Usage information displayed'];
    }

    /**
     * Main function that processes command line arguments
     */
    public function main(
        array $args = []
    ): array
    {
        if (empty($args) === true) {
            return $this->showUsage();
        }

        $command = $args[0];
        $subArgs = array_slice($args, 1);

        // Handle "laravel" action
        if ($command === 'laravel') {
            return $this->laravelCmd($subArgs);
        }

        // Handle laravel artisan action
        if ($command === 'artisan') {
            return $this->laravelCmd($subArgs);
        }

        // Handle "composer" action
        if ($command === 'composer') {
            return $this->composerCmd($subArgs);
        }

        // Handle "phpdoc" action
        if ($command === 'phpdoc') {
            return $this->phpdocCmd(...$subArgs);
        }

        // Handle "shell" action
        if ($command === 'shell') {
            $container = $subArgs[0] ?? 'appserver';
            $user = $subArgs[1] ?? '';
            return $this->execShell($container, $user);
        }

        // Handle "db" action
        if ($command === 'db') {
            $subCommand = $subArgs[0] ?? '';

            if ($subCommand === 'nuke') {
                $this->mysqlCmd(['admin', '--force', 'drop', $this->roxDbName]);
                return $this->mysqlCmd(['admin', '--default-character-set=utf8', 'create', $this->roxDbName]);
            }

            if ($subCommand === 'admin') {
                return $this->mysqlAdmin(array_slice($subArgs, 1));
            }

            if ($subCommand === 'dump') {
                $db = $subArgs[1] ?? '';
                return $this->mysqlDump($db);
            }

            if ($subCommand === 'local') {
                return $this->setEnv('.env.rox-local-db-with-passwords');
            }

            if ($subCommand === 'dev') {
                return $this->setEnv('.env.rox-dev-db-with-passwords');
            }

            if ($subCommand === 'live') {
                return $this->setEnv('.env.rox-live-db-with-passwords');
            }

            return $this->mysqlCmd($subArgs);
        }

        // Handle "cache" action
        if ($command === 'cache') {
            return $this->redisCmd($subArgs);
        }

        // Handle "httpd" action
        if ($command === 'httpd') {
            return $this->containerExec('webserver', 'root', ['apachectl', ...$subArgs]);
        }

        // Handle "xdebug", "gnupg", "mongodb" actions
        if (in_array($command, ['xdebug', 'gnupg', 'mongodb'])) {
            $action = $subArgs[0] ?? '';

            if ($action === 'off') {
                $this->notice("Disabling {$command}");
                $this->newLine();
                $result = $this->containerExec('appserver', 'root', ['phpdismod', $command]);
            } elseif ($action === 'on') {
                $this->notice("Enabling {$command}");
                $this->newLine();
                $result = $this->containerExec('appserver', 'root', ['phpenmod', $command]);
            } else {
                return ['answer' => false, 'message' => "Invalid action for {$command}. Use 'on' or 'off'."];
            }

            if ($result['answer']) {
                return $this->fpmCmd('reload');
            }

            return $result;
        }

        // Handle "opcache" action
        if ($command === 'opcache') {
            $action = $subArgs[0] ?? '';

            if ($action === 'off') {
                $this->notice("Disabling {$command}");
                $this->newLine();
                $result = $this->containerExec('appserver', 'root', ['phpdismod', $command]);
            } elseif ($action === 'on') {
                $this->notice("Enabling normal {$command}");
                $this->newLine();
                $cpResult = $this->containerExec('appserver', 'root', ['cp', '/etc/php/opcache-on.blacklist', '/etc/php/opcache.blacklist']);

                if (!$cpResult['answer']) {
                    return $cpResult;
                }

                $result = $this->containerExec('appserver', 'root', ['phpenmod', $command]);
            } elseif ($action === 'full') {
                $this->notice("Enabling full speed {$command}");
                $this->newLine();
                $cpResult = $this->containerExec('appserver', 'root', ['cp', '/etc/php/opcache-full.blacklist', '/etc/php/opcache.blacklist']);

                if (!$cpResult['answer']) {
                    return $cpResult;
                }

                $result = $this->containerExec('appserver', 'root', ['phpenmod', $command]);
            } else {
                return ['answer' => false, 'message' => "Invalid action for {$command}. Use 'on', 'off', or 'full'."];
            }

            if ($result['answer']) {
                return $this->fpmCmd('reload');
            }

            return $result;
        }

        // Handle "jit" action
        if ($command === 'jit') {
            $action = $subArgs[0] ?? '';

            if ($action === 'off') {
                $this->notice("Disabling {$command}");
                $this->newLine();
                $disableResult = $this->containerExec('appserver', 'root', ['phpdismod', 'opcache']);

                if (!$disableResult['answer']) {
                    return $disableResult;
                }

                $cpResult = $this->containerExec('appserver', 'root', [
                    'cp',
                    "/etc/php/{$this->phpVersion}/mods-available/opcache-on.ini",
                    "/etc/php/{$this->phpVersion}/mods-available/opcache.ini"
                ]);

                if (!$cpResult['answer']) {
                    return $cpResult;
                }

                $result = $this->containerExec('appserver', 'root', ['phpenmod', 'opcache']);
            } elseif ($action === 'on') {
                $this->notice("Enabling {$command}");
                $this->newLine();
                $disableResult = $this->containerExec('appserver', 'root', ['phpdismod', 'opcache']);

                if (!$disableResult['answer']) {
                    return $disableResult;
                }

                $cpResult = $this->containerExec('appserver', 'root', [
                    'cp',
                    "/etc/php/{$this->phpVersion}/mods-available/opcache-jit.ini",
                    "/etc/php/{$this->phpVersion}/mods-available/opcache.ini"
                ]);

                if (!$cpResult['answer']) {
                    return $cpResult;
                }

                $result = $this->containerExec('appserver', 'root', ['phpenmod', 'opcache']);
            } else {
                return ['answer' => false, 'message' => "Invalid action for {$command}. Use 'on' or 'off'."];
            }

            if ($result['answer']) {
                return $this->fpmCmd('reload');
            }

            return $result;
        }

        // Handle "npm" and "grunt" actions
        if ($command === 'npm' || $command === 'grunt') {
            $cmd = implode(' ', array_map('escapeshellarg', $args));
            return $this->containerExec('appserver', 'dockerhost', ['bash', '-c', "cd /var/www/ && {$cmd}"]);
        }

        // Handle "purge" action
        if ($command === 'purge') {
            $subCommand = $subArgs[0] ?? '';

            if ($subCommand === 'laravel') {
                return $this->purgeAllLaravel();
            }

            return $this->purgeAllIndependent();
        }

        // Handle "container" action
        if ($command === 'container') {
            $subCommand = $subArgs[0] ?? '';

            if ($subCommand === 'ip') {
                $container = $subArgs[1] ?? '';
                return $this->getIp($container);
            }

            if ($subCommand === 'url') {
                return $this->updateHostsFile();
            }

            return ['answer' => false, 'message' => "Invalid container command. Use 'ip' or 'url'."];
        }

        // Handle "data-sync" action
        if ($command === 'data-sync') {
            $subCommand = $subArgs[0] ?? '';

            if ($subCommand === 'db') {
                $script = "{$this->composeDir}/data-sync/db.sh";
                $scriptArgs = array_slice($subArgs, 1);
                $cmd = escapeshellcmd($script) . ' ' . implode(' ', array_map('escapeshellarg', $scriptArgs));

                $output = [];
                $returnVar = 0;
                exec($cmd, $output, $returnVar);

                return [
                    'answer' => ($returnVar === 0),
                    'message' => implode("\n", $output)
                ];
            }

            if ($subCommand === 'media') {
                $script = "{$this->composeDir}/data-sync/media.sh";
                $scriptArgs = array_slice($subArgs, 1);
                $cmd = escapeshellcmd($script) . ' ' . implode(' ', array_map('escapeshellarg', $scriptArgs));

                $output = [];
                $returnVar = 0;
                exec($cmd, $output, $returnVar);

                return [
                    'answer' => ($returnVar === 0),
                    'message' => implode("\n", $output)
                ];
            }

            $this->error('Synchronize what?');
            $this->newLine();
            echo 'Did you mean "data-sync db" or "data-sync media"?' . PHP_EOL;

            return ['answer' => false, 'message' => 'Invalid data-sync command'];
        }

        // Handle "unit" action
        if ($command === 'unit') {
            $subCommand = $subArgs[0] ?? '';

            if ($subCommand === 'laravel') {
                return $this->testUnitLaravel(array_slice($subArgs, 1));
            }

            if ($subCommand === 'paratest') {
                // Turn off xdebug. We want speed
                $xdebugResult = $this->containerExec('appserver', 'root', ['phpdismod', 'xdebug']);

                if (!$xdebugResult['answer']) {
                    return $xdebugResult;
                }

                return $this->testUnitParatest(array_slice($subArgs, 1));
            }

            if ($subCommand === 'coverage') {
                // Turn ON xdebug. It is required
                $xdebugResult = $this->containerExec('appserver', 'root', ['phpenmod', 'xdebug']);

                if (!$xdebugResult['answer']) {
                    return $xdebugResult;
                }

                return $this->testUnitCoverage(array_slice($subArgs, 1));
            }

            return $this->testUnitLaravel($subArgs);
        }

        // Handle "analyse" action
        if ($command === 'analyse') {
            return $this->phpstanCmd($subArgs);
        }

        // Handle empty action
        if (empty($command)) {
            return $this->showUsage();
        }

        // Dispatch all other commands to docker-compose
        $result = $this->composeCmd($args);

        if ($command === 'start') {
            $pwdResult = shell_exec('pwd');
            echo $pwdResult;

            $cpResult = shell_exec('cp rox/phpstan.neon.dist phpstan.neon.dist');
            $this->updateHostsFile();

            $this->notice('Enabling opcache');
            $this->newLine();
            $this->containerExec('appserver', 'root', ['phpenmod', 'opcache']);

            $this->notice('Enabling xdebug');
            $this->newLine();
            $this->containerExec('appserver', 'root', ['phpenmod', 'xdebug']);

            $this->notice('Enabling gnupg');
            $this->newLine();
            $this->containerExec('appserver', 'root', ['phpenmod', 'gnupg']);

            $this->notice('Enabling mongodb');
            $this->newLine();
            $this->containerExec('appserver', 'root', ['phpenmod', 'mongodb']);

            $this->setSymlink();
        }

        if ($command === 'stop') {
            echo "Stopping" . PHP_EOL;
        }

        return $result;
    }
}

$main = new Main();
$args = array_slice($argv, 1); // Get command line arguments (skip the script name)
$result = $main->main($args);
exit($result['answer'] ? 0 : 1); // Return success/failure status
