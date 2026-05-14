<?php

declare(strict_types=1);

namespace App\Helpers\Ranking;

/**
 * Punkty ligowe — sumowanie zwycięstw/remisów. Sporty drużynowe:
 * football, basketball, volleyball, handball.
 *
 * Input participant: 'result' => 'win'|'draw'|'loss'  (preferowane)
 *   lub 'finishPlace' = 1 jako alias dla 'win' (turnieje pucharowe).
 * Context (configurable): points_per_win=3, points_per_draw=1, points_per_loss=0.
 */
final class LeaguePointsStrategy implements RankingStrategyInterface
{
    public function key(): string
    {
        return 'league_points';
    }

    public function recalculate(array $participants, array $currentRankings, array $context): array
    {
        $pWin  = (int)($context['points_per_win']  ?? 3);
        $pDraw = (int)($context['points_per_draw'] ?? 1);
        $pLoss = (int)($context['points_per_loss'] ?? 0);

        $state = [];
        foreach ($currentRankings as $row) {
            $mid = (int)$row['member_id'];
            $state[$mid] = [
                'points'       => (int)($row['points'] ?? 0),
                'games_played' => (int)($row['games_played'] ?? 0),
                'wins'         => (int)($row['wins'] ?? 0),
            ];
        }

        $out = [];
        foreach ($participants as $p) {
            $mid = (int)($p['member_id'] ?? 0);
            if ($mid <= 0) {
                continue;
            }
            $result = strtolower((string)($p['result'] ?? ''));
            if ($result === '' && isset($p['finishPlace'])) {
                $result = (int)$p['finishPlace'] === 1 ? 'win' : 'loss';
            }
            $delta = match ($result) {
                'win'  => $pWin,
                'draw' => $pDraw,
                'loss' => $pLoss,
                default => 0,
            };
            $cur = $state[$mid] ?? ['points' => 0, 'games_played' => 0, 'wins' => 0];
            $isWin = $result === 'win';
            $out[] = [
                'member_id'    => $mid,
                'new_points'   => $cur['points'] + $delta,
                'delta'        => $delta,
                'games_played' => $cur['games_played'] + 1,
                'wins'         => $cur['wins'] + ($isWin ? 1 : 0),
                'is_win'       => $isWin,
            ];
        }
        return $out;
    }
}
