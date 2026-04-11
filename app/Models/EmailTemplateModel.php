<?php

namespace App\Models;

class EmailTemplateModel extends BaseModel
{
    protected string $table = 'email_templates';

    /** Pobierz szablon per-klub jeśli istnieje, w przeciwnym razie globalny. */
    public function resolve(string $templateType, ?int $clubId = null): ?array
    {
        if ($clubId !== null) {
            $stmt = $this->db->prepare(
                "SELECT * FROM email_templates
                 WHERE club_id = ? AND template_type = ? AND is_active = 1 LIMIT 1"
            );
            $stmt->execute([$clubId, $templateType]);
            $row = $stmt->fetch();
            if ($row) return $row;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM email_templates
             WHERE club_id IS NULL AND template_type = ? AND is_active = 1 LIMIT 1"
        );
        $stmt->execute([$templateType]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Zwraca listę wszystkich szablonów dla klubu (ze wszystkich typów). */
    public function listForClub(int $clubId): array
    {
        $sql = "SELECT et.*,
                       CASE WHEN et.club_id IS NULL THEN 'globalny' ELSE 'per-klub' END AS origin
                FROM email_templates et
                WHERE et.club_id IS NULL OR et.club_id = ?
                ORDER BY et.template_type, et.club_id IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    /** Renderuje szablon zastępując {placeholders} danymi z $vars. */
    public static function render(array $template, array $vars): array
    {
        $subject = $template['subject'];
        $body    = $template['body'];
        foreach ($vars as $k => $v) {
            $subject = str_replace('{' . $k . '}', (string)$v, $subject);
            $body    = str_replace('{' . $k . '}', (string)$v, $body);
        }
        return ['subject' => $subject, 'body' => $body];
    }
}
