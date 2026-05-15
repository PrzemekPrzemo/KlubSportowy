<?php

declare(strict_types=1);

namespace App\Helpers\Bracket;

/**
 * Pure tournament bracket math.
 *
 * Wszystkie metody sa stateless — generuja struktury danych (round/position/seed slot),
 * ale NIE zapisuja niczego do bazy. Persystencja jest w `TournamentBracketController`
 * + `TournamentBracketModel`.
 *
 * Layout danych zwracanych przez singleElimination()/doubleElimination():
 *   [
 *     [
 *       'round'          => int,          // 1..N
 *       'position'       => int,          // 0-based w rundzie
 *       'bracket_side'   => 'upper'|'lower'|'final'|'third_place',
 *       'seed_a'         => int|null,     // seed numer (1-based) lub null = bye / TBD
 *       'seed_b'         => int|null,
 *       'parent_pos'     => int|null,     // pozycja meczu w nastepnej rundzie (do ktorego trafia winner)
 *       'parent_slot'    => 0|1|null,     // ktora pozycja (player1 vs player2) w parent
 *       'loser_to'       => [round, pos, slot]|null,  // DE only: gdzie loser dropuje
 *     ],
 *     ...
 *   ]
 *
 * Dla round_robin: ['round','position','seed_a','seed_b'] (bez parent_*).
 */
class BracketGenerator
{
    /**
     * Najmniejsza potega 2 >= n. Dla SE bracket size.
     */
    public static function bracketSize(int $n): int
    {
        if ($n <= 1) {
            return max(1, $n);
        }
        return (int) pow(2, (int) ceil(log($n, 2)));
    }

    /**
     * Liczba bye-ow (pustych slotow) w R1 dla N uczestnikow.
     */
    public static function byes(int $n): int
    {
        return self::bracketSize($n) - $n;
    }

    /**
     * Liczba rund SE dla N uczestnikow.
     */
    public static function roundsForSE(int $n): int
    {
        if ($n <= 1) return 0;
        return (int) ceil(log($n, 2));
    }

    /**
     * Standardowe seed-to-slot mapping dla turnieju o `size` slotach.
     * Zwraca tablice [pos => seed] (pos 0-based, seed 1-based), gdzie:
     *   - seed 1 vs seed N
     *   - seed 2 vs seed N-1
     *   itd. (tzw. standard tournament bracket).
     *
     * Dla size=8: [1, 8, 5, 4, 3, 6, 7, 2] (klasyczny układ — najmocniejsze nasiona oddalone).
     */
    public static function seedSlots(int $size): array
    {
        if ($size < 2 || ($size & ($size - 1)) !== 0) {
            throw new \InvalidArgumentException("Bracket size must be power of 2, got {$size}");
        }
        // Recursive standard seeding
        $slots = [1, 2];
        while (count($slots) < $size) {
            $next = [];
            $total = count($slots) * 2 + 1;
            foreach ($slots as $s) {
                $next[] = $s;
                $next[] = $total - $s;
            }
            $slots = $next;
        }
        return $slots;
    }

