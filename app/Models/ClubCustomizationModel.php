<?php

namespace App\Models;

use App\Helpers\ClubContext;

class ClubCustomizationModel extends BaseModel
{
    protected string $table = 'club_customization';

    public function findForClub(int $clubId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `club_customization` WHERE club_id = ?");
        $stmt->execute([$clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function ensureExists(int $clubId): void
    {
        $existing = $this->findForClub($clubId);
        if ($existing === null) {
            $stmt = $this->db->prepare("INSERT INTO `club_customization` (club_id) VALUES (?)");
            $stmt->execute([$clubId]);
        }
    }

    public function upsert(int $clubId, array $data): void
    {
        $this->ensureExists($clubId);
        if (empty($data)) return;
        $set  = implode(' = ?, ', array_map(fn($c) => "`{$c}`", array_keys($data))) . ' = ?';
        $stmt = $this->db->prepare("UPDATE `club_customization` SET {$set} WHERE club_id = ?");
        $stmt->execute([...array_values($data), $clubId]);
    }

    public static function getForCurrentClub(): array
    {
        $clubId = ClubContext::current();
        if ($clubId === null) {
            return self::defaults();
        }
        $row = (new self())->findForClub($clubId);
        return $row ?: self::defaults();
    }

    public static function defaults(): array
    {
        return [
            'club_id'               => null,
            'logo_path'             => null,
            'logo_alt_path'         => null,
            'logo_dark_path'        => null,
            'primary_color'         => '#0d6efd',
            'navbar_bg'             => '#212529',
            'accent_color'          => '#198754',
            'custom_css'            => null,
            'custom_css_updated_at' => null,
            'favicon_path'          => null,
            'email_header_html'     => null,
            'email_from_name'       => null,
            'sms_sender_id'         => null,
            'subdomain'             => null,
            'motto'                 => null,
        ];
    }
}
