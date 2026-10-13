<?php

declare(strict_types=1);

namespace BirthStars;

/** One star from the catalog, as stored in the SQLite database. */
/** @psalm-immutable Properties are treated as read-only (PHP 8.0 has no readonly). */
final class Star
{
    public const PARSEC_IN_LY = 3.261563777;

    public function __construct(
        public int $hygId,
        public ?int $hip,
        public ?int $hr,
        public ?int $hd,
        public ?string $gliese,
        public ?string $proper,
        public ?string $bayer,
        public ?int $flamsteed,
        public ?string $constellation,
        public float $raHours,
        public float $decDeg,
        public float $distanceLy,
        public float $magnitude,
        public ?string $spectralType,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        $int = static fn ($v) => $v === null || $v === '' ? null : (int) $v;
        $str = static fn ($v) => $v === null || $v === '' ? null : (string) $v;

        return new self(
            (int) $row['hyg_id'],
            $int($row['hip']),
            $int($row['hr']),
            $int($row['hd']),
            $str($row['gl']),
            $str($row['proper']),
            $str($row['bayer']),
            $int($row['flam']),
            $str($row['con']),
            (float) $row['ra'],
            (float) $row['dec'],
            (float) $row['dist_ly'],
            (float) $row['mag'],
            $str($row['spect']),
        );
    }
}
