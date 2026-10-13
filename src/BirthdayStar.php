<?php

declare(strict_types=1);

namespace BirthStars;

use DateTimeImmutable;

final class BirthdayStar
{
    public function __construct(
        public readonly Star $star,
        public readonly float $ageYears,
        public readonly DateTimeImmutable $birth,
        public readonly int $starsReached,
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
