<?php

declare(strict_types=1);

namespace BirthStars;

/**
 * Hawaiian star names with published sources (public/assets/data/hawaiian-star-names.json).
 * The same file feeds the result page and the sky chart labels. Pending native-speaker review.
 */
final class HawaiianNames
{
    /** @var array<int, array<string, mixed>> */
    private array $byHip = [];

    public function __construct(string $jsonFile)
    {
        $data = is_file($jsonFile) ? json_decode((string) file_get_contents($jsonFile), true) : null;
        foreach (is_array($data) ? ($data['names'] ?? []) : [] as $n) {
            if (is_array($n) && isset($n['hip'], $n['hawaiian']) && !empty($n['sources'])) {
                $this->byHip[(int) $n['hip']] = $n;
            }
        }
    }

    /** @return array<string, mixed>|null */
    public function forHip(?int $hip): ?array
    {
        return $hip !== null ? ($this->byHip[$hip] ?? null) : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->byHip;
    }
}
