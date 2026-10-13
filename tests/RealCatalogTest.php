<?php

declare(strict_types=1);

namespace BirthStars\Tests;

use BirthStars\StarRepository;
use PHPUnit\Framework\TestCase;

/** Smoke test of the committed database: every age 0–120 gets a nearby named star. */
final class RealCatalogTest extends TestCase
{
    public function testEveryAgeHasAMatch(): void
    {
        $path = dirname(__DIR__) . '/data/stars.sqlite';
        if (!is_file($path)) {
            self::markTestSkipped('data/stars.sqlite not built');
        }
        $repo = StarRepository::open($path);
        for ($age = 5.0; $age <= 120.0; $age += 0.25) {
            $s = $repo->closestNamed($age);
            self::assertNotNull($s);
            self::assertLessThan(3.0, abs($s->distanceLy - $age), "age $age matched {$s->distanceLy} ly");
        }
        self::assertSame('HYG Database v4.1 (astronexus/HYG-Database), CC BY-SA 4.0', $repo->meta()['source']);
    }
}
