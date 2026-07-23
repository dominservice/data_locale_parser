<?php

declare(strict_types=1);

namespace Dominservice\DataLocaleParser\Build;

use PharData;
use RuntimeException;

final class SourceManager
{
    /** @var array<mixed> */
    private array $lock;

    public function __construct(private string $lockPath)
    {
        $this->lock = Json::read($lockPath);
    }

    public function cldrVersion(): string
    {
        return (string) ($this->lock['cldr_version'] ?? '');
    }

    /**
     * @return array<string, string>
     */
    public function prepare(string $buildDirectory): array
    {
        $cacheDirectory = $buildDirectory.'/cache';
        $sourceDirectory = $buildDirectory.'/sources';
        $prepared = [];

        foreach ($this->lock['sources'] ?? [] as $name => $source) {
            if (!is_string($name) || !is_array($source)) {
                throw new RuntimeException('The source lock contains an invalid source definition.');
            }

            $url = (string) ($source['url'] ?? '');
            $expectedHash = (string) ($source['sha256'] ?? '');
            $archive = $cacheDirectory.'/'.$name.'-'.$this->cldrVersion().'.tgz';
            $destination = $sourceDirectory.'/'.$name.'-'.$this->cldrVersion();
            $packageDirectory = $destination.'/package';

            $this->createDirectory($cacheDirectory);

            if (!is_file($archive)) {
                $this->download($url, $archive);
            }

            $actualHash = hash_file('sha256', $archive);

            if ($actualHash === false || !hash_equals($expectedHash, $actualHash)) {
                throw new RuntimeException(sprintf(
                    'Checksum mismatch for "%s": expected %s, got %s.',
                    $archive,
                    $expectedHash,
                    $actualHash === false ? '<unreadable>' : $actualHash
                ));
            }

            if (!is_file($packageDirectory.'/package.json')) {
                $this->createDirectory($destination);

                try {
                    (new PharData($archive))->extractTo($destination, null, true);
                } catch (\Throwable $exception) {
                    throw new RuntimeException(
                        sprintf('Unable to extract source archive "%s": %s', $archive, $exception->getMessage()),
                        0,
                        $exception
                    );
                }
            }

            if (!is_dir($packageDirectory)) {
                throw new RuntimeException(sprintf(
                    'Source archive "%s" did not contain the expected package directory.',
                    $archive
                ));
            }

            $prepared[$name] = $packageDirectory;
        }

        return $prepared;
    }

    public function validateMaintainedRuntimeSources(string $dataDirectory): void
    {
        foreach ($this->lock['maintained_runtime_sources'] ?? [] as $file => $expectedHash) {
            $path = $dataDirectory.'/'.$file;
            $actualHash = is_file($path) ? hash_file('sha256', $path) : false;

            if ($actualHash === false || !hash_equals((string) $expectedHash, $actualHash)) {
                throw new RuntimeException(sprintf(
                    'Maintained runtime source "%s" changed without updating the source lock.',
                    $path
                ));
            }
        }
    }

    private function download(string $url, string $destination): void
    {
        if ($url === '') {
            throw new RuntimeException('The source URL cannot be empty.');
        }

        $temporary = $destination.'.part-'.getmypid();
        $input = @fopen($url, 'rb');

        if ($input === false) {
            throw new RuntimeException(sprintf('Unable to download source "%s".', $url));
        }

        $output = @fopen($temporary, 'wb');

        if ($output === false) {
            fclose($input);
            throw new RuntimeException(sprintf('Unable to create temporary file "%s".', $temporary));
        }

        try {
            if (stream_copy_to_stream($input, $output) === false) {
                throw new RuntimeException(sprintf('Unable to download source "%s".', $url));
            }
        } finally {
            fclose($input);
            fclose($output);
        }

        if (!rename($temporary, $destination)) {
            throw new RuntimeException(sprintf('Unable to move downloaded source to "%s".', $destination));
        }
    }

    private function createDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create directory "%s".', $directory));
        }
    }
}
