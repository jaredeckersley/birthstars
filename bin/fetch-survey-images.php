#!/usr/bin/env php
<?php

/**
 * Download real sky-survey photos of every possible birthday star (named stars in data/stars.sqlite)
 * from CDS hips2fits into public/assets/survey/, and update public/assets/survey/manifest.json.
 *
 *   php bin/fetch-survey-images.php            # fetch missing images (safe to re-run / resume)
 *   php bin/fetch-survey-images.php --force    # re-download everything
 *   php bin/fetch-survey-images.php --only=123 # one star (HYG id)
 *   options: --delay=1.0 (seconds between requests) --limit=N --prune (delete images of stars no longer in the DB)
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use BirthStars\SurveyImages;

$root = dirname(__DIR__);
$dir = $root . '/public/assets/survey';
$opts = getopt('', ['force', 'only:', 'delay:', 'limit:', 'prune', 'quality:']);
$delay = isset($opts['delay']) ? max(0.2, (float) $opts['delay']) : 1.0;
$limit = isset($opts['limit']) ? (int) $opts['limit'] : PHP_INT_MAX;
$quality = isset($opts['quality']) ? (int) $opts['quality'] : 80;
$force = isset($opts['force']);

if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
    fwrite(STDERR, "Cannot create $dir\n");
    exit(1);
}
$manifestFile = $dir . '/manifest.json';
$manifest = is_file($manifestFile) ? (json_decode((string) file_get_contents($manifestFile), true) ?: []) : [];
$images = $manifest['images'] ?? [];

$pdo = new PDO('sqlite:' . $root . '/data/stars.sqlite', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$sql = 'SELECT hyg_id, proper, ra, dec, mag, pmra, pmdec FROM stars WHERE named = 1';
if (isset($opts['only'])) {
    $sql .= ' AND hyg_id = ' . (int) $opts['only'];
}
$stars = $pdo->query($sql . ' ORDER BY dist_ly')->fetchAll(PDO::FETCH_ASSOC);

$save = static function () use (&$images, $manifestFile): void {
    ksort($images, SORT_NUMERIC);
    $data = [
        'generated_by' => 'bin/fetch-survey-images.php',
        'service' => SurveyImages::ENDPOINT,
        'size_px' => SurveyImages::SIZE_PX,
        'updated_at' => gmdate('c'),
        'images' => $images,
    ];
    file_put_contents($manifestFile . '.tmp', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    rename($manifestFile . '.tmp', $manifestFile);
};

/** @return string|null JPEG bytes */
$download = static function (string $url) use ($delay): ?string {
    $ctx = stream_context_create(['http' => [
        'timeout' => 90,
        'ignore_errors' => true,
        'user_agent' => 'BirthdayStars/1.0 (Journey Through the Universe outreach; offline image cache)',
    ]]);
    for ($attempt = 1; $attempt <= 4; $attempt++) {
        $body = @file_get_contents($url, false, $ctx);
        $status = 0;
        foreach ($http_response_header ?? [] as $h) {
            if (preg_match('#^HTTP/\S+ (\d{3})#', $h, $m)) {
                $status = (int) $m[1];
            }
        }
        if ($body !== false && $status === 200 && substr($body, 0, 2) === "\xFF\xD8") {
            return $body;
        }
        $wait = $delay * (2 ** $attempt) * 2;
        fwrite(STDERR, "  attempt $attempt failed (HTTP $status), retrying in {$wait}s\n");
        usleep((int) ($wait * 1e6));
    }

    return null;
};

/** Mean brightness 0-255 (sampled), or null if GD is unavailable. */
$brightness = static function (string $jpeg): ?float {
    if (!function_exists('imagecreatefromstring')) {
        return null;
    }
    $im = @imagecreatefromstring($jpeg);
    if ($im === false) {
        return 0.0;
    }
    $sum = 0;
    $n = 0;
    $w = imagesx($im);
    $h = imagesy($im);
    for ($y = 5; $y < $h; $y += 20) {
        for ($x = 5; $x < $w; $x += 20) {
            $c = imagecolorat($im, $x, $y);
            $sum += (($c >> 16) & 255) + (($c >> 8) & 255) + ($c & 255);
            $n += 3;
        }
    }
    imagedestroy($im);

    return $n > 0 ? $sum / $n : 0.0;
};

