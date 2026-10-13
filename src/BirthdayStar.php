<?php

declare(strict_types=1);

namespace BirthStars;

use DateTimeImmutable;

/** @psalm-immutable Properties are treated as read-only (PHP 8.0 has no readonly). */
final class BirthdayStar
{
    public function __construct(
        public Star $star,
        public float $ageYears,
        public DateTimeImmutable $birth,
        public int $starsReached,
    ) {
    }

    /** Light from stars closer than ~4.2 ly reaches us in less time than the child has been alive. */
    public function isTooYoungForAnyStar(): bool
    {
        return $this->ageYears < $this->star->distanceLy - 1.0;
    }

    /** When the light we see today left the star (birth date shifted by the distance/age difference). */
    public function lightLeftOn(): DateTimeImmutable
    {
        $now = $this->birth->add(new \DateInterval('P' . (int) round($this->ageYears * 365.25) . 'D'));
        $days = (int) round($this->star->distanceLy * 365.25);

        return $now->sub(new \DateInterval('P' . $days . 'D'));
    }
}
