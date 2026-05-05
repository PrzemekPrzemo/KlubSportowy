<?php

namespace App\Models;

/**
 * Reguły wysyłki przypomnień email/SMS — kiedy słać który template,
 * dla jakiego zdarzenia, na jaki kanał.
 *
 * Trigger events:
 *   - days_after_due       — N dni po terminie (overdue payment_dues)
 *   - days_before_expiry   — N dni przed wygaśnięciem (license, medical)
 *   - immediate            — natychmiast (np. po utworzeniu nowego dues)
 *
 * Pełna izolacja per klub przez ClubScopedModel.
 */
class NotificationRuleModel extends ClubScopedModel
{
    protected string $table = 'notification_rules';

    public static array $TRIGGER_EVENTS = [
        'days_after_due'     => 'N dni po terminie',
        'days_before_expiry' => 'N dni przed wygaśnięciem',
        'immediate'          => 'Natychmiast',
    ];

    public static array $CHANNELS = [
        'email' => 'E-mail',
        'sms'   => 'SMS',
        'both'  => 'E-mail + SMS',
    ];

    /**
     * Lista aktywnych reguł dla danego template_type.
     */
    public function activeRulesForTemplate(string $templateType): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM notification_rules
             WHERE club_id = ? AND template_type = ? AND is_active = 1
             ORDER BY days_offset"
        );
        $stmt->execute([$clubId, $templateType]);
        return $stmt->fetchAll();
    }

    /**
     * Wszystkie reguły klubu (do panelu admina).
     */
    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM notification_rules
             WHERE club_id = ?
             ORDER BY template_type, trigger_event, days_offset"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