    /**
     * Generuje pelna drabinke single-elimination dla N uczestnikow (po assignSeeds).
     *
     * Algorytm:
     *   1. size = next power of 2 >= N
     *   2. seedSlots() ustala kolejnosc seedow w R1 (1 vs N, 2 vs N-1, ...)
     *   3. byes: seed > N zostaje null (auto-advance dla drugiego gracza)
     *   4. Buduje drzewo rund: R1 ma size/2 meczow, R2 ma size/4, ..., final 1 mecz
     *   5. parent_pos = floor(pos/2) w rundzie R+1
     *
     * @return array<int, array<string, mixed>>
     */
    public static function singleElimination(int $participantsCount, bool $thirdPlaceMatch = false): array
    {
        if ($participantsCount < 2) {
            return [];
        }
        $size = self::bracketSize($participantsCount);
        $slots = self::seedSlots($size);
        $rounds = self::roundsForSE($participantsCount);

        $matches = [];

        // Round 1
        $r1Count = $size / 2;
        for ($i = 0; $i < $r1Count; $i++) {
            $seedA = $slots[$i * 2];
            $seedB = $slots[$i * 2 + 1];
            $matches[] = [
                'round'        => 1,
                'position'     => $i,
                'bracket_side' => 'upper',
                'seed_a'       => $seedA <= $participantsCount ? $seedA : null,
                'seed_b'       => $seedB <= $participantsCount ? $seedB : null,
                'parent_pos'   => intdiv($i, 2),
                'parent_slot'  => $i % 2,
                'loser_to'     => null,
            ];
        }

        // Rounds 2..N (winners only)
        for ($r = 2; $r <= $rounds; $r++) {
            $count = (int) ($size / (2 ** $r));
            for ($i = 0; $i < $count; $i++) {
                $isFinal = ($r === $rounds);
                $matches[] = [
                    'round'        => $r,
                    'position'     => $i,
                    'bracket_side' => $isFinal ? 'final' : 'upper',
                    'seed_a'       => null,
                    'seed_b'       => null,
                    'parent_pos'   => $isFinal ? null : intdiv($i, 2),
                    'parent_slot'  => $isFinal ? null : ($i % 2),
                    'loser_to'     => null,
                ];
            }
        }

        // Optional 3rd place match (semifinal losers play)
        if ($thirdPlaceMatch && $rounds >= 2) {
            $matches[] = [
                'round'        => $rounds, // same round as final, but separate side
                'position'     => 1,        // distinct position from final (which is pos 0)
                'bracket_side' => 'third_place',
                'seed_a'       => null,
                'seed_b'       => null,
                'parent_pos'   => null,
                'parent_slot'  => null,
                'loser_to'     => null,
            ];
        }

        return $matches;
    }

    /**
     * Round-robin: kazdy z kazdym. N*(N-1)/2 meczow w (N-1) rundach (parzyste N)
     * lub N rundach (nieparzyste N — gracz "bye" w jednej rundzie).
     *
     * Circle method: gracz 1 stoi, pozostali rotuja zgodnie z ruchem zegara.
     */
    public static function roundRobin(int $participantsCount): array
    {
        if ($participantsCount < 2) {
            return [];
        }

        $n = $participantsCount;
        $hasBye = ($n % 2 === 1);
        if ($hasBye) {
            $n++;
        }
        $rounds = $n - 1;
        $matchesPerRound = $n / 2;

        // Build initial circle (seeds 1..n; bye = $n if hasBye)
        $circle = range(1, $n);

        $matches = [];
        for ($r = 1; $r <= $rounds; $r++) {
            $pos = 0;
            for ($m = 0; $m < $matchesPerRound; $m++) {
                $a = $circle[$m];
                $b = $circle[$n - 1 - $m];
                $isBye = $hasBye && ($a > $participantsCount || $b > $participantsCount);

                $matches[] = [
                    'round'        => $r,
                    'position'     => $pos++,
                    'bracket_side' => 'upper',
                    'seed_a'       => $a <= $participantsCount ? $a : null,
                    'seed_b'       => $b <= $participantsCount ? $b : null,
                    'parent_pos'   => null,
                    'parent_slot'  => null,
                    'loser_to'     => null,
                    'is_bye'       => $isBye,
                ];
            }
            // Rotate: fix circle[0], rotate others by 1 position clockwise
            $first = $circle[0];
            $rest  = array_slice($circle, 1);
            $last  = array_pop($rest);
            array_unshift($rest, $last);
            $circle = array_merge([$first], $rest);
        }

        return $matches;
    }

