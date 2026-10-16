<?php

declare(strict_types=1);

namespace BirthStars\Tests;

use BirthStars\App;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class AppTest extends TestCase
{
    use FixtureDb;

    /** @return array{int, string, array<string, string[]>} */
    private function get(string $uri, ?string $surveyDir = null): array
    {
        $app = App::create([
            'db' => $this->buildFixtureDb(),
            'now' => new \DateTimeImmutable('2026-10-13T12:00:00-10:00'),
            'survey_dir' => $surveyDir ?? $this->tmpDir . '/no-survey',
        ]);
        $res = $app->handle((new ServerRequestFactory())->createServerRequest('GET', $uri)->withQueryParams(self::query($uri)));

        return [$res->getStatusCode(), (string) $res->getBody(), $res->getHeaders()];
    }

    /** @return array<string, string> */
    private static function query(string $uri): array
    {
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $q);

        return $q;
    }

    public function testHome(): void
    {
        [$code, $body, $headers] = $this->get('/');
        self::assertSame(200, $code);
        self::assertStringContainsString('What&#39;s your birthday star right now?', $body);
        self::assertStringContainsString('left it around when you were born', $body);
        self::assertStringContainsString('around the time you were born', $body);
        self::assertStringContainsString('lang="haw">Hōkū Hānau<', $body);
        self::assertStringContainsString("default-src 'self'", $headers['Content-Security-Policy'][0]);
        self::assertStringNotContainsString('data-kiosk-home', $body);
    }

    public function testKiosk(): void
    {
        [$code, $body] = $this->get('/kiosk');
        self::assertSame(200, $code);
        self::assertStringContainsString('data-kiosk-home="/kiosk"', $body);
        self::assertStringContainsString('name="kiosk" value="1"', $body);
    }

    public function testResult(): void
    {
        [$code, $body] = $this->get('/star?year=2015&month=6&day=1');
        self::assertSame(200, $code);
        self::assertStringContainsString('61 Cygni', $body);
        self::assertStringContainsString('Cygnus', $body);
        self::assertStringContainsString('id="skychart"', $body);
        self::assertStringContainsString('The light you see left it around when you were born', $body);
        self::assertStringContainsString('But the trip is about', $body);
        self::assertStringContainsString('trillion miles</strong> long!', $body);
        self::assertStringNotContainsString('Even at that speed', $body);
    }

    public function testInvalidInputIsRejectedAndEscaped(): void
    {
        [$code, $body] = $this->get('/star?year=%3Cscript%3E&month=1&day=1');
        self::assertSame(422, $code);
        self::assertStringNotContainsString('<script>', $body);
        self::assertStringContainsString('role="alert"', $body);
    }

    public function testNotFound(): void
    {
        [$code, $body] = $this->get('/no-such-page');
        self::assertSame(404, $code);
        self::assertStringContainsString('Lost in space', $body);
    }

    public function testResultShowsTelescopePhotoWhenAvailable(): void
    {
        $this->buildFixtureDb();
        $dir = $this->tmpDir . '/survey';
        mkdir($dir);
        // 61 Cyg is HYG id 2 in the fixture.
        file_put_contents($dir . '/manifest.json', json_encode(['images' => ['2' => ['hips' => 'CDS/P/PanSTARRS/DR1/color-z-zg-g', 'fov_deg' => 0.35]]]));
        file_put_contents($dir . '/2.jpg', "\xFF\xD8");
        [$code, $body] = $this->get('/star?year=2015&month=6&day=1', $dir);
        array_map('unlink', glob($dir . '/*') ?: []);
        rmdir($dir);
        self::assertSame(200, $code);
        self::assertStringContainsString('photographed by a real telescope', $body);
        self::assertStringContainsString('/assets/survey/2.jpg', $body);
        self::assertStringContainsString('Haleakalā', $body);
        self::assertStringContainsString('three-quarters of the width of the full Moon', $body);
        // The marker must be a transparent ring even if the stylesheet fails to load.
        self::assertMatchesRegularExpression('#<g class="photo-ring" fill="none"#', $body);
        self::assertStringNotContainsString('fill="#', $body);
        self::assertMatchesRegularExpression('#app\.css\?v=[0-9a-f]{10}#', $body);
    }

    public function testResultFallsBackWithoutPhoto(): void
    {
        [$code, $body] = $this->get('/star?year=2015&month=6&day=1');
        self::assertSame(200, $code);
        self::assertStringContainsString("don't have a telescope photo", $body);
        self::assertStringNotContainsString('/assets/survey/', $body);
    }

    public function testHawaiianNameShownForSirius(): void
    {
        [$code, $body] = $this->get('/star?year=2017&month=1&day=1');
        self::assertSame(200, $code);
        self::assertStringContainsString('<strong lang="haw">ʻAʻā</strong>', $body);
        self::assertStringContainsString('hawaiian-star-names.json', $body);
    }
}
