<?php

declare(strict_types=1);

namespace BirthStars;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\App as SlimApp;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

final class App
{
    /** Hawaiian subtitle (pending native-speaker review). Change here only. */
    public const SUBTITLE_HAW = 'Hōkū Hānau';

    /**
     * @param array{db?: string, debug?: bool, base_path?: string, now?: DateTimeImmutable, survey_dir?: string, subtitle_haw?: string} $config
     */
    public static function create(array $config = []): SlimApp
    {
        $root = dirname(__DIR__);
        $tz = new DateTimeZone('Pacific/Honolulu');
        $now = $config['now'] ?? new DateTimeImmutable('now', $tz);
        $repo = StarRepository::open($config['db'] ?? $root . '/data/stars.sqlite');
        $ages = new AgeCalculator($tz);
        $finder = new BirthdayStarFinder($repo, $ages);
        $sky = new SkyFacts();
        $hawaiian = new HawaiianNames($root . '/public/assets/data/hawaiian-star-names.json');
        $photos = new SurveyImages($config['survey_dir'] ?? $root . '/public/assets/survey');

        $app = AppFactory::create();
        if (isset($config['base_path'])) {
            $app->setBasePath($config['base_path']);
        }
        $twig = Twig::create($root . '/templates', ['cache' => false, 'autoescape' => 'html', 'strict_variables' => false]);
        $twig->getEnvironment()->addGlobal('base', $config['base_path'] ?? '');
        // Hawaiian subtitle shown under the title. TODO: needs review by a native speaker before launch.
        $twig->getEnvironment()->addGlobal('subtitle_haw', $config['subtitle_haw'] ?? self::SUBTITLE_HAW);
        $app->add(TwigMiddleware::create($app, $twig));
        $app->addRoutingMiddleware();

        // Security headers: everything is served locally, so lock the page down to 'self'.
        $app->add(static function (Request $req, Handler $h): Response {
            return $h->handle($req)
                ->withHeader('Content-Security-Policy', "default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; connect-src 'self'; frame-ancestors 'self'; base-uri 'none'; form-action 'self'")
                ->withHeader('X-Content-Type-Options', 'nosniff')
                ->withHeader('Referrer-Policy', 'no-referrer')
                ->withHeader('Permissions-Policy', 'geolocation=(), camera=(), microphone=()');
        });

        $errors = $app->addErrorMiddleware((bool) ($config['debug'] ?? false), true, true);
        $errors->setErrorHandler(HttpNotFoundException::class, static function (Request $req) use ($app, $twig): Response {
            return $twig->render($app->getResponseFactory()->createResponse(404), 'notfound.twig', ['kiosk' => false]);
        });

        $kiosk = static fn (Request $r): bool => ($r->getQueryParams()['kiosk'] ?? '') === '1';
        $meta = $repo->meta();

        $home = static function (Request $req, Response $res, bool $isKiosk, ?string $error = null, int $status = 200) use ($twig, $now) {
            $q = $req->getQueryParams();
            $pick = static fn (string $k) => is_string($q[$k] ?? null) && ctype_digit($q[$k]) ? (int) $q[$k] : null;

            return $twig->render($res->withStatus($status), 'home.twig', [
                'kiosk' => $isKiosk,
                'error' => $error,
                'max_year' => (int) $now->format('Y'),
                'min_year' => (int) $now->format('Y') - AgeCalculator::MAX_AGE_YEARS,
                'sel' => ['year' => $pick('year'), 'month' => $pick('month'), 'day' => $pick('day')],
            ]);
        };

        $app->get('/', static fn (Request $q, Response $r) => $home($q, $r, $kiosk($q)));
        $app->get('/kiosk', static fn (Request $q, Response $r) => $home($q, $r, true));

        $app->get('/star', static function (Request $req, Response $res) use ($twig, $ages, $finder, $sky, $photos, $hawaiian, $now, $kiosk, $home, $meta) {
            $q = $req->getQueryParams();
            try {
                $birth = $ages->birthDate($q['year'] ?? null, $q['month'] ?? null, $q['day'] ?? null, $now);
            } catch (InvalidBirthDate $e) {
                return $home($req, $res, $kiosk($req), $e->getMessage(), 422);
            }
            $result = $finder->find($birth, $now);
            $star = $result->star;

            return $twig->render($res, 'result.twig', [
                'kiosk' => $kiosk($req),
                'r' => $result,
                'star' => $star,
                'name' => StarNames::displayName($star),
                'designation' => StarNames::designation($star),
                'constellation' => StarNames::constellation($star->constellation),
                'constellation_meaning' => StarNames::constellationMeaning($star->constellation),
                'facts' => $sky->describe($star, $now),
                'age_years' => $result->ageYears,
                'light_speed_miles' => 186282,
                'miles' => $star->distanceLy * 5.878625e12,
                'source' => $meta['source'] ?? 'HYG Database',
                'star_count' => (int) ($meta['star_count'] ?? 0),
                'photo' => $photos->forStar($star->hygId),
                'hawaiian' => $hawaiian->forHip($star->hip),
            ]);
        });

        $app->get('/about', static fn (Request $q, Response $r) => $twig->render($r, 'about.twig', ['kiosk' => $kiosk($q), 'meta' => $meta]));

        return $app;
    }
}
