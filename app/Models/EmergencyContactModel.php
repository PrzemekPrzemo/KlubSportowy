<?php

namespace App\Models;

use App\Models\Traits\EncryptsFields;

class EmergencyContactModel extends ClubScopedModel
{
    use EncryptsFields;

    protected string $table = 'member_emergency_contacts';

    protected static array $ENCRYPTED_FIELDS = ['phone', 'phone_alt', 'email', 'notes'];

    public function insert(array $data): int
    {
        return parent::insert($this->encryptFields($data));
    }

    public function update(int $id, array $data): bool
    {
        return parent::update($id, $this->encryptFields($data));
    }

    public function findById(int $id): ?array
    {
        return $this->decryptRow(parent::findById($id));
    }

    public static array $RELATIONSHIPS = [
        'rodzic'     => 'Rodzic',
        'małżonek'   => 'Małżonek/a',
        'rodzeństwo' => 'Rodzeństwo',
        'opiekun'    => 'Opiekun prawny',
        'partner'    => 'Partner/ka',
        'przyjaciel' => 'Przyjaciel',
        'inny'       => 'Inny',
    ];

    public function listForMember(int $memberId): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM member_emergency_contacts
             WHERE club_id = ? AND member_id = ?
             ORDER BY is_primary DESC, created_at ASC"
        );
        $stmt->execute([$clubId, $memberId]);
        return $this->decryptRows($stmt->fetchAll());
    }

    public function primaryForMember(int $memberId): ?array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM member_emergency_contacts
             WHERE club_id = ? AND member_id = ? AND is_primary = 1
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$clubId, $memberId]);
        return $this->decryptRow($stmt->fetch() ?: null);
    }

    /**
     * Gdy dodajemy primary — zdejmij flagę z pozostałych rekordów tego zawodnika.
     */
    public function setPrimary(int $memberId, int $contactId): void
    {
        $clubId = $this->clubId();
        $this->db->prepare(
            "UPDATE member_emergency_contacts SET is_primary = 0
             WHERE club_id = ? AND member_id = ? AND id != ?"
        )->execute([$clubId, $memberId, $contactId]);

        $this->db->prepare(
            "UPDATE member_emergency_contacts SET is_primary = 1
             WHERE club_id = ? AND member_id = ? AND id = ?"
        )->execute([$clubId, $memberId, $contactId]);
    }

    public function membersWithoutContact(): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT m.id, m.first_name, m.last_name, m.member_number
             FROM members m
             LEFT JOIN member_emergency_contacts ec
                    ON ec.member_id = m.id AND ec.club_id = m.club_id
             WHERE m.club_id = ?
               AND m.status = 'aktywny'
               AND ec.id IS NULL
             ORDER BY m.last_name"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
