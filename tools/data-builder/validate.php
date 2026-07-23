<?php

declare(strict_types=1);

use Dominservice\DataLocaleParser\Build\Json;
use Dominservice\DataLocaleParser\Build\SourceManager;
use Dominservice\DataLocaleParser\Build\Validator;

require __DIR__.'/src/Json.php';
require __DIR__.'/src/SourceManager.php';
require __DIR__.'/src/Validator.php';

$root = dirname(__DIR__, 2);
$argument = $argv[1] ?? 'data';
$dataDirectory = str_starts_with($argument, '/')
    ? $argument
    : $root.'/'.ltrim($argument, '/');

$sourceManager = new SourceManager(__DIR__.'/data-sources.lock.json');
$sourceManager->validateMaintainedRuntimeSources($root.'/data');
$validator = new Validator(__DIR__.'/config/runtime-contract.json');
$result = $validator->validate($dataDirectory);

fwrite(STDOUT, sprintf(
    "Validated %s %s data.\n",
    $result['source']['name'] ?? 'compiled',
    $result['source']['version'] ?? ''
));

foreach ($result['types'] as $type => $metrics) {
    fwrite(STDOUT, sprintf(
        "%s: %d locales, %d-%d keys, %d key layouts, %d payloads, %d aliases.\n",
        $type,
        $metrics['locale_count'],
        $metrics['key_count_min'],
        $metrics['key_count_max'],
        $metrics['key_set_count'],
        $metrics['payload_count'],
        $metrics['deduplicated_locale_count']
    ));
}
