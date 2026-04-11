<?php

namespace App\Models;

class RolePermissionModel extends BaseModel
{
    protected string $table = 'role_permissions';

    /**
     * Zwraca listę modułów dostępnych (can_view = 1) dla danej roli i klubu.
     * Używa reguł per-klub jeśli istnieją, w przeciwnym razie globalnych (club_id NULL).
     */
    public function modulesForRole(string $role, ?int $clubId = null): array
    {
        $db = $this->db;

        // Najpierw per-klub
        if ($clubId !== null) {
            $stmt = $db->prepare(
                "SELECT DISTINCT module FROM role_permissions
                 WHERE role = ? AND can_view = 1 AND club_id = ?"
            );
            $stmt->execute([$role, $clubId]);
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                return array_column($rows, 'module');
            }
        }

        // Fallback globalny
        $stmt = $db->prepare(
            "SELECT DISTINCT module FROM role_permissions
             WHERE role = ? AND can_view = 1 AND club_id IS NULL"
        );
        $stmt->execute([$role]);
        return array_column($stmt->fetchAll(), 'module');
    }

    public function can(string $role, string $module, string $action = 'view', ?int $clubId = null): bool
    {
        $col = $action === 'edit' ? 'can_edit' : 'can_view';

        if ($clubId !== null) {
            $stmt = $this->db->prepare(
                "SELECT {$col} FROM role_permissions WHERE role = ? AND module = ? AND club_id = ?"
            );
            $stmt->execute([$role, $module, $clubId]);
            $val = $stmt->fetchColumn();
            if ($val !== false) return (bool)$val;
        }

        $stmt = $this->db->prepare(
            "SELECT {$col} FROM role_permissions WHERE role = ? AND module = ? AND club_id IS NULL"
        );
        $stmt->execute([$role, $module]);
        $val = $stmt->fetchColumn();
        return (bool)$val;
    }

    public function setAll(array $matrix, ?int $clubId = null): void
    {
        // $matrix = ['role' => ['module' => ['view' => 1, 'edit' => 1]]]
        $db = $this->db;
        foreach ($matrix as $role => $modules) {
            foreach ($modules as $module => $flags) {
                $canView = !empty($flags['view']) ? 1 : 0;
                $canEdit = !empty($flags['edit']) ? 1 : 0;
                if ($clubId === null) {
                    $stmt = $db->prepare(
                        "INSERT INTO role_permissions (club_id, role, module, can_view, can_edit)
                         VALUES (NULL, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_edit = VALUES(can_edit)"
                    );
                    $stmt->execute([$role, $module, $canView, $canEdit]);
                } else {
                    $stmt = $db->prepare(
                        "INSERT INTO role_permissions (club_id, role, module, can_view, can_edit)
                         VALUES (?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_edit = VALUES(can_edit)"
                    );
                    $stmt->execute([$clubId, $role, $module, $canView, $canEdit]);
                }
            }
        }
    }

    public function matrixForClub(?int $clubId = null): array
    {
        $sql = "SELECT role, module, can_view, can_edit FROM role_permissions WHERE club_id "
             . ($clubId === null ? 'IS NULL' : '= ' . (int)$clubId);
        return $this->db->query($sql)->fetchAll();
    }
}
