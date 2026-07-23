<?php

declare(strict_types=1);

namespace Dominservice\DataLocaleParser\Tests\Support;

use Illuminate\Support\Collection;
use JsonSerializable;

trait NormalizesSnapshotValues
{
    /**
     * @return mixed
     */
    protected function normalizeSnapshotValue(mixed $value, bool $sortAssociativeKeys = false): mixed
    {
        if ($value instanceof Collection) {
            return [
                '__type' => $value::class,
                'items' => $this->normalizeSnapshotValue($value->all(), $sortAssociativeKeys),
            ];
        }

        if (is_object($value)) {
            $properties = $value instanceof JsonSerializable
                ? $value->jsonSerialize()
                : get_object_vars($value);

            return [
                '__type' => $value::class,
                'properties' => $this->normalizeSnapshotValue($properties, $sortAssociativeKeys),
            ];
        }

        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeSnapshotValue($item, $sortAssociativeKeys);
            }

            if ($sortAssociativeKeys && !$this->snapshotArrayIsList($normalized)) {
                ksort($normalized);
            }

            return $normalized;
        }

        return $value;
    }

    /**
     * @param array<mixed> $value
     */
    private function snapshotArrayIsList(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}
