<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Bracket\BracketGenerator;
use App\Helpers\ClubContext;

/**
 * Model do zarzadzania tournament_brackets + tournament_seeds + generowania
 * meczow w `tournament_matches`.
 *
 * Multi-tenant: wszystkie operacje sa scope'owane do current club. Probuje
 * dostepu cross-club skutkuja brakiem zmian (silent fail) — zewn. kod nie
 * powinien tego napotykac (controller waliduje przed wywolaniem).
 */
class TournamentBracketModel extends ClubScopedModel
{
    protected string $table = 'tournament_brackets';

    /**
     * Pobierz konfiguracje drabinki dla turnieju (lub null).
     */
    public function configFor(int $tournamentId): ?array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT b.* FROM tournament_brackets b
             JOIN tournaments t ON t.id = b.tournament_id
             WHERE b.tournament_id = ?"
            . ($clubId !== null ? " AND t.club_id = ?" : "")
        );
        $params = $clubId !== null ? [$tournamentId, $clubId] : [$tournamentId];
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Zapisz/zaktualizuj konfiguracje drabinki.
     */
    public function upsertConfig(int $tournamentId, array $data): void
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            throw new \RuntimeException('Brak kontekstu klubu.');
        }

        // Walidacja: tournament nalezy do current club
        $own = $this->db->prepare(
            "SELECT id FROM tournaments WHERE id = ? AND club_id = ?"
        );
        $own->execute([$tournamentId, $clubId]);
        if (!$own->fetch()) {
            throw new \RuntimeException('Turniej nie nalezy do biezacego klubu.');
        }

        $allowedTypes  = ['single_elimination','double_elimination','round_robin','swiss'];
        $allowedSeeds  = ['random','manual','ranking','snake'];

        $type = in_array($data['bracket_type'] ?? '', $allowedTypes, true)
            ? $data['bracket_type'] : 'single_elimination';
        $seed = in_array($data['seed_method'] ?? '', $allowedSeeds, true)
            ? $data['seed_method'] : 'random';
        $tpm  = !empty($data['third_place_match']) ? 1 : 0;
        $rounds = isset($data['rounds_count']) && $data['rounds_count'] !== ''
            ? (int)$data['rounds_count'] : null;

        // INSERT ... ON DUPLICATE KEY UPDATE (uniq na tournament_id)
        $sql = "INSERT INTO tournament_brackets
                    (club_id, tournament_id, bracket_type, seed_method, third_place_match, rounds_count)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    bracket_type = VALUES(bracket_type),
                    seed_method  = VALUES(seed_method),
                    third_place_match = VALUES(third_place_match),
                    rounds_count = VALUES(rounds_count)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId, $tournamentId, $type, $seed, $tpm, $rounds]);
    }

    /**
     * Zablokuj drabinke (po starcie meczow).
     */
    public function lock(int $tournamentId): void
    {
        $clubId = $this->clubId();
        $sql = "UPDATE tournament_brackets b
                JOIN tournaments t ON t.id = b.tournament_id
                SET b.is_locked = 1
                WHERE b.tournament_id = ?"
                . ($clubId !== null ? " AND t.club_id = ?" : "");
        $params = $clubId !== null ? [$tournamentId, $clubId] : [$tournamentId];
        $this->db->prepare($sql)->execute($params);
    }

    /**
     * Zapisz seedy uczestnikom turnieju (replace all).
     *
     * @param array<int, array{participant_id:int, seed_number:int, bracket_side?:string}> $seeds
     */
    public function saveSeeds(int $tournamentId, array $seeds): void
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            throw new \RuntimeException('Brak kontekstu klubu.');
        }

        // Verify tournament ownership
        $own = $this->db->prepare("SELECT id FROM tournaments WHERE id = ? AND club_id = ?");
        $own->execute([$tournamentId, $clubId]);
        if (!$own->fetch()) {
            throw new \RuntimeException('Turniej nie nalezy do biezacego klubu.');
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM tournament_seeds WHERE tournament_id = ?")
                     ->execute([$tournamentId]);

            $ins = $this->db->prepare(
                "INSERT INTO tournament_seeds
                    (club_id, tournament_id, participant_id, seed_number, bracket_side)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $usedSeeds = [];
            $usedParts = [];
            foreach ($seeds as $s) {
                $pid  = (int)($s['participant_id'] ?? 0);
                $num  = (int)($s['seed_number'] ?? 0);
                $side = in_array(($s['bracket_side'] ?? 'upper'), ['upper','lower'], true)
                    ? $s['bracket_side'] : 'upper';
                if ($pid <= 0 || $num <= 0) continue;
                if (isset($usedSeeds[$num]) || isset($usedParts[$pid])) {
                    throw new \RuntimeException("Duplikat seed#{$num} lub participant#{$pid}");
                }
                $usedSeeds[$num] = true;
                $usedParts[$pid] = true;
                $ins->execute([$clubId, $tournamentId, $pid, $num, $side]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Pobierz seedy + participant_id + member info.
     */
    public function seedsFor(int $tournamentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, tp.member_id, m.first_name, m.last_name, m.member_number
             FROM tournament_seeds s
             JOIN tournament_participants tp ON tp.id = s.participant_id
             LEFT JOIN members m ON m.id = tp.member_id
             WHERE s.tournament_id = ?
             ORDER BY s.seed_number ASC"
        );
        $stmt->execute([$tournamentId]);
        return $stmt->fetchAll();
    }

    /**
     * Generuje mecze w `tournament_matches` zgodnie z konfiguracja drabinki + seedami.
     *
     * Wymaga: tournament_brackets row + tournament_seeds (jesli brak — seedy beda
     * przydzielone wg seed_method config'a).
     *
     * @return int Liczba utworzonych meczow.
     */
    public function generateMatches(int $tournamentId, bool $overwrite = false): int
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            throw new \RuntimeException('Brak kontekstu klubu.');
        }

        // Walidacja klubu turnieju
        $tStmt = $this->db->prepare("SELECT * FROM tournaments WHERE id = ? AND club_id = ?");
        $tStmt->execute([$tournamentId, $clubId]);
        $tournament = $tStmt->fetch();
        if (!$tournament) {
            throw new \RuntimeException('Turniej nie nalezy do biezacego klubu.');
        }

        $cfg = $this->configFor($tournamentId);
        if (!$cfg) {
            // domyslnie z tournament.format
            $cfg = [
                'bracket_type'      => $tournament['format'] ?? 'single_elimination',
                'seed_method'       => 'random',
                'third_place_match' => 0,
            ];
            $this->upsertConfig($tournamentId, $cfg);
        }

        // Czy juz sa mecze?
        $existing = $this->db->prepare("SELECT COUNT(*) FROM tournament_matches WHERE tournament_id = ?");
        $existing->execute([$tournamentId]);
        if ((int)$existing->fetchColumn() > 0 && !$overwrite) {
            throw new \RuntimeException('Mecze juz istnieja dla tego turnieju. Uzyj overwrite=true.');
        }

        // Participants
        $partsStmt = $this->db->prepare(
            "SELECT id, member_id FROM tournament_participants WHERE tournament_id = ?"
        );
        $partsStmt->execute([$tournamentId]);
        $participants = $partsStmt->fetchAll();
        if (count($participants) < 2) {
            throw new \RuntimeException('Za malo uczestnikow (min. 2).');
        }

        // Seeds: load existing or assign now
        $seedsStmt = $this->db->prepare(
            "SELECT participant_id, seed_number FROM tournament_seeds
             WHERE tournament_id = ? ORDER BY seed_number ASC"
        );
        $seedsStmt->execute([$tournamentId]);
        $seedsRows = $seedsStmt->fetchAll();

        if (empty($seedsRows) || count($seedsRows) !== count($participants)) {
            $assigned = BracketGenerator::assignSeeds($participants, $cfg['seed_method']);
            $this->saveSeeds($tournamentId, $assigned);
            $seedsStmt->execute([$tournamentId]);
            $seedsRows = $seedsStmt->fetchAll();
        }

        // Build seed# => member_id map
        $memberBySeed = [];
        $partById = [];
        foreach ($participants as $p) {
            $partById[(int)$p['id']] = (int)($p['member_id'] ?? 0);
        }
        foreach ($seedsRows as $s) {
            $memberBySeed[(int)$s['seed_number']] = $partById[(int)$s['participant_id']] ?? null;
        }

        $count = count($participants);

        // Generate bracket
        $brackets = match ($cfg['bracket_type']) {
            'round_robin'        => BracketGenerator::roundRobin($count),
            'double_elimination' => BracketGenerator::doubleElimination($count),
            default              => BracketGenerator::singleElimination($count, !empty($cfg['third_place_match'])),
        };

        // Transactional write
        $this->db->beginTransaction();
        try {
            if ($overwrite) {
                $this->db->prepare("DELETE FROM tournament_matches WHERE tournament_id = ?")
                         ->execute([$tournamentId]);
            }

            $matchNumber = 1;
            // First pass: insert all matches, capture id by (round, position, bracket_side)
            $idMap = [];
            $ins = $this->db->prepare(
                "INSERT INTO tournament_matches
                    (tournament_id, round, match_number, player1_id, player2_id,
                     bracket_position, bracket_side, is_bye)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );

            foreach ($brackets as $m) {
                $p1 = isset($m['seed_a']) && $m['seed_a'] !== null ? ($memberBySeed[$m['seed_a']] ?? null) : null;
                $p2 = isset($m['seed_b']) && $m['seed_b'] !== null ? ($memberBySeed[$m['seed_b']] ?? null) : null;
                $isBye = !empty($m['is_bye']) ? 1 : 0;
                // Single-elim implicit bye: jeden gracz null w R1
                if ($m['round'] === 1 && ($p1 === null xor $p2 === null)) {
                    $isBye = 1;
                }

                $ins->execute([
                    $tournamentId,
                    (int)$m['round'],
                    $matchNumber++,
                    $p1,
                    $p2,
                    (int)$m['position'],
                    $m['bracket_side'] ?? 'upper',
                    $isBye,
                ]);
                $newId = (int) $this->db->lastInsertId();
                $key = $this->matchKey((int)$m['round'], (int)$m['position'], (string)($m['bracket_side'] ?? 'upper'));
                $idMap[$key] = $newId;
            }

            // Second pass: link parent_match_id + slot_in_parent
            $upd = $this->db->prepare(
                "UPDATE tournament_matches SET parent_match_id = ?, slot_in_parent = ? WHERE id = ?"
            );

            foreach ($brackets as $m) {
                if ($m['parent_pos'] === null) continue;

                // Dla SE/DE-WB: parent jest w nastepnej rundzie po tej samej stronie (upper),
                // a final ma bracket_side='final' (mecz finalowy)
                $parentRound = (int)$m['round'] + 1;
                $parentSide  = $m['bracket_side'];

                // Special: ostatnia runda WB to 'final' (jedyny mecz)
                // Generator ustawil to dla rundy = roundsForSE.
                $parentKey = $this->matchKey($parentRound, (int)$m['parent_pos'], $parentSide);
                // Sprobuj jeszcze 'final' (gdy parent jest finalem)
                if (!isset($idMap[$parentKey])) {
                    $parentKey = $this->matchKey($parentRound, (int)$m['parent_pos'], 'final');
                }
                if (!isset($idMap[$parentKey])) continue;

                $childKey = $this->matchKey((int)$m['round'], (int)$m['position'], (string)$m['bracket_side']);
                if (!isset($idMap[$childKey])) continue;

                $upd->execute([$idMap[$parentKey], (int)$m['parent_slot'], $idMap[$childKey]]);
            }

            // Auto-advance BYE matches in R1 of SE/DE-WB
            $byeStmt = $this->db->prepare(
                "SELECT id, player1_id, player2_id, parent_match_id, slot_in_parent
                 FROM tournament_matches
                 WHERE tournament_id = ? AND is_bye = 1 AND winner_id IS NULL AND round = 1"
            );
            $byeStmt->execute([$tournamentId]);
            foreach ($byeStmt->fetchAll() as $bye) {
                $winner = $bye['player1_id'] ?? $bye['player2_id'];
                if (!$winner) continue;
                $this->db->prepare(
                    "UPDATE tournament_matches SET winner_id = ? WHERE id = ?"
                )->execute([(int)$winner, (int)$bye['id']]);
                if (!empty($bye['parent_match_id'])) {
                    $col = (int)$bye['slot_in_parent'] === 0 ? 'player1_id' : 'player2_id';
                    $this->db->prepare(
                        "UPDATE tournament_matches SET {$col} = ? WHERE id = ?"
                    )->execute([(int)$winner, (int)$bye['parent_match_id']]);
                }
            }

            // Tournament status -> active
            $this->db->prepare("UPDATE tournaments SET status = 'active' WHERE id = ? AND club_id = ?")
                     ->execute([$tournamentId, $clubId]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return count($brackets);
    }

    private function matchKey(int $round, int $position, string $side): string
    {
        return "{$round}|{$position}|{$side}";
    }
}
