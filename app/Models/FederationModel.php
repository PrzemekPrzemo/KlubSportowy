<?php

namespace App\Models;

class FederationModel extends BaseModel
{
    protected string $table = 'federations';

    public function listActive(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM `federations` WHERE is_active = 1 ORDER BY code"
        );
        return $stmt->fetchAll();
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `federations` WHERE code = ? LIMIT 1");
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