    /**
     * Double-elimination — minimalna implementacja (stub).
     * Generuje winners bracket (jak SE), losers bracket i grand final.
     * Pelny advancement DE jest skomplikowany — to bazowa struktura.
     *
     * Schemat losers-bracket dla size=N (potega 2):
     *   - LB ma 2*(log2(N)) - 1 rund
     *   - LB-R1 hosting losers z WB-R1 (parami)
     *   - LB-R(2k) hostuje losers z WB-R(k+1) vs LB-R(2k-1) winners
     *   - LB-R(2k+1) tylko LB-internal: LB-R(2k) winners parami
     *   - Grand final: WB winner vs LB winner (bracket reset opcjonalny)
     */
    public static function doubleElimination(int $participantsCount): array
    {
        if ($participantsCount < 2) {
            return [];
        }

        // Winners bracket = SE matches (without 3rd place)
        $wb = self::singleElimination($participantsCount, false);

        $size = self::bracketSize($participantsCount);
        $wbRounds = self::roundsForSE($participantsCount);

        // Losers bracket structure:
        // For WB of size N=2^k, LB has 2k-1 rounds.
        // We index LB rounds R1..R(2k-1), each with specific match count.
        $lbRounds = max(0, 2 * $wbRounds - 1);
        $lb = [];
        $matchesInLbRound = []; // round_num => count

        // LB round structure: alternates between "drop-in" (receives WB losers + LB carries)
        // and "carry-only" (just LB internal).
        // R1: size/4 matches (pairs of WB-R1 losers — only when WB-R1 has size/2 matches)
        // R2: size/4 (R1 winners + WB-R2 losers)
        // R3: size/8 (R2 winners pair up)
        // R4: size/8 (R3 winners + WB-R3 losers)
        // ...
        // Last LB round: 1 match (LB final)
        $cur = (int) ($size / 4);
        for ($r = 1; $r <= $lbRounds; $r++) {
            $matchesInLbRound[$r] = max(1, $cur);
            // Every 2 rounds halves
            if ($r % 2 === 0 && $cur > 1) {
                $cur = intdiv($cur, 2);
            }
        }
        // Special-case tiny brackets where size <= 2
        if ($size <= 2) {
            $matchesInLbRound = [];
            $lbRounds = 0;
        }

        foreach ($matchesInLbRound as $r => $cnt) {
            for ($i = 0; $i < $cnt; $i++) {
                $lb[] = [
                    'round'        => $r,
                    'position'     => $i,
                    'bracket_side' => 'lower',
                    'seed_a'       => null,
                    'seed_b'       => null,
                    'parent_pos'   => null,   // computed at advance time
                    'parent_slot'  => null,
                    'loser_to'     => null,
                ];
            }
        }

        // Grand final (and optional bracket reset)
        $gf = [[
            'round'        => $wbRounds + 1, // virtual round after WB
            'position'     => 0,
            'bracket_side' => 'final',
            'seed_a'       => null,
            'seed_b'       => null,
            'parent_pos'   => null,
            'parent_slot'  => null,
            'loser_to'     => null,
        ]];

        return array_merge($wb, $lb, $gf);
    }

    /**
     * Przydziela seedy uczestnikom wedlug metody.
     *
     * @param array<int, array{id:int, member_id?:int}> $participants Lista uczestnikow (kazdy ma 'id')
     * @param string $method 'random'|'manual'|'ranking'|'snake'
     * @param array<int,int|float> $rankings  Mapowanie participant_id => ranking score (dla 'ranking')
     * @return array<int, array{participant_id:int, seed_number:int}>
     */
    public static function assignSeeds(array $participants, string $method = 'random', array $rankings = []): array
    {
        if (empty($participants)) {
            return [];
        }

        $ids = array_map(fn($p) => (int)($p['id'] ?? $p['participant_id'] ?? 0), $participants);
        $ids = array_values(array_filter($ids, fn($x) => $x > 0));

        switch ($method) {
            case 'manual':
                // Caller must have set seeds explicitly; just preserve given order
                break;

            case 'ranking':
                // Sort by ranking score desc (higher score = better = lower seed#)
                usort($ids, function ($a, $b) use ($rankings) {
                    $sa = (float) ($rankings[$a] ?? 0);
                    $sb = (float) ($rankings[$b] ?? 0);
                    return $sb <=> $sa;
                });
                break;

            case 'snake':
                // Used for groups/divisions — pair top with bottom alternately.
                // For single-line seed assignment, same as ranking but interleave.
                usort($ids, function ($a, $b) use ($rankings) {
                    $sa = (float) ($rankings[$a] ?? 0);
                    $sb = (float) ($rankings[$b] ?? 0);
                    return $sb <=> $sa;
                });
                $top = array_slice($ids, 0, intdiv(count($ids), 2));
                $bot = array_reverse(array_slice($ids, intdiv(count($ids), 2)));
                $interleaved = [];
                $count = max(count($top), count($bot));
                for ($i = 0; $i < $count; $i++) {
                    if (isset($top[$i])) $interleaved[] = $top[$i];
                    if (isset($bot[$i])) $interleaved[] = $bot[$i];
                }
                $ids = $interleaved;
                break;

            case 'random':
            default:
                // Stable random — sort by random key
                shuffle($ids);
                break;
        }

        $out = [];
        $seed = 1;
        foreach ($ids as $pid) {
            $out[] = [
                'participant_id' => $pid,
                'seed_number'    => $seed++,
            ];
        }
        return $out;
    }
}
