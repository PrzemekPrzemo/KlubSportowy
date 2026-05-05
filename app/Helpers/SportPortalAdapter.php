<?php

namespace App\Helpers;

use App\Sports\Support\BaseSportArchetype;
use PDO;

/**
 * Generic adapter dla portalu zawodnika.
 *
 * Dla sportow ktore nie maja dedykowanego case-u w
 * MemberPortalController::sportDetail(), uzywa archetypu i introspekcji
 * INFORMATION_SCHEMA do auto-detekcji co pokazac:
 *
 * - Wszystkie tabele z `archetype.tables()` z kolumna member_id albo
 *   leader_id albo player1_id zawierajace tego zawodnika
 * - 10 ostatnich wierszy per tabela
 *
 * Zwraca dane gotowe do renderu w portal/sport_generic.php — kazda
 * tabela jako sekcja z naglowkiem (wyniki, pasy, transfery, ...).
 */
class SportPortalAdapter
{
    public function __construct(
        private readonly PDO $db,
    ) {
    }

    /**
     * @return array{title:string, sections:array<int,array{table:string,label:string,columns:string[],rows:array}>}
     */
    public function loadForMember(BaseSportArchetype $archetype, int $memberId, ?int $clubId = null): array
    {
        $key      = $archetype->key();
        $sections = [];

        foreach ($archetype->tables() as $table) {
            if (!$this->tableExists($table)) continue;
            $cols = $this->columns($table);

            // FK do zawodnika — sprawdz przyjazne aliasy.
            $fk = null;
            foreach (['member_id', 'leader_id', 'player1_id'] as $candidate) {
                if (isset($cols[$candidate])) { $fk = $candidate; break; }
            }
            if ($fk === null) continue;

            // SELECT * z limitem + filtrowanie po club_id gdy istnieje.
            $sql = "SELECT * FROM `{$table}` WHERE `{$fk}` = ?";
            $params = [$memberId];
            if (isset($cols['club_id']) && $clubId !== null) {
                $sql .= " AND `club_id` = ?";
                $params[] = $clubId;
            }
            $orderCol = $this->bestOrderColumn($cols);
            if ($orderCol !== null) {
                $sql .= " ORDER BY `{$orderCol}` DESC";
            }
            $sql .= " LIMIT 10";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            if (empty($rows)) {
                $sections[] = [
                    'table'   => $table,
                    'label'   => $this->humanizeTableLabel($table, $key),
                    'columns' => $this->displayColumns($cols),
                    'rows'    => [],
                ];
                continue;
            }

            $sections[] = [
                'table'   => $table,
                'label'   => $this->humanizeTableLabel($table, $key),
                'columns' => $this->displayColumns($cols),
                'rows'    => $rows,
            ];
        }

        return [
            'title'    => 'Mój profil — ' . ucfirst(str_replace('_', ' ', $key)),
            'sections' => $sections,
        ];
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $stmt->execute([$table]);
        return ((int)$stmt->fetchColumn()) > 0;
    }

    /** @return array<string,bool> col_name => true */
    private function columns(string $table): array
    {
        $stmt = $this->db->prepare(
            'SELECT column_name FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $stmt->execute([$table]);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $c) {
            $out[strtolower((string)$c)] = true;
        }
        return $out;
    }

    /**
     * Wybiera kolumne sortowania: preferuj date'y/timestamp'y,
     * fallback na id (DESC dla "ostatnio wstawione").
     */
    private function bestOrderColumn(array $cols): ?string
    {
        foreach ([
            'competition_date', 'event_date', 'match_date', 'race_date',
            'score_date', 'send_date', 'rating_date', 'tournament_date',
            'set_date', 'set_at', 'pr_date', 'awarded_at', 'created_at',
            'id',
        ] as $candidate) {
            if (isset($cols[$candidate])) return $candidate;
        }
        return null;
    }

    /**
     * Zwraca podzbiór sensownych kolumn do wyswietlenia w view (bez club_id,
     * member_id, *_id internalowych, created_at metadata).
     *
     * @return string[]
     */
    private function displayColumns(array $cols): array
    {
        $hidden = ['id','club_id','member_id','leader_id','player1_id','player2_id',
                   'follower_id','partnership_id','wod_id','route_id','boat_id',
                   'discipline_id','event_id','created_at','updated_at',
                   'created_by','set_by'];
        $out = [];
        foreach ($cols as $name => $_) {
            if (in_array($name, $hidden, true)) continue;
            $out[] = $name;
            if (count($out) >= 8) break; // max 8 kolumn — readability
        }
        return $out;
    }

    private function humanizeTableLabel(string $table, string $sportKey): string
    {
        // Strip prefix like "swimming_" / "ski_jump_" / "table_tennis_"
        $candidates = [$sportKey . '_', str_replace('_', '', $sportKey) . '_'];
        $stripped = $table;
        foreach ($candidates as $prefix) {
            if (str_starts_with($table, $prefix)) {
                $stripped = substr($table, strlen($prefix));
                break;
            }
        }
        // Special cases
        $map = [
            'results' => 'Wyniki',
            'belts'   => 'Pasy / stopnie',
            'fighters'=> 'Profil zawodnika',
            'records' => 'Rekordy',
            'rankings'=> 'Rankingi',
            'matches' => 'Mecze',
            'sends'   => 'Przejscia',
            'routes'  => 'Drogi',
            'wods'    => 'Workouty (WOD)',
            'scores'  => 'Wyniki WOD',
            'prs'     => 'Rekordy osobiste (PR)',
            'pairs'   => 'Pary',
            'partnerships' => 'Pary',
            'tournaments'  => 'Turnieje',
            'crew'    => 'Załoga',
            'boats'   => 'Łodzie',
            'races'   => 'Regaty',
            'licenses'=> 'Licencje',
            'ratings' => 'Rankingi ELO',
            'handicaps' => 'Handicap',
            'rounds'  => 'Rundy',
            'times'   => 'Czasy',
            'fencers' => 'Profil zawodnika',
            'medicals'=> 'Badania lekarskie',
            'starts'  => 'Starty',
            'horses'  => 'Konie',
            'riders'  => 'Riderzy',
            'horse_owners' => 'Właściciele koni',
            'competition_classes' => 'Klasy zawodów',
            'horse_health' => 'Zdrowie koni',
            'horse_training' => 'Treningi koni',
        ];
        return $map[$stripped] ?? ucfirst(str_replace('_', ' ', $stripped));
    }
}
