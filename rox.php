#!/usr/bin/env php
<?php

$cmd = 'rox/main.php';
$cwd = getcwd();

// Traverse up directories until we find main.php
while (!file_exists($cmd)) {
    chdir('..');
    if (getcwd() === '/') {
        fwrite(STDERR, "'$cmd' not found\n");
        exit(1);
    }
}

$cmd = getcwd() . '/' . $cmd;

// Execute main.php with all arguments
require_once $cmd;

// The main.php file defines a Main class, so we need to instantiate it and call the main method
$main = new Main();
$main->main(array_slice($argv, 1));
