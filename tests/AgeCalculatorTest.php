<?php

declare(strict_types=1);

namespace BirthStars\Tests;

use BirthStars\AgeCalculator;
use BirthStars\InvalidBirthDate;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AgeCalculatorTest extends TestCase
{
    private function today(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-13', new \DateTimeZone('Pacific/Honolulu'));
    }

    public function testValidDate(): void
    {
        $c = new AgeCalculator();
        $b = $c->birthDate('2016', '10', '13', $this->today());
        self::assertSame('2016-10-13', $b->format('Y-m-d'));
        self::assertEqualsWithDelta(10.0, $c->ageInYears($b, $this->today()), 0.01);
    }

    public function testLeapDayIsAccepted(): void
    {
        self::assertSame('2020-02-29', (new AgeCalculator())->birthDate('2020', '2', '29', $this->today())->format('Y-m-d'));
    }

    /** @return iterable<string, array{mixed, mixed, mixed}> */
    public static function badDates(): iterable
    {
        yield 'future' => ['2027', '1', '1'];
        yield 'not a day' => ['2019', '2', '29'];
        yield 'month 13' => ['2000', '13', '1'];
        yield 'letters' => ['20x0', '1', '1'];
        yield 'injection' => ["2000'; DROP TABLE stars;--", '1', '1'];
        yield 'array' => [['2000'], '1', '1'];
        yield 'missing' => [null, '1', '1'];
        yield 'too old' => ['1900', '1', '1'];
        yield 'negative' => ['-5', '1', '1'];
    }

    /**
     * @dataProvider badDates
     */
    public function testRejectsBadInput(mixed $y, mixed $m, mixed $d): void
    {
        $this->expectException(InvalidBirthDate::class);
        (new AgeCalculator())->birthDate($y, $m, $d, $this->today());
    }

    public function testBornTodayIsZero(): void
    {
        $c = new AgeCalculator();
        self::assertSame(0.0, $c->ageInYears($c->birthDate('2026', '10', '13', $this->today()), $this->today()));
    }
}
