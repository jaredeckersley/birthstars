<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Built-in server (php -S): let it serve static files directly.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

$config = ['debug' => getenv('APP_DEBUG') === '1'];
// APP_BASE_PATH overrides auto-detection (e.g. "/birthstars").
$override = getenv('APP_BASE_PATH');
$config['base_path'] = BirthStars\BasePath::detect($_SERVER, $override === false ? null : $override);

BirthStars\App::create($config)->run();
