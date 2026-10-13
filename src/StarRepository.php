<?php

declare(strict_types=1);

namespace BirthStars;

use PDO;

final class StarRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public static function open(string $path): self
    {
        if (!is_file($path)) {
            throw new \RuntimeException("Star database not found at $path. Run: php bin/import-stars.php");
        }
        $pdo = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        return new self($pdo);
    }

    /** The named star whose distance in light years is closest to $ly. */
    public function closestNamed(float $ly): ?Star
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM stars WHERE named = 1 ORDER BY ABS(dist_ly - :ly), mag LIMIT 1'
        );
        $st->execute(['ly' => $ly]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ? Star::fromRow($row) : null;
    }

    /** How many catalogued stars (named or not) lie closer than $ly. */
    public function countCloserThan(float $ly): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM stars WHERE dist_ly <= :ly');
        $st->execute(['ly' => $ly]);

        return (int) $st->fetchColumn();
    }

    /** @return array<string, string> */
    public function meta(): array
    {
        return $this->pdo->query('SELECT key, value FROM meta')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
