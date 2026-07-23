<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$resultsDirectory = $root.'/build/test-results';

if (!is_dir($resultsDirectory) && !mkdir($resultsDirectory, 0777, true) && !is_dir($resultsDirectory)) {
    fwrite(STDERR, sprintf("Unable to create test results directory \"%s\".\n", $resultsDirectory));
    exit(1);
}

$command = [
    PHP_BINARY,
    $root.'/vendor/bin/phpunit',
    '--log-junit',
    $resultsDirectory.'/junit.xml',
];

$escaped = implode(' ', array_map('escapeshellarg', $command));
passthru($escaped, $exitCode);

exit($exitCode);
