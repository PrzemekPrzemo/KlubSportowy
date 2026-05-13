<?php

declare(strict_types=1);

namespace App\Helpers\Ranking;

use App\Helpers\Database;
use PDO;

/**
 * Wybór strategii dla sportu. Preferowane źródło: `sports.ranking_strategy`
 * (kolumna dodana w migracji 057). Fallback: hardcoded mapa per sport_key.
 */
final class RankingStrategyFactory
{
    /** Default mapping when DB column is missing or NULL. */
    private const DEFAULTS = [
        'football'      => 'league_points',
        'basketball'    => 'league_points',
        'volleyball'    => 'league_points',
        'handball'      => 'league_points',
        'tennis'        => 'elo',
        'squash'        => 'elo',
        'padel'         => 'elo',
        'chess'         => 'elo',
        'table_tennis'  => 'elo',
        'badminton'     => 'elo',
        'swimming'      => 'best_time',
        'athletics'     => 'best_time',
        'rollerskating' => 'best_time',
        'shooting'      => 'best_time',
        'cycling'       => 'best_time',
        'rowing'        => 'best_time',
    ];

    /** In-process cache: sport_key => strategy_key. */
    private static array $cache = [];

    public static function forSport(string $sportKey): RankingStrategyInterface
    {
        return self::byKey(self::resolveKey($sportKey));
    }

    public static function byKey(string $key): RankingStrategyInterface
    {
        return match ($key) {
            'elo'           => new EloStrategy(),
            'best_time'     => new BestTimeStrategy(),
            'league_points' => new LeaguePointsStrategy(),
            default         => new LeaguePointsStrategy(),
        };
    }

    public static function resolveKey(string $sportKey): string
    {
        $sportKey = strtolower(trim($sportKey));
        if (isset(self::$cache[$sportKey])) {
            return self::$cache[$sportKey];
        }
        $resolved = self::DEFAULTS[$sportKey] ?? 'league_points';

        // Try DB lookup — schema may or may not have the column yet (graceful fallback).
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("SELECT `ranking_strategy` FROM `sports` WHERE `key` = ? LIMIT 1");
            $stmt->execute([$sportKey]);
            $val = $stmt->fetchColumn();
            if (is_string($val) && $val !== '') {
                $resolved = $val;
            }
        } catch (\Throwable) {
            // Column or table missing — keep default.
        }

        self::$cache[$sportKey] = $resolved;
        return $resolved;
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
