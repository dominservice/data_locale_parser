<?php

declare(strict_types=1);

namespace Dominservice\DataLocaleParser\Build;

use JsonException;
use RuntimeException;

final class Json
{
    /**
     * @return array<mixed>
     */
    public static function read(string $path): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read JSON file "%s".', $path));
        }

        try {
            $value = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                sprintf('Invalid JSON file "%s": %s', $path, $exception->getMessage()),
                0,
                $exception
            );
        }

        if (!is_array($value)) {
            throw new RuntimeException(sprintf('JSON file "%s" must contain an object.', $path));
        }

        return $value;
    }

    /**
     * @param array<mixed> $value
     */
    public static function write(string $path, array $value): void
    {
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create directory "%s".', $directory));
        }

        try {
            $contents = json_encode(
                $value,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_PRESERVE_ZERO_FRACTION
                | JSON_THROW_ON_ERROR
            )."\n";
        } catch (JsonException $exception) {
            throw new RuntimeException(
                sprintf('Unable to encode JSON file "%s": %s', $path, $exception->getMessage()),
                0,
                $exception
            );
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException(sprintf('Unable to write JSON file "%s".', $path));
        }
    }
}
