<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$argument = $argv[1] ?? 'build/snapshots/current';

if ($argument === '--update') {
    putenv('UPDATE_SNAPSHOTS=1');
} else {
    $output = str_starts_with($argument, '/')
        ? $argument
        : $root.'/'.$argument;

    putenv('SNAPSHOT_RECORD_DIR='.$output);
}

$command = [
    PHP_BINARY,
    $root.'/vendor/bin/phpunit',
    '--testsuite',
    'snapshots',
];

$escaped = implode(' ', array_map('escapeshellarg', $command));
passthru($escaped, $exitCode);

exit($exitCode);
