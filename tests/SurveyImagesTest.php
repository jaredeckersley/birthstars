<?php

declare(strict_types=1);

namespace BirthStars\Tests;

use BirthStars\SurveyImages;
use PHPUnit\Framework\TestCase;

final class SurveyImagesTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/bssurvey-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        rmdir($this->dir);
    }

    public function testSurveyChoice(): void
    {
        self::assertSame('CDS/P/PanSTARRS/DR1/color-z-zg-g', SurveyImages::surveyFor(38.7));
        self::assertSame('CDS/P/PanSTARRS/DR1/color-z-zg-g', SurveyImages::surveyFor(-16.7));
        self::assertSame('CDS/P/DSS2/color', SurveyImages::surveyFor(-60.8));
    }

    public function testFallbackOrder(): void
    {
        self::assertSame(['CDS/P/PanSTARRS/DR1/color-z-zg-g', 'CDS/P/DSS2/color', 'CDS/P/2MASS/color'], SurveyImages::candidatesFor(10.0));
        self::assertSame(['CDS/P/DSS2/color', 'CDS/P/2MASS/color'], SurveyImages::candidatesFor(-50.0));
    }

    public function testFieldOfViewGrowsForBrightStars(): void
    {
        self::assertSame(1.0, SurveyImages::fovFor(-1.44));
        self::assertGreaterThan(SurveyImages::fovFor(5.2), SurveyImages::fovFor(2.5));
        self::assertSame(0.25, SurveyImages::fovFor(11.0));
    }

    public function testProperMotionShiftsPosition(): void
    {
        // Barnard's Star: ~10.3"/yr north. Over 12 years that is ~0.034° in Dec.
        [$ra, $dec] = SurveyImages::positionAt(269.452, 4.6933, -797.84, 10326.93, 2012.0);
        self::assertEqualsWithDelta(4.6933 + 10326.93 * 12 / 3.6e6, $dec, 1e-6);
        self::assertLessThan(269.452, $ra);
        [$ra2, $dec2] = SurveyImages::positionAt(10.0, 20.0, null, null, 1993.0);
        self::assertSame([10.0, 20.0], [$ra2, $dec2]);
        [$ra3] = SurveyImages::positionAt(359.9999, 0.0, 1000.0, 0.0, 2012.0);
        self::assertLessThan(1.0, $ra3); // wraps around 360°
    }

    public function testUrl(): void
    {
        $u = SurveyImages::url('CDS/P/DSS2/color', 10.5, -20.25, 0.5);
        self::assertStringStartsWith(SurveyImages::ENDPOINT . '?', $u);
        parse_str((string) parse_url($u, PHP_URL_QUERY), $q);
        self::assertSame(['CDS/P/DSS2/color', '600', '0.5', '10.5', '-20.25', 'jpg'], [$q['hips'], $q['width'], $q['fov'], $q['ra'], $q['dec'], $q['format']]);
    }

    public function testFovInKidWords(): void
    {
        self::assertSame('about half the width of the full Moon', SurveyImages::fovInWords(0.25));
        self::assertSame('about three-quarters of the width of the full Moon', SurveyImages::fovInWords(0.35));
        self::assertSame('about as wide as the full Moon', SurveyImages::fovInWords(0.5));
        self::assertSame('about 2 full Moons wide', SurveyImages::fovInWords(1.0));
    }

    public function testForStarNeedsManifestEntryAndFile(): void
    {
        file_put_contents($this->dir . '/manifest.json', json_encode(['images' => [
            '5' => ['hips' => 'CDS/P/PanSTARRS/DR1/color-z-zg-g', 'fov_deg' => 0.35],
            '6' => ['hips' => 'CDS/P/DSS2/color', 'fov_deg' => 1.0],
        ]]));
        file_put_contents($this->dir . '/5.jpg', "\xFF\xD8fake");
        $s = new SurveyImages($this->dir);
        $p = $s->forStar(5);
        self::assertNotNull($p);
        self::assertSame('5.jpg', $p['file']);
        self::assertTrue($p['pan_starrs']);
        self::assertStringContainsString('Haleakalā', $p['credit']);
        self::assertNull($s->forStar(6), 'file missing');
        self::assertNull($s->forStar(7), 'not in manifest');
        self::assertNull((new SurveyImages($this->dir . '/nope'))->forStar(5));
    }
}
