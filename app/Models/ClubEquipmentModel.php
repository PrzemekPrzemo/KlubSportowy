<?php

namespace App\Models;

class ClubEquipmentModel extends ClubScopedModel
{
    protected string $table = 'club_equipment_items';

    public static array $STATES = [
        'nowy'       => ['label' => 'Nowy',        'class' => 'success'],
        'dobry'      => ['label' => 'Dobry',       'class' => 'primary'],
        'używany'    => ['label' => 'Używany',     'class' => 'info'],
        'do_serwisu' => ['label' => 'Do serwisu',  'class' => 'warning'],
        'wycofany'   => ['label' => 'Wycofany',    'class' => 'secondary'],
    ];

    public function listForClub(?string $sportKey = null, ?string $state = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT cei.*,
                       (SELECT cea.id FROM club_equipment_assignments cea
                        WHERE cea.item_id = cei.id AND cea.returned_at IS NULL
                        ORDER BY cea.issued_at DESC LIMIT 1) AS active_assignment_id,
                       (SELECT CONCAT(m.last_name, ' ', m.first_name)
                        FROM club_equipment_assignments cea
                        JOIN members m ON m.id = cea.member_id
                        WHERE cea.item_id = cei.id AND cea.returned_at IS NULL
                        ORDER BY cea.issued_at DESC LIMIT 1) AS assigned_to
                FROM club_equipment_items cei
                WHERE cei.club_id = ?";
        $params = [$clubId];
        if ($sportKey !== null && $sportKey !== '') {
            $sql .= " AND cei.sport_key = ?";
            $params[] = $sportKey;
        }
        if ($state !== null && array_key_exists($state, self::$STATES)) {
            $sql .= " AND cei.state = ?";
            $params[] = $state;
        }
        $sql .= " ORDER BY cei.sport_key, cei.category, cei.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function availableCount(?string $sportKey = null): int
    {
        $clubId = $this->clubId();
        $sql = "SELECT COUNT(*) FROM club_equipment_items cei
                WHERE cei.club_id = ? AND cei.state NOT IN ('wycofany','do_serwisu')
                  AND NOT EXISTS (
                      SELECT 1 FROM club_equipment_assignments cea
                      WHERE cea.item_id = cei.id AND cea.returned_at IS NULL
                  )";
        $params = [$clubId];
        if ($sportKey !== null && $sportKey !== '') {
            $sql .= " AND cei.sport_key = ?";
            $params[] = $sportKey;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function assignmentHistory(int $itemId): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT cea.*, m.first_name, m.last_name, m.member_number
             FROM club_equipment_assignments cea
             JOIN members m ON m.id = cea.member_id
             WHERE cea.club_id = ? AND cea.item_id = ?
             ORDER BY cea.issued_at DESC"
        );
        $stmt->execute([$clubId, $itemId]);
        return $stmt->fetchAll();
    }

    public function assignToMember(int $itemId, int $memberId, ?string $notes = null, ?int $issuedByUser = null): int
    {
        $clubId = $this->clubId();

        // Ensure not currently assigned
        $check = $this->db->prepare(
            "SELECT id FROM club_equipment_assignments
             WHERE item_id = ? AND club_id = ? AND returned_at IS NULL LIMIT 1"
        );
        $check->execute([$itemId, $clubId]);
        if ($check->fetchColumn()) {
            return 0; // already assigned
        }

        $stmt = $this->db->prepare(
            "INSERT INTO club_equipment_assignments (club_id, item_id, member_id, issued_at, issued_by, issue_notes)
             VALUES (?, ?, ?, NOW(), ?, ?)"
        );
        $stmt->execute([$clubId, $itemId, $memberId, $issuedByUser, $notes]);
        return (int)$this->db->lastInsertId();
    }

    public function returnFromMember(int $assignmentId, ?string $notes = null, ?int $returnedByUser = null): bool
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "UPDATE club_equipment_assignments
             SET returned_at = NOW(), returned_by = ?, return_notes = ?
             WHERE id = ? AND club_id = ? AND returned_at IS NULL"
        );
        return $stmt->execute([$returnedByUser, $notes, $assignmentId, $clubId]);
    }

    public function findWithAssignment(int $itemId): ?array
    {
        $item = $this->findById($itemId);
        if (!$item) return null;
        $item['history'] = $this->assignmentHistory($itemId);
        return $item;
    }
}
