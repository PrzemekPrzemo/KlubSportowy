<?php

declare(strict_types=1);

namespace App\Helpers\Ranking;

/**
 * Czasówka — sporty czasowe (pływanie, lekkoatletyka, rollerskating)
 * oraz punktowe gdzie liczy się pozycja (strzelectwo).
 *
 * Jeśli participant ma 'time' (mniej = lepiej), ranking wyliczany jest po czasie;
 * w przeciwnym razie używamy podanego 'finishPlace'.
 * Punkty za pozycję: 1=10, 2=8, 3=6, 4=5, 5=4, 6=3, 7=2, 8=2, 9=1, 10=1, dalej 0.
 */
final class BestTimeStrategy implements RankingStrategyInterface
{
    private const PLACE_POINTS = [
        1 => 10, 2 => 8, 3 => 6, 4 => 5, 5 => 4,
        6 => 3, 7 => 2, 8 => 2, 9 => 1, 10 => 1,
    ];

    public function key(): string
    {
        return 'best_time';
    }

    public function recalculate(array $participants, array $currentRankings, array $context): array
    {
        $table = $context['place_points'] ?? self::PLACE_POINTS;

        $state = [];
        foreach ($currentRankings as $row) {
            $mid = (int)$row['member_id'];
            $state[$mid] = [
                'points'       => (int)($row['points'] ?? 0),
                'games_played' => (int)($row['games_played'] ?? 0),
                'wins'         => (int)($row['wins'] ?? 0),
            ];
        }

        // Determine places: jeśli mamy times → posortuj rosnąco; inaczej użyj finishPlace.
        $hasTimes = false;
        foreach ($participants as $p) {
            if (isset($p['time']) && (float)$p['time'] > 0) {
                $hasTimes = true;
                break;
            }
        }

        $ranked = [];
        if ($hasTimes) {
            $withTime = array_values(array_filter($participants, static fn($p) =>
                isset($p['time']) && (float)$p['time'] > 0 && (int)($p['member_id'] ?? 0) > 0));
            usort($withTime, static fn($a, $b) => (float)$a['time'] <=> (float)$b['time']);
            $place = 1;
            foreach ($withTime as $p) {
                $ranked[] = ['member_id' => (int)$p['member_id'], 'place' => $place++];
            }
        } else {
            foreach ($participants as $p) {
                $mid = (int)($p['member_id'] ?? 0);
                $place = isset($p['finishPlace']) ? (int)$p['finishPlace'] : 0;
                if ($mid > 0 && $place > 0) {
                    $ranked[] = ['member_id' => $mid, 'place' => $place];
                }
            }
        }

        $out = [];
        foreach ($ranked as $r) {
            $mid = $r['member_id'];
            $delta = (int)($table[$r['place']] ?? 0);
            $cur = $state[$mid] ?? ['points' => 0, 'games_played' => 0, 'wins' => 0];
            $isWin = $r['place'] === 1;
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
