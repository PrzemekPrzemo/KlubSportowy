<?php

declare(strict_types=1);

namespace App\Helpers\Ranking;

/**
 * Kontrakt strategii rankingowej. Każda implementacja przelicza punkty
 * członków po pojedynczym evencie/turnieju (lub pełnym sezonie).
 *
 * Input shape (per participant):
 *   [
 *     'member_id'    => int,
 *     'finishPlace'  => ?int,   // 1 = winner, NULL = bez wyniku
 *     'score'        => ?float, // punkty/gole
 *     'time'         => ?float, // czas w sekundach (im mniej, tym lepiej)
 *     'result'       => ?string,// 'win'|'draw'|'loss' (sporty drużynowe)
 *     ...
 *   ]
 *
 * Current rankings (per member):
 *   ['member_id' => int, 'points' => int, 'games_played' => int, 'wins' => int]
 *
 * Output:
 *   ['member_id' => int, 'new_points' => int, 'delta' => int,
 *    'games_played' => int, 'is_win' => bool]
 */
interface RankingStrategyInterface
{
    /** Zwraca klucz strategii: 'elo' | 'league_points' | 'best_time'. */
    public function key(): string;

    /**
     * @param array<int, array<string, mixed>> $participants
     * @param array<int, array<string, mixed>> $currentRankings
     * @param array<string, mixed> $context  // np. points_per_win, season, sport_key
     * @return array<int, array<string, mixed>>
     */
    public function recalculate(array $participants, array $currentRankings, array $context): array;
}
