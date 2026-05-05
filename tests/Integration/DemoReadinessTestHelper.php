<?php

namespace Tests\Integration;

use App\Sports\Support\BaseSportArchetype;

/**
 * Test helper dla fazy A.3 — sprawdza czy sport po seedowaniu jest
 * "demo-ready", tzn. ma min 1 wpis w kazdym z kanonicznych typow encji
 * (athlete / event / result).
 *
 * Uzywane w fazach B/C/D/E/F/G/H przez kazdy DemoReadyTest per sport:
 *
 *   $this->assertSportDemoReady($archetype, $clubId);
 *
 * Helper sam wyciąga tabele z $archetype->tables() i wyśle COUNT(*)
 * z club_id filter — wymaga prawdziwej DB (skip-on-no-DB pattern).
 */
trait DemoReadinessTestHelper
{
    /**
     * Asercja ze sport jest demo-ready: kazda z table'i pluginu zawiera
     * min 1 wiersz dla danego klubu.
     *
     * @param BaseSportArchetype $archetype  konkretny archetyp pluginu sportu
     * @param int                $clubId     klub do sprawdzenia
     * @param int                $minPerTable minimalna liczba wierszy per tabela (default 1)
     */
    public function assertSportDemoReady(
        BaseSportArchetype $archetype,
        int $clubId,
        int $minPerTable = 1
    ): void {
        $db     = $this->requireDatabase();
        $tables = $archetype->tables();
        $this->assertNotEmpty(
            $tables,
            'Archetype ' . $archetype->key() . ' nie deklaruje tables() — popraw plugin'
        );

        $counts = [];
        foreach ($tables as $table) {
            // Sanity: tabela istnieje
            $exists = $db->query("SHOW TABLES LIKE " . $db->quote($table))->fetchColumn();
            if (!$exists) {
                $this->fail(
                    "Tabela '{$table}' wymagana dla {$archetype->key()} nie istnieje "
                    . '— sprawdz czy plugin migration zostal zaaplikowany w CI'
                );
            }

            // Count rows per club
            $stmt = $db->prepare("SELECT COUNT(*) FROM `{$table}` WHERE club_id = ?");
            $stmt->execute([$clubId]);
            $count = (int)$stmt->fetchColumn();
            $counts[$table] = $count;
            $this->assertGreaterThanOrEqual(
                $minPerTable,
                $count,
                "Demo nie jest gotowe dla {$archetype->key()}: tabela '{$table}' ma "
                . "{$count} wierszy dla klubu {$clubId} (oczekiwano min {$minPerTable})"
            );
        }
    }

    /**
     * Pomocnicza metoda dla testow demo: zwraca raport count'ow per tabela.
     * Useful do debugowania gdy assertSportDemoReady fails — pokaze ktora
     * tabela jest pusta.
     *
     * @return array<string, int>  table_name => row_count
     */
    public function getDemoCounts(BaseSportArchetype $archetype, int $clubId): array
    {
        $db = $this->requireDatabase();
        $out = [];
        foreach ($archetype->tables() as $table) {
            try {
                $stmt = $db->prepare("SELECT COUNT(*) FROM `{$table}` WHERE club_id = ?");
                $stmt->execute([$clubId]);
                $out[$table] = (int)$stmt->fetchColumn();
            } catch (\Throwable) {
                $out[$table] = -1; // table missing
            }
        }
        return $out;
    }
}
