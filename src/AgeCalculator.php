<?php

declare(strict_types=1);

namespace BirthStars;

use DateTimeImmutable;
use DateTimeZone;

/** Validates a birth date and turns it into an age in years (= light years travelled). */
final class AgeCalculator
{
    public const MAX_AGE_YEARS = 120;
    private const DAYS_PER_YEAR = 365.25;

    public function __construct(private readonly DateTimeZone $tz = new DateTimeZone('Pacific/Honolulu'))
    {
    }

    /**
     * @throws InvalidBirthDate
     */
    public function birthDate(mixed $year, mixed $month, mixed $day, ?DateTimeImmutable $today = null): DateTimeImmutable
    {
        foreach (['year' => $year, 'month' => $month, 'day' => $day] as $name => $v) {
            if (!is_string($v) && !is_int($v)) {
                throw new InvalidBirthDate("Please choose a $name.");
            }
            if (!preg_match('/^\d{1,4}$/', (string) $v)) {
                throw new InvalidBirthDate("That $name doesn't look right.");
            }
        }
        $y = (int) $year;
        $m = (int) $month;
        $d = (int) $day;
        if (!checkdate($m, $d, $y)) {
            throw new InvalidBirthDate("That day doesn't exist on the calendar. Try again!");
        }
        $today ??= new DateTimeImmutable('today', $this->tz);
        $birth = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $y, $m, $d), $this->tz);
        if ($birth > $today) {
            throw new InvalidBirthDate("That birthday is in the future! Are you a time traveller?");
        }
        if ($this->ageInYears($birth, $today) > self::MAX_AGE_YEARS) {
            throw new InvalidBirthDate('Please choose a birthday in the last ' . self::MAX_AGE_YEARS . ' years.');
        }

        return $birth;
    }

    public function ageInYears(DateTimeImmutable $birth, ?DateTimeImmutable $today = null): float
    {
        $today ??= new DateTimeImmutable('today', $this->tz);
        $days = (int) $birth->diff($today)->days;

        return $days / self::DAYS_PER_YEAR;
    }
}
