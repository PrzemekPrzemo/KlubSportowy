<?php

namespace App\Sports\Equestrian\Models;

use App\Models\ClubScopedModel;

/**
 * Zawody jezdzieckie. Hierarchia:
 *   competition → competition_classes → starts → results
 *
 * Schema z migracji 004_equestrian_competitions (Q.5).
 */
class EquestrianCompetitionModel extends ClubScopedModel
{
    protected string $table = 'equestrian_competitions';

    public static array $LEVELS = [
        'klubowe'    => 'Klubowe',
        'regionalne' => 'Regionalne',
        'krajowe'    => 'Krajowe',
        'CDN'        => 'CDN — krajowe (PZJ)',
        'CDI'        => 'CDI — międzynarodowe ujeżdżenie (FEI)',
        'CSO'        => 'CSO — krajowe skoki',
        'CSI'        => 'CSI — międzynarodowe skoki (FEI)',
        'CIC'        => 'CIC — krótki WKKW (FEI)',
        'CCI'        => 'CCI — długi WKKW (FEI)',
        'CEI'        => 'CEI — międzynarodowe rajdy (FEI)',
        'para'       => 'Para — parajeździectwo',
        'inne'       => 'Inne',
    ];

    public static array $STATUS = [
        'zaplanowane' => 'Zaplanowane',
        'w_trakcie'   => 'W trakcie',
        'zakonczone'  => 'Zakończone',
        'odwolane'    => 'Odwołane',
    ];

    public function listForClub(?string $status = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT c.*,
                       (SELECT COUNT(*) FROM equestrian_competition_classes cc WHERE cc.competition_id = c.id) AS class_count
                FROM equestrian_competitions c
                WHERE c.club_id = ?";
        $params = [$clubId];
        if ($status) {
            $sql .= " AND c.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY c.date_from DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findWithClasses(int $id): ?array
    {
        $row = $this->findById($id);
        if (!$row) return null;

        $stmt = $this->db->prepare(
            "SELECT cc.*,
                    (SELECT COUNT(*) FROM equestrian_starts s WHERE s.competition_class_id = cc.id) AS start_count
             FROM equestrian_competition_classes cc
             WHERE cc.competition_id = ?
             ORDER BY cc.class_no, cc.id"
        );
        $stmt->execute([$id]);
        $row['classes'] = $stmt->fetchAll();
        return $row;
    }
}
