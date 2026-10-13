<?php

declare(strict_types=1);

namespace BirthStars\Tests;

use BirthStars\HawaiianNames;
use BirthStars\StarNames;
use PHPUnit\Framework\TestCase;

final class HawaiianNamesAndChartDataTest extends TestCase
{
    private const DATA = __DIR__ . '/../public/assets/data/';

    public function testHawaiianNamesHaveSourcesAndCorrectSpelling(): void
    {
        $h = new HawaiianNames(self::DATA . 'hawaiian-star-names.json');
        self::assertSame('Hōkūleʻa', $h->forHip(69673)['hawaiian']);   // Arcturus
        self::assertSame('Hōkūpaʻa', $h->forHip(11767)['hawaiian']);   // Polaris
        self::assertSame('ʻAʻā', $h->forHip(32349)['hawaiian']);       // Sirius
        self::assertNull($h->forHip(1));
        self::assertNull($h->forHip(null));
        foreach ($h->all() as $n) {
            self::assertNotEmpty($n['sources']);
            foreach ($n['sources'] as $src) {
                self::assertMatchesRegularExpression('#^https://#', $src);
            }
            // ʻokina must be U+02BB, never an apostrophe or quote.
            self::assertStringNotContainsString("'", $n['hawaiian']);
            self::assertStringNotContainsString('‘', $n['hawaiian']);
            self::assertIsFloat($n['ra_deg'] + 0.0);
        }
    }

    public function testAll88ConstellationsHaveLabelsAndLines(): void
    {
        $names = json_decode((string) file_get_contents(self::DATA . 'constellations.names.json'), true);
        $ids = array_unique(array_column($names['constellations'], 0));
        sort($ids);
        $expected = array_keys(StarNames::CONSTELLATIONS);
        sort($expected);
        self::assertSame($expected, $ids);
        foreach ($names['constellations'] as [$id, $name, $ra, $dec, $rank]) {
            self::assertNotSame('', $name);
            self::assertTrue($ra >= 0 && $ra < 360 && $dec >= -90 && $dec <= 90, $id);
            self::assertContains($rank, [1, 2, 3]);
        }
        $lines = json_decode((string) file_get_contents(self::DATA . 'constellations.lines.json'), true);
        self::assertCount(89, $lines['features']); // 88 + Serpens in two parts
    }
}
