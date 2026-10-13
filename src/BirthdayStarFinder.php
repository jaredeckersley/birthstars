<?php

declare(strict_types=1);

namespace BirthStars;

use DateTimeImmutable;

/** Finds "your birthday star": the named star whose light set off when you were born. */
final class BirthdayStarFinder
{
    public function __construct(
        private readonly StarRepository $stars,
        private readonly AgeCalculator $ages,
    ) {
    }

    public function find(DateTimeImmutable $birth, ?DateTimeImmutable $today = null): BirthdayStar
    {
        $ageYears = $this->ages->ageInYears($birth, $today);
        $star = $this->stars->closestNamed($ageYears);
        if ($star === null) {
            throw new \RuntimeException('The star database is empty.');
        }

        return new BirthdayStar($star, $ageYears, $birth, $this->stars->countCloserThan($ageYears));
    }
}
