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
    private function get(string $uri): array
    {
        $app = App::create(['db' => $this->buildFixtureDb(), 'now' => new \DateTimeImmutable('2026-10-13T12:00:00-10:00')]);
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
        self::assertStringContainsString('Which star was born', $body);
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
}
