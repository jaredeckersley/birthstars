<?php

declare(strict_types=1);

namespace BirthStars;

/**
 * Works out the URL prefix the app is served under, e.g. "/birthstars" when the repo is
 * cloned into /var/www/html/birthstars and the root .htaccess forwards requests to public/.
 */
final class BasePath
{
    /** @param array<string, mixed> $server $_SERVER */
    public static function detect(array $server, ?string $override = null): string
    {
        if ($override !== null && $override !== '') {
            return self::clean($override);
        }
        $script = (string) ($server['SCRIPT_NAME'] ?? '');
        if ($script === '' || PHP_SAPI === 'cli-server') {
            return '';
        }
        $dir = str_replace('\\', '/', dirname($script));   // e.g. /birthstars/public
        if (substr($dir, -7) === '/public') {
            $dir = substr($dir, 0, -7);                      // repo root is the web-visible folder
        }

        return self::clean($dir);
    }

    private static function clean(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '' : $path;
    }
}
