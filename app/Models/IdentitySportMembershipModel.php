<?php

namespace App\Models;

/**
 * identity_sport_memberships — krzyzowa tabela laczaca tożsamosc zawodnika
 * (member_identities) z konkretna sekcja sportowa w klubie. Jeden zawodnik
 * moze nalezec do wielu klubow i wielu sportow rownoczesnie; ten model
 * obsluguje wyszukiwanie tych powiazan dla portalu zawodnika.
 */
class IdentitySportMembershipModel extends BaseModel
{
    protected string $table = 'identity_sport_memberships';

    /**
     * Zwraca wszystkie aktywne przynaleznosci dla danej tozsamosci,
     * z dolaczonymi nazwami klubow i sportow oraz iconka sportu.
     * Sortuje primary first, potem alfabetycznie po klubie i sporcie.
     */
    public function forIdentity(int $identityId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ism.id, ism.identity_id, ism.club_id, ism.sport_key, ism.member_id,
                    ism.role, ism.is_primary, ism.joined_at, ism.left_at,
                    c.name      AS club_name,
                    c.short_name AS club_short_name,
                    s.id        AS sport_id,
                    s.name      AS sport_name,
                    s.icon      AS sport_icon,
                    s.color     AS sport_color
             FROM identity_sport_memberships ism
             JOIN clubs c ON c.id = ism.club_id
             LEFT JOIN sports s ON s.`key` = ism.sport_key
             WHERE ism.identity_id = ?
               AND ism.left_at IS NULL
             ORDER BY ism.is_primary DESC, c.name, s.name"
        );
        $stmt->execute([$identityId]);
        return $stmt->fetchAll();
    }

    /**
     * Pojedyncza przynaleznosc po id (z taka sama struktura jak forIdentity).
     * Zwraca null jesli nie istnieje lub jesli left_at jest ustawiona.
     */
    public function findActive(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ism.*, c.name AS club_name, s.name AS sport_name,
                    s.icon AS sport_icon, s.color AS sport_color, s.id AS sport_id
             FROM identity_sport_memberships ism
             JOIN clubs c ON c.id = ism.club_id
             LEFT JOIN sports s ON s.`key` = ism.sport_key
             WHERE ism.id = ? AND ism.left_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Domyslna (primary) przynaleznosc dla tozsamosci, jesli wskazana.
     * Uzywana przy pierwszym logowaniu portalu zanim user wybierze sekcje.
     */
    public function primaryFor(int $identityId): ?array
    {
        $list = $this->forIdentity($identityId);
        foreach ($list as $row) {
            if ((int)$row['is_primary'] === 1) return $row;
        }
        return $list[0] ?? null;
    }
}
