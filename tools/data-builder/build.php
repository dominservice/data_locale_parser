<?php

declare(strict_types=1);

use Dominservice\DataLocaleParser\Build\Compiler;
use Dominservice\DataLocaleParser\Build\Json;
use Dominservice\DataLocaleParser\Build\SourceManager;

require __DIR__.'/src/Json.php';
require __DIR__.'/src/SourceManager.php';
require __DIR__.'/src/LocaleResolver.php';
require __DIR__.'/src/Compiler.php';

$root = dirname(__DIR__, 2);
$options = getopt('', ['build-dir::', 'output::', 'report::', 'legacy-data::', 'replace']);
$buildDirectory = absolutePath($root, $options['build-dir'] ?? 'build/data-builder');
$outputDirectory = absolutePath($root, $options['output'] ?? 'build/runtime-data');
$reportPath = absolutePath($root, $options['report'] ?? 'build/reports/data-build.json');
$replace = array_key_exists('replace', $options);
$legacyData = array_key_exists('legacy-data', $options)
    ? absolutePath($root, (string) $options['legacy-data'])
    : (is_file($root.'/data/country/en/country.php') ? $root.'/data' : null);

$sources = new SourceManager(__DIR__.'/data-sources.lock.json');
$sources->validateMaintainedRuntimeSources($root.'/data');
$prepared = $sources->prepare($buildDirectory);
$compiler = new Compiler(
    __DIR__.'/config/runtime-contract.json',
    __DIR__.'/config/compatibility-values.json',
    $prepared,
    $sources->cldrVersion()
);
$compileDirectory = $outputDirectory;
$outputHasEntries = is_dir($outputDirectory)
    && array_values(array_diff(scandir($outputDirectory) ?: [], ['.', '..'])) !== [];

if ($outputHasEntries) {
    if (!$replace) {
        throw new RuntimeException(sprintf(
            'Output directory "%s" is not empty. Pass --replace to replace a previous compiled build safely.',
            $outputDirectory
        ));
    }

    if (!is_file($outputDirectory.'/manifest.php')) {
        throw new RuntimeException(sprintf(
            'Refusing to replace "%s" because it is not a compiled data build.',
            $outputDirectory
        ));
    }

    $compileDirectory = $outputDirectory.'.staging-'.getmypid();
}

$report = $compiler->compile($compileDirectory, $legacyData);

if ($compileDirectory !== $outputDirectory) {
    $backupDirectory = $buildDirectory.'/build-backups';

    if (!is_dir($backupDirectory)
        && !mkdir($backupDirectory, 0777, true)
        && !is_dir($backupDirectory)) {
        throw new RuntimeException(sprintf(
            'Unable to create build backup directory "%s".',
            $backupDirectory
        ));
    }

    $backup = $backupDirectory.'/runtime-data-'.date('Ymd-His').'-'.getmypid();

    if (!rename($outputDirectory, $backup)) {
        throw new RuntimeException(sprintf(
            'Unable to back up previous build "%s".',
            $outputDirectory
        ));
    }

    if (!rename($compileDirectory, $outputDirectory)) {
        rename($backup, $outputDirectory);
        throw new RuntimeException(sprintf(
            'Unable to publish compiled build to "%s".',
            $outputDirectory
        ));
    }
}

Json::write($reportPath, $report);

fwrite(STDOUT, sprintf(
    "Compiled Unicode CLDR %s runtime data: %d files, %d logical bytes.\nReport: %s\n",
    $sources->cldrVersion(),
    $report['compiled']['file_count'],
    $report['compiled']['logical_bytes'],
    $reportPath
));

function absolutePath(string $root, string $path): string
{
    return str_starts_with($path, '/') ? $path : $root.'/'.ltrim($path, '/');
}
