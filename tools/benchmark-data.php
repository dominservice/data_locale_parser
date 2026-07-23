<?php

declare(strict_types=1);

use Dominservice\DataLocaleParser\CompiledDataRepository;

require dirname(__DIR__).'/vendor/autoload.php';

$root = dirname(__DIR__);
$legacyDirectory = $argv[1] ?? $root.'/build/legacy-data';
$rounds = isset($argv[2]) ? max(3, (int) $argv[2]) : 9;
$outputPath = $argv[3] ?? null;
$contract = json_decode(
    (string) file_get_contents($root.'/tools/data-builder/config/runtime-contract.json'),
    true,
    512,
    JSON_THROW_ON_ERROR
);

if (!is_dir($legacyDirectory.'/country')) {
    fwrite(STDERR, sprintf(
        "Legacy data directory \"%s\" is unavailable.\n",
        $legacyDirectory
    ));
    exit(1);
}

$loadLegacy = static function (bool $sorted) use ($contract, $legacyDirectory): int {
    $valueCount = 0;

    foreach ($contract['types'] as $definition) {
        $directory = $definition['directory'];

        foreach ($definition['locales'] as $locale) {
            $payload = require sprintf(
                '%s/%s/%s/%s.php',
                $legacyDirectory,
                $directory,
                $locale,
                $directory
            );

            if ($sorted) {
                (new Collator($locale))->asort($payload);
            }

            $valueCount += count($payload);
        }
    }

    return $valueCount;
};

$loadCompiled = static function (bool $sorted) use ($contract, $root): int {
    $repository = new CompiledDataRepository($root.'/data');
    $valueCount = 0;

    foreach ($contract['types'] as $type => $definition) {
        foreach ($definition['locales'] as $locale) {
            $payload = $repository->load($type, $locale);

            if ($sorted) {
                (new Collator($locale))->asort($payload);
            }

            $valueCount += count($payload);
        }
    }

    return $valueCount;
};

// Warm the operating-system filesystem cache before collecting medians.
$loadLegacy(false);
$loadCompiled(false);

$results = [
    'schema' => 1,
    'environment' => [
        'php' => PHP_VERSION,
        'intl' => INTL_ICU_VERSION,
        'rounds' => $rounds,
    ],
    'scenarios' => [],
];

foreach ([
    'load_all_raw' => false,
    'load_and_sort_all' => true,
] as $scenario => $sorted) {
    $legacyTimes = measure($loadLegacy, $sorted, $rounds);
    $compiledTimes = measure($loadCompiled, $sorted, $rounds);
    $legacyMedian = median($legacyTimes);
    $compiledMedian = median($compiledTimes);

    $results['scenarios'][$scenario] = [
        'legacy_median_ms' => round($legacyMedian, 3),
        'compiled_median_ms' => round($compiledMedian, 3),
        'saved_ms' => round($legacyMedian - $compiledMedian, 3),
        'speedup' => round($legacyMedian / $compiledMedian, 3),
        'time_reduction_percent' => round(
            (($legacyMedian - $compiledMedian) / $legacyMedian) * 100,
            2
        ),
        'legacy_samples_ms' => array_map(static fn (float $time): float => round($time, 3), $legacyTimes),
        'compiled_samples_ms' => array_map(static fn (float $time): float => round($time, 3), $compiledTimes),
    ];
}

$typicalIterations = 500;
$legacyTypical = static function () use ($legacyDirectory, $typicalIterations): int {
    $valueCount = 0;

    for ($iteration = 0; $iteration < $typicalIterations; $iteration++) {
        foreach (['country', 'currency', 'language'] as $directory) {
            $path = $legacyDirectory.'/'.$directory.'/pl_PL/'.$directory.'.php';

            if (!is_file($path)) {
                throw new RuntimeException(sprintf('Missing legacy benchmark file "%s".', $path));
            }

            $valueCount += count(require $path);
        }
    }

    return $valueCount;
};
$compiledTypical = static function () use ($root, $typicalIterations): int {
    $valueCount = 0;

    for ($iteration = 0; $iteration < $typicalIterations; $iteration++) {
        $repository = new CompiledDataRepository($root.'/data');
        $valueCount += count($repository->load('countries', 'pl_PL'));
        $valueCount += count($repository->load('currencies', 'pl_PL'));
        $valueCount += count($repository->load('languages', 'pl_PL'));
    }

    return $valueCount;
};
$legacyTypical();
$compiledTypical();
$legacyTypicalTimes = measureExpected(
    $legacyTypical,
    $rounds,
    $typicalIterations * (249 + 285 + 609)
);
$compiledTypicalTimes = measureExpected(
    $compiledTypical,
    $rounds,
    $typicalIterations * (249 + 285 + 609)
);
$legacyTypicalMedian = median($legacyTypicalTimes) / $typicalIterations;
$compiledTypicalMedian = median($compiledTypicalTimes) / $typicalIterations;
$results['scenarios']['typical_three_raw_lists'] = [
    'locale' => 'pl_PL',
    'iterations_per_sample' => $typicalIterations,
    'legacy_median_ms_per_request' => round($legacyTypicalMedian, 4),
    'compiled_median_ms_per_request' => round($compiledTypicalMedian, 4),
    'saved_ms_per_request' => round($legacyTypicalMedian - $compiledTypicalMedian, 4),
    'speedup' => round($legacyTypicalMedian / $compiledTypicalMedian, 3),
    'time_reduction_percent' => round(
        (($legacyTypicalMedian - $compiledTypicalMedian) / $legacyTypicalMedian) * 100,
        2
    ),
];

