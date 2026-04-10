<?php

namespace App\Models;

use App\Helpers\ClubContext;

/**
 * Abstrakcyjna klasa bazowa dla modeli powiązanych z klubem.
 *
 * Automatycznie filtruje zapytania po club_id z ClubContext.
 * Automatycznie dodaje club_id przy insercie.
 * Super admin może wyłączyć scope przez withoutScope().
 */
abstract class ClubScopedModel extends BaseModel
{
    private bool $scopeEnabled = true;

    protected function clubId(): ?int
    {
        if (!$this->scopeEnabled) {
            return null;
        }
        return ClubContext::current();
    }

    public function withoutScope(): static
    {
        $this->scopeEnabled = false;
        return $this;
    }

    public function withScope(): static
    {
        $this->scopeEnabled = true;
        return $this;
    }

    public function findById(int $id): ?array
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            return parent::findById($id);
        }
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE id = ? AND club_id = ?"
        );
        $stmt->execute([$id, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findAll(string $orderBy = 'id', string $dir = 'ASC'): array
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            return parent::findAll($orderBy, $dir);
        }
        $dir     = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $orderBy = preg_replace('/[^a-zA-Z0-9_]/', '', $orderBy);
        $stmt    = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE club_id = ? ORDER BY `{$orderBy}` {$dir}"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function delete(int $id): bool
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            return parent::delete($id);
        }
        $stmt = $this->db->prepare(
            "DELETE FROM `{$this->table}` WHERE id = ? AND club_id = ?"
        );
        return $stmt->execute([$id, $clubId]);
    }

    public function count(): int
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            return parent::count();
        }
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `{$this->table}` WHERE club_id = ?"
        );
        $stmt->execute([$clubId]);
        return (int)$stmt->fetchColumn();
    }

    public function insert(array $data): int
    {
        $clubId = $this->clubId();
        if ($clubId !== null && !isset($data['club_id'])) {
            $data['club_id'] = $clubId;
        }
        return parent::insert($data);
    }

    protected function clubWhere(): string
    {
        return $this->clubId() !== null ? ' AND club_id = ?' : '';
    }

    protected function clubParams(): array
    {
        $id = $this->clubId();
        return $id !== null ? [$id] : [];
    }
}
