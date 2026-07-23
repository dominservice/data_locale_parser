<?php

declare(strict_types=1);

namespace Dominservice\DataLocaleParser\Tests\Support;

use RuntimeException;

trait InteractsWithSnapshots
{
    /**
     * @param array<mixed> $actual
     */
    protected function assertMatchesJsonSnapshot(string $relativePath, array $actual): void
    {
        $json = $this->encodeSnapshot($actual);
        $recordDirectory = getenv('SNAPSHOT_RECORD_DIR');
        $updateBaseline = getenv('UPDATE_SNAPSHOTS') === '1';

        if ($recordDirectory !== false && $recordDirectory !== '') {
            $path = rtrim($recordDirectory, '/').'/'.$relativePath;
            $this->writeSnapshot($path, $json);
            $this->addToAssertionCount(1);

            return;
        }

        $path = dirname(__DIR__).'/Fixtures/snapshots/'.$relativePath;

        if ($updateBaseline) {
            $this->writeSnapshot($path, $json);
            $this->addToAssertionCount(1);

            return;
        }

        if (!is_file($path)) {
            throw new RuntimeException(sprintf(
                'Snapshot "%s" does not exist. Run "composer test:snapshots:update" to create it.',
                $relativePath
            ));
        }

        $expected = file_get_contents($path);

        if ($expected === false) {
            throw new RuntimeException(sprintf('Unable to read snapshot "%s".', $path));
        }

        $this->assertSame($expected, $json, sprintf(
            'Snapshot "%s" changed. Record candidate results with "composer test:snapshots:record".',
            $relativePath
        ));
    }

    /**
     * @param array<mixed> $value
     */
    protected function snapshotHash(array $value): string
    {
        return hash('sha256', $this->encodeSnapshot($value));
    }

    /**
     * @param array<mixed> $value
     */
    private function encodeSnapshot(array $value): string
    {
        $json = json_encode(
            $value,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_PRESERVE_ZERO_FRACTION
            | JSON_THROW_ON_ERROR
        );

        return $json."\n";
    }

    private function writeSnapshot(string $path, string $contents): void
    {
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create snapshot directory "%s".', $directory));
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException(sprintf('Unable to write snapshot "%s".', $path));
        }
    }
}
