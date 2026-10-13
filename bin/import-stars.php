#!/usr/bin/env php
<?php

/**
 * Refresh the star data.
 *
 *   php bin/import-stars.php                 # download the pinned HYG release and rebuild
 *   php bin/import-stars.php path/to/hyg.csv # use a local copy (e.g. no internet at the booth)
 *
 * To move to a newer HYG release, change HYG_COMMIT / HYG_FILE / HYG_SHA256 below.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use BirthStars\HygImporter;

const HYG_REPO = 'astronexus/HYG-Database';
const HYG_COMMIT = 'e4fd9c95381c6b5f34e465259cb4d2e1a7ff2e09'; // 2025-02-13, HYG v4.1
const HYG_FILE = 'hyg/CURRENT/hygdata_v41.csv';
const HYG_SHA256 = 'd9f69fd86bbf90a4e4d52b4c5c53eacfa6dfc0bfdef85bfd94f095e0bebe4ebd';

$root = dirname(__DIR__);
$url = 'https://raw.githubusercontent.com/' . HYG_REPO . '/' . HYG_COMMIT . '/' . HYG_FILE;
$csv = $argv[1] ?? null;
$tmpDownload = null;

if ($csv === null) {
    $tmpDownload = tempnam(sys_get_temp_dir(), 'hyg');
    fwrite(STDOUT, "Downloading $url ...\n");
    $ctx = stream_context_create(['http' => ['timeout' => 120, 'user_agent' => 'birthstars-importer']]);
    if (!@copy($url, $tmpDownload, $ctx)) {
        fwrite(STDERR, "Download failed. Download the file by hand and pass its path as an argument.\n");
        exit(1);
    }
    $csv = $tmpDownload;
}

$hash = hash_file('sha256', $csv);
if ($hash !== HYG_SHA256) {
    fwrite(STDERR, "Warning: SHA-256 $hash does not match the pinned release (" . HYG_SHA256 . ").\n");
    if ($tmpDownload !== null) {
        fwrite(STDERR, "Refusing to import an unexpected download.\n");
        exit(1);
    }
}

$result = (new HygImporter())->import(
    $csv,
    $root . '/data/stars.sqlite',
    $root . '/public/assets/data/sky-stars.json',
    [
        'source' => 'HYG Database v4.1 (astronexus/HYG-Database), CC BY-SA 4.0',
        'source_url' => $url,
        'source_sha256' => $hash,
    ],
);

if ($tmpDownload !== null) {
    @unlink($tmpDownload);
}

fwrite(STDOUT, sprintf(
    "Imported %d stars within %.0f light years (%d with names). Sky chart: %d bright stars.\n",
    $result['stars'],
    HygImporter::MAX_DISTANCE_LY,
    $result['named'],
    $result['sky'],
));
