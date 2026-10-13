<?php

declare(strict_types=1);

namespace BirthStars\Tests;

use BirthStars\HygImporter;

trait FixtureDb
{
    private string $tmpDir;

    protected function buildFixtureDb(): string
    {
        $this->tmpDir = sys_get_temp_dir() . '/bstest-' . bin2hex(random_bytes(4));
        mkdir($this->tmpDir);
        $db = $this->tmpDir . '/stars.sqlite';
        (new HygImporter())->import(__DIR__ . '/fixtures/hyg-sample.csv', $db, $this->tmpDir . '/sky.json', ['source' => 'fixture']);

        return $db;
    }

    protected function tearDown(): void
    {
        if (isset($this->tmpDir) && is_dir($this->tmpDir)) {
            array_map('unlink', glob($this->tmpDir . '/*') ?: []);
            rmdir($this->tmpDir);
        }
    }
}
