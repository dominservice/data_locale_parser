<?php

declare(strict_types=1);

namespace Dominservice\DataLocaleParser\Build;

use RuntimeException;

final class LocaleResolver
{
    /** @var array<string, true> */
    private array $available = [];

    /** @var array<string, array<mixed>> */
    private array $languageAliases;

    /** @var array<string, string> */
    private array $likelySubtags;

    /** @var array<string, string> */
    private array $parentLocales;

    /** @var array<string, true> */
    private array $defaultContent = [];

    /**
     * @param list<string> $availableLocales
     * @param array<mixed> $aliases
     * @param array<string, string> $likelySubtags
     * @param array<string, string> $parentLocales
     * @param list<string> $defaultContent
     */
    public function __construct(
        array $availableLocales,
        array $aliases,
        array $likelySubtags,
        array $parentLocales,
        array $defaultContent
    ) {
        foreach ($availableLocales as $locale) {
            $this->available[$locale] = true;
        }

        $this->languageAliases = $aliases;
        $this->likelySubtags = $likelySubtags;
        $this->parentLocales = $parentLocales;

        foreach ($defaultContent as $locale) {
            $this->defaultContent[$locale] = true;
        }
    }

    /**
     * @return array{locale: string, reason: string}
     */
    public function resolve(string $requestedLocale): array
    {
        $requested = str_replace('_', '-', $requestedLocale);
        $queue = [[$requested, 'exact']];
        $visited = [];

        while ($queue !== []) {
            [$candidate, $reason] = array_shift($queue);

            if (isset($visited[$candidate])) {
                continue;
            }

            $visited[$candidate] = true;

            if (isset($this->available[$candidate])) {
                return ['locale' => $candidate, 'reason' => $reason];
            }

            foreach ($this->nextCandidates($candidate) as [$next, $nextReason]) {
                if (!isset($visited[$next])) {
                    $queue[] = [$next, $reason === 'exact' ? $nextReason : $reason.'+'.$nextReason];
                }
            }
        }

        throw new RuntimeException(sprintf(
            'Unable to resolve CLDR locale "%s". Candidates: %s.',
            $requestedLocale,
            implode(', ', array_keys($visited))
        ));
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function nextCandidates(string $locale): array
    {
        $candidates = [];
        $parts = explode('-', $locale);
        $language = $parts[0];
        $script = $this->scriptPart($parts);
        $region = $this->regionPart($parts);

        if ($language === 'no') {
            $replacement = array_merge(['nb'], array_slice($parts, 1));
            $candidates[] = [implode('-', $replacement), 'legacy-no-to-nb'];
        }

        $alias = $this->languageAliases[$language] ?? null;

        if (is_array($alias) && isset($alias['_replacement'])) {
            $replacement = explode('-', (string) $alias['_replacement']);

            if (count($replacement) === 1) {
                $replacement = array_merge($replacement, array_slice($parts, 1));
            } else {
                if ($region !== null && $this->regionPart($replacement) === null) {
                    $replacement[] = $region;
                }
            }

            $candidates[] = [implode('-', $replacement), 'language-alias'];
        }

        $maximized = $this->maximizedLocale($locale, $language, $script, $region);

        if ($maximized !== null && $maximized !== $locale) {
            $candidates[] = [$maximized, 'likely-subtag'];
        }

        if (isset($this->parentLocales[$locale])) {
            $parent = $this->parentLocales[$locale];

            if ($parent !== 'root' && $parent !== 'und') {
                $candidates[] = [$parent, 'explicit-parent'];
            }
        }

        if (isset($this->defaultContent[$locale])) {
            $collapsed = $this->withoutRegion($parts);

            if ($collapsed !== $locale) {
                $candidates[] = [$collapsed, 'default-content-parent'];
            }
        }

        if ($region !== null) {
            $withoutRegion = $this->withoutRegion($parts);

            if ($withoutRegion !== $locale) {
                $candidates[] = [$withoutRegion, 'region-parent'];
            }
        }

        if ($script !== null) {
            $candidates[] = [$language, 'language-parent'];
        } elseif (count($parts) > 1) {
            $candidates[] = [$language, 'language-parent'];
        }

        return $this->uniqueCandidates($candidates);
    }

    private function maximizedLocale(
        string $locale,
        string $language,
        ?string $script,
        ?string $region
    ): ?string {
        $likely = $this->likelySubtags[$locale] ?? null;

        if ($likely === null && $region !== null) {
            $likely = $this->likelySubtags[$language.'-'.$region] ?? null;
        }

        $likely ??= $this->likelySubtags[$language] ?? null;

        if ($likely === null) {
            return null;
        }

        $likelyParts = explode('-', $likely);
        $likelyScript = $this->scriptPart($likelyParts);
        $likelyRegion = $this->regionPart($likelyParts);
        $result = [$language];

        if ($script ?? $likelyScript) {
            $result[] = $script ?? $likelyScript;
        }

        if ($region ?? $likelyRegion) {
            $result[] = $region ?? $likelyRegion;
        }

        return implode('-', $result);
    }

    /**
     * @param list<string> $parts
     */
    private function scriptPart(array $parts): ?string
    {
        foreach (array_slice($parts, 1) as $part) {
            if (strlen($part) === 4) {
                return $part;
            }
        }

        return null;
    }

    /**
     * @param list<string> $parts
     */
    private function regionPart(array $parts): ?string
    {
        foreach (array_slice($parts, 1) as $part) {
            if (strlen($part) === 2 || (strlen($part) === 3 && ctype_digit($part))) {
                return $part;
            }
        }

        return null;
    }

    /**
     * @param list<string> $parts
     */
    private function withoutRegion(array $parts): string
    {
        return implode('-', array_values(array_filter(
            $parts,
            fn (string $part, int $index): bool => $index === 0
                || !(strlen($part) === 2 || (strlen($part) === 3 && ctype_digit($part))),
            ARRAY_FILTER_USE_BOTH
        )));
    }

    /**
     * @param list<array{0: string, 1: string}> $candidates
     * @return list<array{0: string, 1: string}>
     */
    private function uniqueCandidates(array $candidates): array
    {
        $unique = [];

        foreach ($candidates as [$locale, $reason]) {
            $unique[$locale] ??= [$locale, $reason];
        }

        return array_values($unique);
    }
}
