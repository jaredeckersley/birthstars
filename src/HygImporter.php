<?php

declare(strict_types=1);

namespace BirthStars;

use PDO;

/**
 * Imports the HYG star catalog CSV into a small SQLite database (birthday-star candidates)
 * and writes a compact JSON file of bright stars for the offline sky chart.
 */
final class HygImporter
{
    public const MAX_DISTANCE_LY = 135.0;
    public const SKY_CHART_MAG_LIMIT = 5.5;

    private const REQUIRED = ['id', 'hip', 'hd', 'hr', 'gl', 'proper', 'ra', 'dec', 'dist', 'mag', 'spect', 'ci', 'bayer', 'flam', 'con'];

    /**
     * @param array<string, string> $meta Provenance (source URL, sha256, release...)
     * @return array{stars: int, named: int, sky: int}
     */
    public function import(string $csvPath, string $dbPath, ?string $skyJsonPath, array $meta = []): array
    {
        $in = @fopen($csvPath, 'rb');
        if ($in === false) {
            throw new \RuntimeException("Cannot open $csvPath");
        }
        $header = fgetcsv($in, escape: '');
        if ($header === false) {
            throw new \RuntimeException('CSV is empty');
        }
        $header = array_map(static fn ($h) => trim((string) $h, "\xEF\xBB\xBF \""), $header);
        $missing = array_diff(self::REQUIRED, $header);
        if ($missing !== []) {
            throw new \RuntimeException('Not a HYG CSV, missing columns: ' . implode(', ', $missing));
        }
        $col = array_flip($header);

        $tmp = $dbPath . '.tmp';
        @unlink($tmp);
        $pdo = new PDO('sqlite:' . $tmp, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE TABLE stars (
            hyg_id INTEGER PRIMARY KEY, hip INTEGER, hr INTEGER, hd INTEGER, gl TEXT,
            proper TEXT, bayer TEXT, flam INTEGER, con TEXT,
            ra REAL NOT NULL, dec REAL NOT NULL, dist_ly REAL NOT NULL, mag REAL NOT NULL,
            spect TEXT, named INTEGER NOT NULL)');
        $pdo->exec('CREATE TABLE meta (key TEXT PRIMARY KEY, value TEXT NOT NULL)');
        $ins = $pdo->prepare('INSERT INTO stars VALUES (:hyg_id,:hip,:hr,:hd,:gl,:proper,:bayer,:flam,:con,:ra,:dec,:dist_ly,:mag,:spect,:named)');

        $stars = $named = 0;
        $sky = [];
        $pdo->beginTransaction();
        while (($r = fgetcsv($in, escape: '')) !== false) {
            if (count($r) < count($header)) {
                continue;
            }
            $g = static fn (string $k): string => trim((string) $r[$col[$k]]);
            if ($g('id') === '0' || strcasecmp($g('proper'), 'Sol') === 0) {
                continue; // the Sun
            }
            if (!is_numeric($g('ra')) || !is_numeric($g('dec')) || !is_numeric($g('mag'))) {
                continue;
            }
            $ra = (float) $g('ra');
            $dec = (float) $g('dec');
            $mag = (float) $g('mag');
            $ci = is_numeric($g('ci')) ? (float) $g('ci') : 0.6;
            if ($mag <= self::SKY_CHART_MAG_LIMIT) {
                $sky[] = [round($ra * 15, 3), round($dec, 3), round($mag, 2), round($ci, 2)];
            }

            $distPc = is_numeric($g('dist')) ? (float) $g('dist') : 0.0;
            // HYG uses 100000 pc as "unknown distance".
            if ($distPc <= 0.0 || $distPc >= 100000.0) {
                continue;
            }
            $ly = $distPc * Star::PARSEC_IN_LY;
            if ($ly > self::MAX_DISTANCE_LY) {
                continue;
            }
            $isNamed = $g('proper') !== '' || $g('bayer') !== '' || $g('flam') !== '';
            $n = static fn (string $v) => $v === '' ? null : $v;
            $i = static fn (string $v) => ctype_digit($v) ? (int) $v : null;
            $ins->execute([
                'hyg_id' => (int) $g('id'), 'hip' => $i($g('hip')), 'hr' => $i($g('hr')), 'hd' => $i($g('hd')),
                'gl' => $n($g('gl')), 'proper' => $n($g('proper')), 'bayer' => $n($g('bayer')),
                'flam' => $i($g('flam')), 'con' => $n($g('con')), 'ra' => $ra, 'dec' => $dec,
                'dist_ly' => round($ly, 4), 'mag' => $mag, 'spect' => $n($g('spect')), 'named' => $isNamed ? 1 : 0,
            ]);
            $stars++;
            $named += $isNamed ? 1 : 0;
        }
        fclose($in);
        if ($named === 0) {
            $pdo->rollBack();
            unset($pdo);
            @unlink($tmp);
            throw new \RuntimeException('No usable named stars found; refusing to replace the database.');
        }
        $pdo->exec('CREATE INDEX idx_named_dist ON stars(named, dist_ly)');
        $meta += ['imported_at' => gmdate('c'), 'max_distance_ly' => (string) self::MAX_DISTANCE_LY];
        $meta['star_count'] = (string) $stars;
        $meta['named_count'] = (string) $named;
        $m = $pdo->prepare('INSERT INTO meta VALUES (?, ?)');
        foreach ($meta as $k => $v) {
            $m->execute([$k, $v]);
        }
        $pdo->commit();
        $pdo->exec('VACUUM');
        unset($pdo, $ins, $m);
        if (!rename($tmp, $dbPath)) {
            throw new \RuntimeException("Could not move database into $dbPath");
        }

        if ($skyJsonPath !== null) {
            usort($sky, static fn ($a, $b) => $a[2] <=> $b[2]);
            $json = json_encode(['source' => $meta['source'] ?? 'HYG', 'fields' => ['ra_deg', 'dec_deg', 'mag', 'ci'], 'stars' => $sky], JSON_THROW_ON_ERROR);
            file_put_contents($skyJsonPath, $json);
        }

        return ['stars' => $stars, 'named' => $named, 'sky' => count($sky)];
    }
}
