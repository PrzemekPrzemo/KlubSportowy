<?php

namespace App\Sports\Equestrian\Models;

use App\Models\ClubScopedModel;

/**
 * Zawodnik jezdziecki z licencja PZJ. 1:1 z members (member_id UNIQUE).
 *
 * Klasy licencji PZJ:
 *   B   — podstawowa (kursy szkolne)
 *   S1-S4 — sportowa (poziomy startowe — najwyzszy S4)
 *   PRO — zawodowa (zarobkowa konkurencyjnosc)
 *   PARA — parajezdziectwo
 */
class EquestrianRiderModel extends ClubScopedModel
{
    protected string $table = 'equestrian_riders';

    public static array $LICENSE_CLASSES = [
        'B'    => 'B — podstawowa',
        'S1'   => 'S1 — sportowa I',
        'S2'   => 'S2 — sportowa II',
        'S3'   => 'S3 — sportowa III',
        'S4'   => 'S4 — sportowa IV',
        'PRO'  => 'PRO — zawodowa',
        'PARA' => 'PARA — parajezdziectwo',
    ];

    public static array $DISCIPLINES = [
        'dressage'  => 'Ujeżdżenie',
        'jumping'   => 'Skoki',
        'eventing'  => 'WKKW',
        'endurance' => 'Rajdy',
        'reining'   => 'Reining',
        'vaulting'  => 'Woltyżerka',
        'driving'   => 'Powożenie',
        'para'      => 'Parajeździectwo',
    ];

    public static array $STATUS = [
        'aktywny'    => 'Aktywny',
        'zawieszony' => 'Zawieszony',
        'wycofany'   => 'Wycofany',
    ];

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*,
                    m.first_name, m.last_name, m.member_number,
                    DATEDIFF(r.license_valid_until, CURDATE()) AS days_to_expiry
             FROM equestrian_riders r
             JOIN members m ON m.id = r.member_id
             WHERE r.club_id = ?
             ORDER BY r.status, m.last_name"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    /**
     * Zwraca riderow z licencjami wygasajacymi w ciagu N dni.
     * Klucz dla dashboard'u zarzadu klubu.
     */
    public function expiringSoon(int $days = 30): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*,
                    m.first_name, m.last_name, m.member_number,
                    DATEDIFF(r.license_valid_until, CURDATE()) AS days_to_expiry
             FROM equestrian_riders r
             JOIN members m ON m.id = r.member_id
             WHERE r.club_id = ?
               AND r.status = 'aktywny'
               AND r.license_valid_until IS NOT NULL
               AND r.license_valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY r.license_valid_until"
        );
        $stmt->execute([$this->clubId(), $days]);
        return $stmt->fetchAll();
    }

    /**
     * Pary [id => "Imie Nazwisko (S2)"] dla dropdown'u w starts/pairs.
     */
    public function options(): array
    {
        $rows = $this->listForClub();
        $out  = [];
        foreach ($rows as $r) {
            $label = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            if (!empty($r['license_class'])) {
                $label .= ' (' . $r['license_class'] . ')';
            }
            $out[(int)$r['id']] = $label;
        }
        return $out;
    }
}
