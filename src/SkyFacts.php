<?php

declare(strict_types=1);

namespace BirthStars;

use DateTimeImmutable;
use DateTimeZone;

/** Kid-friendly facts about where and how a star can be seen from an observing site. */
/** @psalm-immutable Properties are treated as read-only (PHP 8.0 has no readonly). */
final class SkyFacts
{
    public function __construct(
        public float $latitude = 19.72,
        public float $longitude = -155.08,
        public string $placeName = 'Hilo, Hawaiʻi',
        public string $timezone = 'Pacific/Honolulu',
    ) {
    }

    public static function brightness(float $mag): string
    {
        return match (true) {
            $mag < 0.0 => 'one of the very brightest stars in the whole sky!',
            $mag < 1.5 => 'one of the brightest stars in the sky. Easy to spot!',
            $mag < 3.0 => 'bright enough to see easily with just your eyes.',
            $mag < 4.5 => 'visible with just your eyes on a clear, dark night.',
            $mag < 6.5 => 'just barely visible with your eyes from a very dark place, like Maunakea.',
            $mag < 9.0 => 'too faint for your eyes, but binoculars will show it.',
            default => 'too faint for your eyes. You need a telescope to see it!',
        };
    }

    public static function nakedEye(float $mag): bool
    {
        return $mag < 6.5;
    }

    /** Highest altitude (degrees) the star ever reaches from this site; negative = never rises. */
    public function maxAltitude(float $decDeg): float
    {
        return 90.0 - abs($this->latitude - $decDeg);
    }

    public function everRises(float $decDeg): bool
    {
        return $this->maxAltitude($decDeg) > 0.0;
    }

    /** Altitude in degrees at a given moment. */
    public function altitudeAt(float $raHours, float $decDeg, DateTimeImmutable $when): float
    {
        $jd = $when->getTimestamp() / 86400.0 + 2440587.5;
        $d = $jd - 2451545.0;
        $gmst = fmod(280.46061837 + 360.98564736629 * $d, 360.0);
        $lst = fmod($gmst + $this->longitude + 720.0, 360.0);
        $ha = deg2rad($lst - $raHours * 15.0);
        $lat = deg2rad($this->latitude);
        $dec = deg2rad($decDeg);
        $sinAlt = sin($lat) * sin($dec) + cos($lat) * cos($dec) * cos($ha);

        return rad2deg(asin(max(-1.0, min(1.0, $sinAlt))));
    }

    /** Altitude at 8:30 pm local time on the given day (a typical "star party" time). */
    public function altitudeTonight(float $raHours, float $decDeg, ?DateTimeImmutable $day = null): float
    {
        $tz = new DateTimeZone($this->timezone);
        $day ??= new DateTimeImmutable('now', $tz);
        $evening = $day->setTimezone($tz)->setTime(20, 30);

        return $this->altitudeAt($raHours, $decDeg, $evening);
    }

    /** Month name when the star is highest in the sky around 9 pm. */
    public function bestMonth(float $raHours): string
    {
        // At 9 pm local solar time, the meridian is ~9h east of the Sun. Sun RA is 0h near March 20.
        $sunRa = fmod($raHours - 9.0 + 48.0, 24.0);
        $days = (int) round($sunRa / 24.0 * 365.25);
        $date = (new DateTimeImmutable('2001-03-20'))->modify("+$days days");

        return $date->format('F');
    }

    /** Compass direction to look (when star is highest). */
    public function lookDirection(float $decDeg): string
    {
        return $decDeg > $this->latitude ? 'north' : 'south';
    }

    /** @return array<string, mixed> */
    public function describe(Star $s, ?DateTimeImmutable $now = null): array
    {
        $tonight = $this->altitudeTonight($s->raHours, $s->decDeg, $now);

        return [
            'place' => $this->placeName,
            'brightness' => self::brightness($s->magnitude),
            'naked_eye' => self::nakedEye($s->magnitude),
            'ever_rises' => $this->everRises($s->decDeg),
            'max_altitude' => round($this->maxAltitude($s->decDeg)),
            'tonight_altitude' => round($tonight),
            'up_tonight' => $tonight > 10.0,
            'best_month' => $this->bestMonth($s->raHours),
            'direction' => $this->lookDirection($s->decDeg),
        ];
    }
}
