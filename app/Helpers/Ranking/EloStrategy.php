<?php

declare(strict_types=1);

namespace App\Helpers\Ranking;

/**
 * Klasyczne Elo (K=32) — sporty 1vs1 turniejowe: tenis, squash, padel, szachy.
 *
 * Dla każdej pary uczestników z różnym finishPlace traktujemy pojedynek
 * jako wygraną wyżej-uplasowanego. Expected score liczony wzorem
 *   E_a = 1 / (1 + 10^((R_b - R_a) / 400))
 * Nowy rating: R' = R + K * (S - E), gdzie S = 1 dla wygranej, 0 dla porażki,
 * 0.5 dla remisu (równe finishPlace).
 */
final class EloStrategy implements RankingStrategyInterface
{
    private const K_FACTOR = 32;
    private const DEFAULT_RATING = 1000;

    public function key(): string
    {
        return 'elo';
    }

    public function recalculate(array $participants, array $currentRankings, array $context): array
    {
        $k = (int)($context['k_factor'] ?? self::K_FACTOR);
        $startRating = (int)($context['default_rating'] ?? self::DEFAULT_RATING);

        // Build a member_id => state map from current rankings.
        $state = [];
        foreach ($currentRankings as $row) {
            $mid = (int)$row['member_id'];
            $state[$mid] = [
                'member_id'    => $mid,
                'rating'       => (int)($row['points'] ?? $startRating),
                'games_played' => (int)($row['games_played'] ?? 0),
                'wins'         => (int)($row['wins'] ?? 0),
                'start'        => (int)($row['points'] ?? $startRating),
            ];
        }

        // Filter participants with valid finishPlace and seed missing state.
        $valid = [];
        foreach ($participants as $p) {
            $mid = (int)($p['member_id'] ?? 0);
            $place = isset($p['finishPlace']) ? (int)$p['finishPlace'] : null;
            if ($mid <= 0 || $place === null || $place <= 0) {
                continue;
            }
            if (!isset($state[$mid])) {
                $state[$mid] = [
                    'member_id'    => $mid,
                    'rating'       => $startRating,
                    'games_played' => 0,
                    'wins'         => 0,
                    'start'        => $startRating,
                ];
            }
            $valid[] = ['member_id' => $mid, 'place' => $place];
        }

        // Pairwise comparison — Elo aktualizujemy "all-play-all" w obrębie turnieju.
        // Pracujemy na kopii ratingów, żeby każda para porównywała stan z początku
        // rundy (eliminuje order-dependence).
        $newRating = [];
        foreach ($valid as $p) {
            $newRating[$p['member_id']] = $state[$p['member_id']]['rating'];
        }
        $count = count($valid);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $valid[$i];
                $b = $valid[$j];
                $rA = $state[$a['member_id']]['rating'];
                $rB = $state[$b['member_id']]['rating'];
                $eA = 1 / (1 + pow(10, ($rB - $rA) / 400));
                $eB = 1 - $eA;
                if ($a['place'] < $b['place']) {
                    $sA = 1.0; $sB = 0.0;
                } elseif ($a['place'] > $b['place']) {
                    $sA = 0.0; $sB = 1.0;
                } else {
                    $sA = 0.5; $sB = 0.5;
                }
                $newRating[$a['member_id']] += $k * ($sA - $eA);
                $newRating[$b['member_id']] += $k * ($sB - $eB);
            }
        }

        $out = [];
        foreach ($valid as $p) {
            $mid = $p['member_id'];
            $newPoints = (int)round($newRating[$mid]);
            $delta = $newPoints - $state[$mid]['start'];
            $isWin = $p['place'] === 1;
            $out[] = [
                'member_id'    => $mid,
                'new_points'   => $newPoints,
                'delta'        => $delta,
                'games_played' => $state[$mid]['games_played'] + 1,
                'wins'         => $state[$mid]['wins'] + ($isWin ? 1 : 0),
                'is_win'       => $isWin,
            ];
        }
        return $out;
    }
}
