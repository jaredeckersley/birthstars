<?php

declare(strict_types=1);

namespace BirthStars;

/** One star from the catalog, as stored in the SQLite database. */
final class Star
{
    public const PARSEC_IN_LY = 3.261563777;

    public function __construct(
        public readonly int $hygId,
        public readonly ?int $hip,
        public readonly ?int $hr,
        public readonly ?int $hd,
        public readonly ?string $gliese,
        public readonly ?string $proper,
        public readonly ?string $bayer,
        public readonly ?int $flamsteed,
        public readonly ?string $constellation,
        public readonly float $raHours,
        public readonly float $decDeg,
        public readonly float $distanceLy,
        public readonly float $magnitude,
        public readonly ?string $spectralType,
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
