<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Built-in server (php -S): let it serve static files directly.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

$config = ['debug' => getenv('APP_DEBUG') === '1'];
if (($base = getenv('APP_BASE_PATH')) !== false && $base !== '') {
    $config['base_path'] = rtrim($base, '/');
}

BirthStars\App::create($config)->run();
