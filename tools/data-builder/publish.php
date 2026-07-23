<?php

declare(strict_types=1);

use Dominservice\DataLocaleParser\Build\Validator;

require __DIR__.'/src/Json.php';
require __DIR__.'/src/Validator.php';

$root = dirname(__DIR__, 2);
$candidateArgument = $argv[1] ?? 'build/runtime-data';
$targetArgument = $argv[2] ?? 'data';
$candidate = absoluteDirectory($root, $candidateArgument);
$target = absoluteDirectory($root, $targetArgument);
$validator = new Validator(__DIR__.'/config/runtime-contract.json');
$validator->validate($candidate);

if (!is_dir($target)) {
    throw new RuntimeException(sprintf('Runtime data directory "%s" does not exist.', $target));
}

$backup = $root.'/build/publish-backups/'.date('Ymd-His').'-'.getmypid();

if (!mkdir($backup, 0777, true) && !is_dir($backup)) {
    throw new RuntimeException(sprintf('Unable to create backup directory "%s".', $backup));
}

$entries = ['country', 'currency', 'language', 'manifest.php'];
$touched = [];

try {
    foreach ($entries as $entry) {
        $source = $candidate.'/'.$entry;
        $destination = $target.'/'.$entry;
        $backupPath = $backup.'/'.$entry;

        if (!file_exists($source)) {
            throw new RuntimeException(sprintf('Candidate entry "%s" is missing.', $source));
        }

        if (file_exists($destination) && !rename($destination, $backupPath)) {
            throw new RuntimeException(sprintf('Unable to back up "%s".', $destination));
        }

        $touched[] = $entry;
        copyEntry($source, $destination);
    }

    $validator->validate($target);
} catch (Throwable $exception) {
    foreach (array_reverse($touched) as $entry) {
        $backupPath = $backup.'/'.$entry;
        $destination = $target.'/'.$entry;

        if (file_exists($backupPath)) {
            removeEntry($destination);

            if (!rename($backupPath, $destination)) {
                throw new RuntimeException(sprintf(
                    'Publishing failed and backup "%s" could not be restored.',
                    $backupPath
                ), 0, $exception);
            }
        } else {
            removeEntry($destination);
        }
    }

    throw $exception;
}

fwrite(STDOUT, sprintf(
    "Published compiled runtime data to %s.\nPrevious data backup: %s\n",
    $target,
    $backup
));

function absoluteDirectory(string $root, string $path): string
{
    return str_starts_with($path, '/') ? rtrim($path, '/') : $root.'/'.trim($path, '/');
}

function copyEntry(string $source, string $destination): void
{
    if (is_file($source)) {
        if (!copy($source, $destination)) {
            throw new RuntimeException(sprintf('Unable to copy "%s" to "%s".', $source, $destination));
        }

        return;
    }

    if (!mkdir($destination, 0777, true) && !is_dir($destination)) {
        throw new RuntimeException(sprintf('Unable to create directory "%s".', $destination));
    }

    $iterator = new DirectoryIterator($source);

    foreach ($iterator as $entry) {
        if ($entry->isDot()) {
            continue;
        }

        copyEntry($entry->getPathname(), $destination.'/'.$entry->getFilename());
    }
}

function removeEntry(string $path): void
{
    if (is_file($path) || is_link($path)) {
        unlink($path);

        return;
    }

    if (!is_dir($path)) {
        return;
    }

    $iterator = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);

    foreach ($iterator as $entry) {
        removeEntry($entry->getPathname());
    }

    rmdir($path);
}
