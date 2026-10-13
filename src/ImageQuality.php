<?php

declare(strict_types=1);

namespace BirthStars;

/**
 * Detects unusable survey cutouts (needs ext-gd):
 *  - "dark_core": a bright star whose centre is masked/saturated to black (Pan-STARRS NaN cores),
 *  - "near_blank": an almost entirely black frame (no coverage),
 *  - "zero_regions": large exactly-black areas (coverage gaps / NaN tiles).
 */
final class ImageQuality
{
    public const CORE_RADIUS = 0.06;      // fraction of image width searched for the star centre
    public const HOLE_MIN = 2;           // sampled (every 2nd) hole pixels needed to call it a masked core
    public const BLOCKS = 10;            // grid used to find large empty (zero/NaN) areas

    public static function available(): bool
    {
        return function_exists('imagecreatefromstring');
    }

    /** @return array{issues: list<string>, metrics: array<string, float>} */
    public static function assessFile(string $path): array
    {
        return self::assess((string) file_get_contents($path));
    }

    /** @return array{issues: list<string>, metrics: array<string, float>} */
    public static function assess(string $jpeg): array
    {
        $im = @imagecreatefromstring($jpeg);
        if ($im === false) {
            return ['issues' => ['unreadable'], 'metrics' => []];
        }
        $w = imagesx($im);
        $h = imagesy($im);
        $lum = static function (int $x, int $y) use ($im): float {
            $c = imagecolorat($im, $x, $y);

            return 0.299 * (($c >> 16) & 255) + 0.587 * (($c >> 8) & 255) + 0.114 * ($c & 255);
        };

        // Whole-frame statistics, plus a BLOCKS x BLOCKS grid of "empty" blocks (all ~zero, no stars).
        $sum = 0.0;
        $n = 0;
        $bright = 0;
        $emptyBlocks = 0;
        $bw = intdiv($w, self::BLOCKS);
        $bh = intdiv($h, self::BLOCKS);
        for ($by = 0; $by < self::BLOCKS; $by++) {
            for ($bx = 0; $bx < self::BLOCKS; $bx++) {
                $zeros = 0;
                $cnt = 0;
                $max = 0.0;
                for ($y = $by * $bh + 1; $y < ($by + 1) * $bh; $y += 3) {
                    for ($x = $bx * $bw + 1; $x < ($bx + 1) * $bw; $x += 3) {
                        $l = $lum($x, $y);
                        $sum += $l;
                        $n++;
                        $cnt++;
                        $bright += $l >= 120 ? 1 : 0;
                        $zeros += $l <= 3.0 ? 1 : 0;
                        $max = max($max, $l);
                    }
                }
                if ($cnt > 0 && $zeros / $cnt > 0.97 && $max < 40) {
                    $emptyBlocks++;
                }
            }
        }
        $mean = $sum / max(1, $n);
        $zeroFrac = $emptyBlocks / (self::BLOCKS * self::BLOCKS);
        $brightFrac = $bright / max(1, $n);

        // Centre: Pan-STARRS masks saturated pixels of bright stars, leaving a black or oddly coloured
        // "hole" inside a bright halo. Count pixels near the centre that are much darker than the
        // halo around them in all 8 directions.
        // [mean of RGB, max of RGB] for a pixel.
        $px3 = static function (int $x, int $y) use ($im): array {
            $c = imagecolorat($im, $x, $y);
            $r = ($c >> 16) & 255;
            $g = ($c >> 8) & 255;
            $b = $c & 255;

            return [($r + $g + $b) / 3.0, (float) max($r, $g, $b)];
        };
        $cx = intdiv($w, 2);
        $cy = intdiv($h, 2);
        $r = (int) round($w * self::CORE_RADIUS);
        $dirs = [[1, 0], [-1, 0], [0, 1], [0, -1], [1, 1], [1, -1], [-1, 1], [-1, -1]];
        $holes = 0;
        for ($y = $cy - $r; $y <= $cy + $r; $y += 2) {
            for ($x = $cx - $r; $x <= $cx + $r; $x += 2) {
                if (hypot($x - $cx, $y - $cy) > $r) {
                    continue;
                }
                [$v, $vmax] = $px3($x, $y);
                if ($v > 130) {
                    continue;
                }
                $enclosed = 0;
                foreach ($dirs as [$dx, $dy]) {
                    for ($k = 2; $k <= 14; $k++) {
                        $px = $x + $dx * $k;
                        $py = $y + $dy * $k;
                        if ($px < 0 || $py < 0 || $px >= $w || $py >= $h) {
                            break;
                        }
                        [$u, $umax] = $px3($px, $py);
                        // Much brighter on average (white/orange halo) or much brighter in its
                        // strongest channel (saturated blue halo around a black hole).
                        if (($u > 140 && $u > $v / 0.6) || ($umax > 180 && $vmax < 0.55 * $umax)) {
                            $enclosed++;
                            break;
                        }
                    }
                }
                if ($enclosed >= 8) {
                    $holes++;
                }
            }
        }
        imagedestroy($im);
        
        $issues = [];
        if ($holes >= self::HOLE_MIN) {
            $issues[] = 'dark_core';
        }
        if ($zeroFrac >= 0.9 || ($mean < 5.0 && $brightFrac < 0.001)) {
            $issues[] = 'near_blank';
        }
        if ($zeroFrac >= 0.08) {
            $issues[] = 'zero_regions';
        }

        return ['issues' => $issues, 'metrics' => [
            'mean' => round($mean, 2), 'zero_frac' => round($zeroFrac, 4), 'bright_frac' => round($brightFrac, 4),
            'core_hole_pixels' => (float) $holes,
        ]];
    }
}
