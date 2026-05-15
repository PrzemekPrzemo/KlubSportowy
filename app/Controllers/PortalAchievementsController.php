<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\MemberAuth;
use App\Helpers\Session;
use PDO;

/**
 * Achievements widoczne dla zawodnika w portalu.
 *
 * - /portal/achievements         — moje odznaki + progres
 * - /portal/achievements/catalog — wszystkie dostepne dla mojego klubu
 * - /portal/achievements/toggle/:id — ukryj/pokaz odznake w profilu
 */
final class PortalAchievementsController extends BaseController
{
    public function index(): void
    {
        MemberAuth::requireLogin();
        $member = MemberAuth::member();
        $clubId = MemberAuth::clubId() ?? (int)($member['club_id'] ?? 0);
        $memberId = (int)($member['id'] ?? 0);

        $db = Database::pdo();

        $earned = $this->fetchEarned($db, $memberId);
        $catalog = $this->fetchCatalog($db, $clubId);
        $totalCount = count($catalog);
        $earnedCount = count($earned);
        $totalPoints = 0;
        foreach ($earned as $row) {
            $totalPoints += (int)($row['points'] ?? 0);
        }

        $byRarity = [
            'common' => 0, 'uncommon' => 0, 'rare' => 0, 'epic' => 0, 'legendary' => 0,
        ];
        foreach ($earned as $row) {
            $r = (string)($row['rarity'] ?? 'common');
            if (isset($byRarity[$r])) {
                $byRarity[$r]++;
            }
        }

        $this->view->setLayout('portal');
        $this->view->render('portal/achievements/index', [
            'title'       => 'Moje osiągnięcia',
            'member'      => $member,
            'earned'      => $earned,
            'totalCount'  => $totalCount,
            'earnedCount' => $earnedCount,
            'totalPoints' => $totalPoints,
            'byRarity'    => $byRarity,
        ]);
    }

    public function catalog(): void
    {
        MemberAuth::requireLogin();
        $member = MemberAuth::member();
        $clubId = MemberAuth::clubId() ?? (int)($member['club_id'] ?? 0);
        $memberId = (int)($member['id'] ?? 0);

        $db = Database::pdo();
        $catalog = $this->fetchCatalog($db, $clubId);

        // Mapa zdobytych: achievement_id => earned_at
        $stmt = $db->prepare(
            "SELECT achievement_id, earned_at FROM member_achievements WHERE member_id = ?"
        );
        $stmt->execute([$memberId]);
        $earnedMap = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $earnedMap[(int)$row['achievement_id']] = (string)$row['earned_at'];
        }

        // Grupuj po category dla UI.
        $grouped = [];
        foreach ($catalog as $a) {
            $cat = (string)($a['category'] ?? 'other');
            $a['earned_at'] = $earnedMap[(int)$a['id']] ?? null;
            $grouped[$cat][] = $a;
        }

        $this->view->setLayout('portal');
        $this->view->render('portal/achievements/catalog', [
            'title'    => 'Katalog odznak',
            'member'   => $member,
            'grouped'  => $grouped,
            'earnedMap' => $earnedMap,
        ]);
    }

    public function toggleVisibility(string $id): void
    {
        MemberAuth::requireLogin();
        Csrf::verify();
        $member = MemberAuth::member();
        $memberId = (int)($member['id'] ?? 0);

        $db = Database::pdo();
        $stmt = $db->prepare(
            "UPDATE member_achievements
             SET is_displayed = 1 - is_displayed
             WHERE id = ? AND member_id = ?"
        );
        $stmt->execute([(int)$id, $memberId]);
        Session::flash('success', 'Zaktualizowano widocznosc odznaki.');
        $this->redirect('portal/achievements');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchCatalog(PDO $db, int $clubId): array
    {
        $stmt = $db->prepare(
            "SELECT * FROM achievement_catalog
             WHERE is_active = 1
               AND (club_id IS NULL OR club_id = :club)
             ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute(['club' => $clubId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Override custom > global gdy ten sam code.
        $byCode = [];
        foreach ($rows as $r) {
            $code = (string)$r['code'];
            if (isset($byCode[$code]) && $byCode[$code]['club_id'] !== null) {
                continue;
            }
            $byCode[$code] = $r;
        }
        return array_values($byCode);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchEarned(PDO $db, int $memberId): array
    {
        $stmt = $db->prepare(
            "SELECT ma.id AS ma_id, ma.earned_at, ma.is_displayed, ma.context,
                    ac.id, ac.code, ac.name, ac.description, ac.category,
                    ac.icon, ac.rarity, ac.points
             FROM member_achievements ma
             JOIN achievement_catalog ac ON ac.id = ma.achievement_id
             WHERE ma.member_id = ?
             ORDER BY ma.earned_at DESC"
        );
        $stmt->execute([$memberId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
