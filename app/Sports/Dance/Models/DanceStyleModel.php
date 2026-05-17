<?php

namespace App\Sports\Dance\Models;

use App\Helpers\ClubContext;
use App\Models\ClubScopedModel;

/**
 * Katalog stylow tanca — globalne (club_id IS NULL) + klubowe (klubowe wlasne).
 */
class DanceStyleModel extends ClubScopedModel
{
    protected string $table = 'sport_dance_styles';

    public const CATEGORIES = [
        'ballroom'     => 'Standardowe (Ballroom)',
        'latin'        => 'Latynoamerykanskie (Latin)',
        'street'       => 'Uliczne (Street)',
        'contemporary' => 'Wspolczesne',
        'folk'         => 'Ludowe',
        'other'        => 'Inne',
    ];

    public const LEVELS = [
        'beginner'     => 'Poczatkujacy',
        'intermediate' => 'Sredniozaawansowany',
        'advanced'     => 'Zaawansowany',
        'professional' => 'Profesjonalny',
    ];

    public function listAvailableForClub(): array
    {
        $clubId = ClubContext::current();
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
             WHERE (club_id IS NULL OR club_id = ?)
               AND active = 1
             ORDER BY (club_id IS NULL) ASC, display_name ASC"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function findByCode(string $code): ?array
    {
        $clubId = ClubContext::current();
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
             WHERE style_code = ?
               AND (club_id IS NULL OR club_id = ?)
             ORDER BY (club_id IS NULL) ASC
             LIMIT 1"
        );
        $stmt->execute([$code, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function addClubStyle(array $data): int
    {
        $code = strtolower(trim($data['style_code'] ?? ''));
        $name = trim($data['display_name'] ?? '');
        $cat  = array_key_exists($data['category'] ?? '', self::CATEGORIES) ? $data['category'] : 'other';
        return $this->insert([
            'style_code'   => $code,
            'display_name' => $name,
            'category'     => $cat,
            'active'       => 1,
        ]);
    }

    public function deactivate(int $id): bool
    {
        return $this->update($id, ['active' => 0]);
    }
}
