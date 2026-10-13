<?php

declare(strict_types=1);

namespace BirthStars\Tests;

use BirthStars\HygImporter;
use PDO;
use PHPUnit\Framework\TestCase;

final class HygImporterTest extends TestCase
{
    use FixtureDb;

    public function testImportsOnlyUsableStarsWithinRange(): void
    {
        $db = $this->buildFixtureDb();
        $pdo = new PDO('sqlite:' . $db);
        $names = $pdo->query('SELECT COALESCE(proper, CAST(hip AS TEXT)) FROM stars ORDER BY hyg_id')->fetchAll(PDO::FETCH_COLUMN);

        // Sol, unknown distance (100000 pc), no magnitude and >135 ly are dropped.
        self::assertSame(['Rigil Kentaurus', '104214', 'Sirius', '99999', 'Polaris', 'Procyon'], $names);
    }

    public function testConvertsParsecsToLightYearsAndFlagsNamedStars(): void
    {
        $pdo = new PDO('sqlite:' . $this->buildFixtureDb());
        $row = $pdo->query('SELECT dist_ly, named, bayer, flam, con FROM stars WHERE hip = 104214')->fetch(PDO::FETCH_ASSOC);
        self::assertEqualsWithDelta(11.404, $row['dist_ly'], 0.01);
        self::assertSame(1, (int) $row['named']);
        self::assertSame(61, (int) $row['flam']);
        self::assertSame('Cyg', $row['con']);
        self::assertSame(0, (int) $pdo->query('SELECT named FROM stars WHERE hip = 99999')->fetchColumn());
    }

    public function testRecordsProvenanceMeta(): void
    {
        $pdo = new PDO('sqlite:' . $this->buildFixtureDb());
        $meta = $pdo->query('SELECT key, value FROM meta')->fetchAll(PDO::FETCH_KEY_PAIR);
        self::assertSame('fixture', $meta['source']);
        self::assertSame('6', $meta['star_count']);
        self::assertSame('5', $meta['named_count']);
        self::assertArrayHasKey('imported_at', $meta);
    }

    public function testWritesSkyChartJsonOfBrightStars(): void
    {
        $this->buildFixtureDb();
        $json = json_decode((string) file_get_contents($this->tmpDir . '/sky.json'), true);
        // mag <= 5.5 regardless of distance (Faraway & TooFar included; Sol and faint stars excluded), brightest first.
        self::assertCount(7, $json['stars']);
        self::assertSame(-1.44, $json['stars'][0][2]);
        self::assertEqualsWithDelta(6.75 * 15, $json['stars'][0][0], 0.001);
    }

    public function testRejectsNonHygFileAndKeepsExistingDatabase(): void
    {
        $db = $this->buildFixtureDb();
        $before = hash_file('sha256', $db);
        $bad = $this->tmpDir . '/bad.csv';
        file_put_contents($bad, "a,b,c\n1,2,3\n");
        try {
            (new HygImporter())->import($bad, $db, null);
            self::fail('expected exception');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('missing columns', $e->getMessage());
        }
        self::assertSame($before, hash_file('sha256', $db));
    }
}
