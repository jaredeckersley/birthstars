<?php

declare(strict_types=1);

namespace BirthStars\Tests;

use BirthStars\AgeCalculator;
use BirthStars\BirthdayStarFinder;
use BirthStars\StarRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class BirthdayStarFinderTest extends TestCase
{
    use FixtureDb;

    private function finder(): BirthdayStarFinder
    {
        return new BirthdayStarFinder(StarRepository::open($this->buildFixtureDb()), new AgeCalculator());
    }

    private function d(string $s): DateTimeImmutable
    {
        return new DateTimeImmutable($s, new DateTimeZone('Pacific/Honolulu'));
    }

    public function testElevenYearOldGets61Cygni(): void
    {
        $r = $this->finder()->find($this->d('2015-06-01'), $this->d('2026-10-13'));
        self::assertSame(104214, $r->star->hip);
        self::assertEqualsWithDelta(11.37, $r->ageYears, 0.01);
        self::assertFalse($r->isTooYoungForAnyStar());
    }

    public function testNineYearOldGetsSiriusNotTheUnnamedStar(): void
    {
        // The unnamed star at 9.78 ly is the closest, but named stars are preferred.
        $r = $this->finder()->find($this->d('2017-01-01'), $this->d('2026-10-13'));
        self::assertSame('Sirius', $r->star->proper);
    }

    public function testBabyGetsNearestStarAndIsFlaggedTooYoung(): void
    {
        $r = $this->finder()->find($this->d('2026-01-01'), $this->d('2026-10-13'));
        self::assertSame('Rigil Kentaurus', $r->star->proper);
        self::assertTrue($r->isTooYoungForAnyStar());
    }

    public function testVeryOldPersonGetsFarthestStar(): void
    {
        $r = $this->finder()->find($this->d('1910-01-01'), $this->d('2026-10-13'));
        self::assertSame('Polaris', $r->star->proper);
    }

    public function testLightLeftRoughlyAtBirth(): void
    {
        $r = $this->finder()->find($this->d('2015-06-01'), $this->d('2026-10-13'));
        self::assertSame('2015', $r->lightLeftOn()->format('Y'));
    }
}
