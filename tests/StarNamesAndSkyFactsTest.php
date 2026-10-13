<?php

declare(strict_types=1);

namespace BirthStars\Tests;

use BirthStars\SkyFacts;
use BirthStars\Star;
use BirthStars\StarNames;
use PHPUnit\Framework\TestCase;

final class StarNamesAndSkyFactsTest extends TestCase
{
    private function star(?string $proper, ?string $bayer, ?int $flam, ?string $con, ?int $hip = 1): Star
    {
        return new Star(1, $hip, null, null, null, $proper, $bayer, $flam, $con, 0.0, 0.0, 10.0, 3.0, null);
    }

    public function testNames(): void
    {
        self::assertSame('Alpha¹ Centauri', StarNames::designation($this->star(null, 'Alp-1', null, 'Cen')));
        self::assertSame('61 Cygni', StarNames::designation($this->star(null, null, 61, 'Cyg')));
        self::assertSame('HIP 1', StarNames::designation($this->star(null, null, null, null)));
        self::assertSame('Sirius', StarNames::displayName($this->star('Sirius', 'Alp', 9, 'CMa')));
        self::assertSame('Ursa Major', StarNames::constellation('UMa'));
        self::assertCount(88, StarNames::CONSTELLATIONS);
    }

    public function testVisibilityFromHawaii(): void
    {
        $s = new SkyFacts();
        self::assertTrue($s->everRises(-60.8));   // Alpha Centauri rises from Hawaii
        self::assertFalse($s->everRises(-75.0));  // too far south
        self::assertEqualsWithDelta(20.5, $s->maxAltitude(89.26), 0.1); // Polaris ≈ latitude
        self::assertSame('north', $s->lookDirection(45.0));
        self::assertSame('February', $s->bestMonth(6.75)); // Sirius: winter evening star
        self::assertStringContainsString('brightest', SkyFacts::brightness(-1.44));
        self::assertStringContainsString('telescope', SkyFacts::brightness(11.0));
    }

    public function testAltitudeOfPolarisIsAboutLatitude(): void
    {
        $alt = (new SkyFacts())->altitudeAt(2.53, 89.26, new \DateTimeImmutable('2026-10-13T20:30:00-10:00'));
        self::assertEqualsWithDelta(19.7, $alt, 1.0);
    }
}
