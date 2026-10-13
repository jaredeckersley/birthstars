<?php

declare(strict_types=1);

namespace BirthStars\Tests;

use BirthStars\ImageQuality;
use PHPUnit\Framework\TestCase;

final class ImageQualityTest extends TestCase
{
    protected function setUp(): void
    {
        if (!ImageQuality::available()) {
            self::markTestSkipped('ext-gd not installed');
        }
    }

    /** @param callable(\GdImage|resource): void $paint */
    private static function jpeg(callable $paint, int $bg = 0x050505): string
    {
        $im = imagecreatetruecolor(600, 600);
        imagefilledrectangle($im, 0, 0, 599, 599, $bg);
        $paint($im);
        ob_start();
        imagejpeg($im, null, 85);
        imagedestroy($im);

        return (string) ob_get_clean();
    }

    private static function starfield($im, int $seed = 1): void
    {
        mt_srand($seed);
        for ($i = 0; $i < 400; $i++) {
            imagefilledellipse($im, mt_rand(0, 599), mt_rand(0, 599), 4, 4, 0xDDDDFF);
        }
    }

    public function testMaskedCoreInsideBrightHaloIsDetected(): void
    {
        $jpeg = self::jpeg(static function ($im): void {
            self::starfield($im);
            imagefilledellipse($im, 300, 300, 160, 160, 0x8899FF); // glow
            imagefilledellipse($im, 300, 300, 90, 90, 0xFFFFFF);   // saturated halo
            imagefilledellipse($im, 302, 298, 22, 22, 0x000000);   // NaN core -> black
        });
        $r = ImageQuality::assess($jpeg);
        self::assertContains('dark_core', $r['issues']);
        self::assertGreaterThanOrEqual(ImageQuality::HOLE_MIN, $r['metrics']['core_hole_pixels']);
    }

    public function testColouredMaskedCoreInBlueHaloIsDetected(): void
    {
        $jpeg = self::jpeg(static function ($im): void {
            imagefilledrectangle($im, 0, 0, 599, 599, 0x1030C0);  // whole frame washed blue
            imagefilledellipse($im, 300, 300, 200, 200, 0x3060FF);
            imagefilledellipse($im, 300, 300, 26, 18, 0x000010);  // black hole in the blue
        });
        self::assertContains('dark_core', ImageQuality::assess($jpeg)['issues']);
    }

    public function testNormalBrightStarIsFine(): void
    {
        $jpeg = self::jpeg(static function ($im): void {
            self::starfield($im, 2);
            imagefilledellipse($im, 300, 300, 120, 120, 0x6677CC);
            imagefilledellipse($im, 300, 300, 70, 70, 0xFFFFFF);  // solid white centre: no hole
            imageline($im, 200, 300, 400, 300, 0xFFFFFF);         // diffraction spikes
            imageline($im, 300, 200, 300, 400, 0xFFFFFF);
        });
        self::assertSame([], ImageQuality::assess($jpeg)['issues']);
    }

    public function testFaintStarOnDarkSkyIsFine(): void
    {
        $jpeg = self::jpeg(static function ($im): void {
            self::starfield($im, 3);
            imagefilledellipse($im, 300, 300, 8, 8, 0xFFDDAA);
        });
        self::assertSame([], ImageQuality::assess($jpeg)['issues']);
    }

    public function testBlankFrame(): void
    {
        $r = ImageQuality::assess(self::jpeg(static function ($im): void {
        }, 0x000000));
        self::assertContains('near_blank', $r['issues']);
    }

    public function testLargeZeroRegion(): void
    {
        $jpeg = self::jpeg(static function ($im): void {
            imagefilledrectangle($im, 0, 0, 599, 599, 0x181820);  // normal sky background
            self::starfield($im, 4);
            imagefilledrectangle($im, 0, 0, 599, 179, 0x000000);  // top 30% missing data
        }, 0x181820);
        self::assertContains('zero_regions', ImageQuality::assess($jpeg)['issues']);
    }

    public function testUnreadable(): void
    {
        self::assertSame(['unreadable'], ImageQuality::assess('not a jpeg')['issues']);
    }
}