$recompress = static function (string $jpeg) use ($quality): string {
    if (!function_exists('imagejpeg') || ($im = @imagecreatefromstring($jpeg)) === false) {
        return $jpeg;
    }
    imageinterlace($im, true);
    ob_start();
    imagejpeg($im, null, $quality);
    $out = (string) ob_get_clean();
    imagedestroy($im);

    return strlen($out) > 0 && strlen($out) < strlen($jpeg) ? $out : $jpeg;
};

$ok = $skipped = $failed = 0;
$failures = [];
$valid = [];
foreach ($stars as $s) {
    $id = (string) $s['hyg_id'];
    $valid[$id] = true;
    $file = "$dir/$id.jpg";
    if (!$force && isset($images[$id]) && is_file($file)) {
        $skipped++;
        continue;
    }
    if ($ok + $failed >= $limit) {
        break;
    }
    $raDeg = (float) $s['ra'] * 15.0;
    $dec = (float) $s['dec'];
    $fov = SurveyImages::fovFor((float) $s['mag']);
    $candidates = array_values(array_unique([SurveyImages::surveyFor($dec), 'CDS/P/DSS2/color']));
    $saved = false;
    foreach ($candidates as $hips) {
        [$epochYear] = [SurveyImages::SURVEYS[$hips][2]];
        [$ra, $de] = SurveyImages::positionAt($raDeg, $dec, $s['pmra'] !== null ? (float) $s['pmra'] : null, $s['pmdec'] !== null ? (float) $s['pmdec'] : null, $epochYear);
        $url = SurveyImages::url($hips, $ra, $de, $fov);
        fwrite(STDOUT, sprintf("[%s] %s via %s ... ", $id, $s['proper'] ?? '', $hips));
        $jpeg = $download($url);
        usleep((int) ($delay * 1e6));
        if ($jpeg === null) {
            fwrite(STDOUT, "failed\n");
            continue;
        }
        $b = $brightness($jpeg);
        if ($b !== null && $b < 2.0) {
            fwrite(STDOUT, "blank (no coverage), trying next survey\n");
            continue;
        }
        $jpeg = $recompress($jpeg);
        file_put_contents("$file.tmp", $jpeg);
        rename("$file.tmp", $file);
        $images[$id] = [
            'name' => $s['proper'],
            'hips' => $hips,
            'survey' => SurveyImages::SURVEYS[$hips][0],
            'credit' => SurveyImages::SURVEYS[$hips][1],
            'fov_deg' => $fov,
            'ra_deg' => round($ra, 6),
            'dec_deg' => round($de, 6),
            'epoch' => SurveyImages::SURVEYS[$hips][2],
            'source_url' => $url,
            'bytes' => strlen($jpeg),
            'fetched_at' => gmdate('c'),
        ];
        $save();
        fwrite(STDOUT, sprintf("ok (%d KB)\n", strlen($jpeg) / 1024));
        $saved = true;
        break;
    }
    if ($saved) {
        $ok++;
    } else {
        $failed++;
        $failures[] = $id;
    }
}

if (isset($opts['prune']) && !isset($opts['only'])) {
    foreach (array_keys($images) as $id) {
        if (!isset($valid[(string) $id])) {
            @unlink("$dir/$id.jpg");
            unset($images[$id]);
        }
    }
    $save();
}

$total = 0;
foreach (glob("$dir/*.jpg") ?: [] as $f) {
    $total += filesize($f);
}
fwrite(STDOUT, sprintf(
    "Done: %d downloaded, %d already present, %d failed%s. %d images, %.1f MB total.\n",
    $ok,
    $skipped,
    $failed,
    $failures ? ' (HYG ids: ' . implode(', ', $failures) . ')' : '',
    count($images),
    $total / 1048576,
));
exit($failed > 0 ? 2 : 0);