$sortedIterations = 500;
$legacyRepeatedSort = static function () use ($legacyDirectory, $sortedIterations): int {
    $raw = require $legacyDirectory.'/country/pl_PL/country.php';
    $count = 0;

    for ($iteration = 0; $iteration < $sortedIterations; $iteration++) {
        $payload = $raw;
        (new Collator('pl_PL'))->asort($payload);
        $count += count($payload);
    }

    return $count;
};
$compiledRepeatedSort = static function () use ($root, $sortedIterations): int {
    $repository = new CompiledDataRepository($root.'/data');
    $raw = $repository->load('countries', 'pl_PL');
    $sorted = null;
    $count = 0;

    for ($iteration = 0; $iteration < $sortedIterations; $iteration++) {
        if ($sorted === null) {
            $sorted = $raw;
            (new Collator('pl_PL'))->asort($sorted);
        }

        $count += count($sorted);
    }

    return $count;
};
$legacySortTimes = measureExpected(
    $legacyRepeatedSort,
    $rounds,
    $sortedIterations * 249
);
$compiledSortTimes = measureExpected(
    $compiledRepeatedSort,
    $rounds,
    $sortedIterations * 249
);
$legacySortMedian = median($legacySortTimes);
$compiledSortMedian = median($compiledSortTimes);
$results['scenarios']['repeat_same_sorted_list'] = [
    'locale' => 'pl_PL',
    'calls_per_sample' => $sortedIterations,
    'legacy_median_ms' => round($legacySortMedian, 3),
    'compiled_median_ms' => round($compiledSortMedian, 3),
    'saved_ms' => round($legacySortMedian - $compiledSortMedian, 3),
    'speedup' => round($legacySortMedian / $compiledSortMedian, 3),
    'time_reduction_percent' => round(
        (($legacySortMedian - $compiledSortMedian) / $legacySortMedian) * 100,
        2
    ),
];

$json = json_encode(
    $results,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
)."\n";

if (is_string($outputPath) && $outputPath !== '') {
    $resolvedOutputPath = str_starts_with($outputPath, '/')
        ? $outputPath
        : $root.'/'.ltrim($outputPath, '/');
    $outputDirectory = dirname($resolvedOutputPath);

    if (!is_dir($outputDirectory)
        && !mkdir($outputDirectory, 0777, true)
        && !is_dir($outputDirectory)) {
        throw new RuntimeException(sprintf(
            'Unable to create benchmark output directory "%s".',
            $outputDirectory
        ));
    }

    if (file_put_contents($resolvedOutputPath, $json) === false) {
        throw new RuntimeException(sprintf(
            'Unable to write benchmark output "%s".',
            $resolvedOutputPath
        ));
    }
}

fwrite(
    STDOUT,
    $json
);

/**
 * @return list<float>
 */
function measure(callable $callback, bool $sorted, int $rounds): array
{
    $times = [];

    for ($round = 0; $round < $rounds; $round++) {
        gc_collect_cycles();
        $start = hrtime(true);
        $count = $callback($sorted);
        $elapsed = (hrtime(true) - $start) / 1_000_000;

        if ($count !== 661280) {
            throw new RuntimeException(sprintf('Unexpected benchmark value count: %d.', $count));
        }

        $times[] = $elapsed;
    }

    return $times;
}

/**
 * @return list<float>
 */
function measureExpected(callable $callback, int $rounds, int $expectedCount): array
{
    $times = [];

    for ($round = 0; $round < $rounds; $round++) {
        gc_collect_cycles();
        $start = hrtime(true);
        $count = $callback();
        $elapsed = (hrtime(true) - $start) / 1_000_000;

        if ($count !== $expectedCount) {
            throw new RuntimeException(sprintf('Unexpected benchmark value count: %d.', $count));
        }

        $times[] = $elapsed;
    }

    return $times;
}

/**
 * @param list<float> $values
 */
function median(array $values): float
{
    sort($values, SORT_NUMERIC);
    $middle = intdiv(count($values), 2);

    return count($values) % 2 === 1
        ? $values[$middle]
        : ($values[$middle - 1] + $values[$middle]) / 2;
}
