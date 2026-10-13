<?php

declare(strict_types=1);

namespace BirthStars;

/**
 * Real sky-survey photos of birthday stars, pre-downloaded from CDS hips2fits
 * into public/assets/survey/<hyg_id>.jpg with a manifest.json describing each one.
 */
final class SurveyImages
{
    public const ENDPOINT = 'https://alasky.cds.unistra.fr/hips-image-services/hips2fits';
    public const SIZE_PX = 600;

    /** HiPS id => [label, credit, observing epoch (years) used for proper motion, minimum Dec covered] */
    public const SURVEYS = [
        'CDS/P/PanSTARRS/DR1/color-z-zg-g' => [
            'Pan-STARRS1 (PS1) 3π Survey',
            'Pan-STARRS1 Surveys (PS1), Institute for Astronomy, University of Hawaiʻi, Haleakalā, Maui; image via CDS hips2fits',
            2012.0,
            -29.0,
        ],
        'CDS/P/DSS2/color' => [
            'Digitized Sky Survey 2 (DSS2)',
            'Digitized Sky Survey, STScI/NASA (Palomar/UK Schmidt plates, Caltech, AAO/ROE); image via CDS hips2fits',
            1993.0,
            -90.0,
        ],
        'CDS/P/2MASS/color' => [
            'Two Micron All Sky Survey (2MASS), infrared',
            'Two Micron All Sky Survey (2MASS), UMass/IPAC-Caltech, NASA/NSF; image via CDS hips2fits',
            1999.0,
            -90.0,
        ],
    ];

    /** Order in which surveys are tried for a star: Pan-STARRS (if covered), then DSS2, then 2MASS. */
    public static function candidatesFor(float $decDeg): array
    {
        return array_values(array_unique([self::surveyFor($decDeg), 'CDS/P/DSS2/color', 'CDS/P/2MASS/color']));
    }

    private string $dir;
    /** @var array<string, array<string, mixed>>|null */
    private ?array $manifest = null;

    public function __construct(string $dir)
    {
        $this->dir = rtrim($dir, '/');
    }

    /** Pan-STARRS where it covers the sky (Dec > -29°), DSS2 elsewhere. */
    public static function surveyFor(float $decDeg): string
    {
        return $decDeg > self::SURVEYS['CDS/P/PanSTARRS/DR1/color-z-zg-g'][3]
            ? 'CDS/P/PanSTARRS/DR1/color-z-zg-g'
            : 'CDS/P/DSS2/color';
    }

    /** Field of view in degrees: wider for bright stars so their glare fits in the frame. */
    public static function fovFor(float $mag): float
    {
        if ($mag < 0.5) {
            return 1.0;
        }
        if ($mag < 2.0) {
            return 0.7;
        }
        if ($mag < 4.0) {
            return 0.5;
        }
        if ($mag < 7.0) {
            return 0.35;
        }

        return 0.25;
    }

    /**
     * Shift J2000 coordinates to the survey epoch using proper motion (mas/yr, pmra includes cos δ).
     * HYG uses 9999.99 as a capped value; it is still the best estimate available.
     *
     * @return array{0: float, 1: float} [ra_deg, dec_deg]
     */
    public static function positionAt(float $raDeg, float $decDeg, ?float $pmra, ?float $pmdec, float $epoch): array
    {
        $dt = $epoch - 2000.0;
        $cos = cos(deg2rad($decDeg));
        $dRa = ($pmra !== null && abs($cos) > 1e-6) ? $pmra * $dt / 3.6e6 / $cos : 0.0;
        $dDec = $pmdec !== null ? $pmdec * $dt / 3.6e6 : 0.0;
        $ra = fmod($raDeg + $dRa + 360.0, 360.0);

        return [$ra, max(-90.0, min(90.0, $decDeg + $dDec))];
    }

    public static function url(string $hips, float $raDeg, float $decDeg, float $fov): string
    {
        return self::ENDPOINT . '?' . http_build_query([
            'hips' => $hips, 'width' => self::SIZE_PX, 'height' => self::SIZE_PX, 'fov' => round($fov, 4),
            'ra' => round($raDeg, 6), 'dec' => round($decDeg, 6), 'projection' => 'TAN', 'format' => 'jpg',
        ]);
    }

    /** Kid-friendly size comparison. The full Moon is about 0.5° wide. */
    public static function fovInWords(float $fovDeg): string
    {
        $moons = $fovDeg / 0.5;

        return match (true) {
            $moons < 0.6 => 'about half the width of the full Moon',
            $moons < 0.85 => 'about three-quarters of the width of the full Moon',
            $moons < 1.2 => 'about as wide as the full Moon',
            $moons < 1.7 => 'about one and a half full Moons wide',
            default => 'about ' . (int) round($moons) . ' full Moons wide',
        };
    }

    /** @return array<string, array<string, mixed>> */
    public function manifest(): array
    {
        if ($this->manifest === null) {
            $file = $this->dir . '/manifest.json';
            $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
            $this->manifest = is_array($data) && is_array($data['images'] ?? null) ? $data['images'] : [];
        }

        return $this->manifest;
    }

    /** @return array<string, mixed>|null Image info for a star, only if the file is really there. */
    public function forStar(int $hygId): ?array
    {
        $entry = $this->manifest()[(string) $hygId] ?? null;
        if (!is_array($entry) || !is_file($this->dir . '/' . $hygId . '.jpg')) {
            return null;
        }
        $hips = (string) ($entry['hips'] ?? '');
        $fov = (float) ($entry['fov_deg'] ?? 0.5);

        return $entry + [
            'file' => $hygId . '.jpg',
            'survey' => self::SURVEYS[$hips][0] ?? $hips,
            'credit' => self::SURVEYS[$hips][1] ?? 'CDS hips2fits',
            'fov_words' => self::fovInWords($fov),
            'pan_starrs' => strpos($hips, 'PanSTARRS') !== false,
            'twomass' => strpos($hips, '2MASS') !== false,
        ];
    }
}
