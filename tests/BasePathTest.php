<?php

declare(strict_types=1);

namespace BirthStars\Tests;

use BirthStars\BasePath;
use PHPUnit\Framework\TestCase;

final class BasePathTest extends TestCase
{
    public function testSubfolderViaRootHtaccess(): void
    {
        self::assertSame('/birthstars', BasePath::detect(['SCRIPT_NAME' => '/birthstars/public/index.php']));
    }

    public function testDocumentRootIsPublic(): void
    {
        self::assertSame('', BasePath::detect(['SCRIPT_NAME' => '/index.php']));
    }

    public function testDocrootIsRepoRoot(): void
    {
        self::assertSame('', BasePath::detect(['SCRIPT_NAME' => '/public/index.php']));
    }

    public function testNestedSubfolder(): void
    {
        self::assertSame('/outreach/birthstars', BasePath::detect(['SCRIPT_NAME' => '/outreach/birthstars/public/index.php']));
    }

    public function testOverrideWins(): void
    {
        self::assertSame('/stars', BasePath::detect(['SCRIPT_NAME' => '/birthstars/public/index.php'], '/stars/'));
    }
}
