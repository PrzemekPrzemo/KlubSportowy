<?php

namespace App\Helpers\Federations;

/**
 * DTO standardowych danych zawodnika przekazywanych do exporterów federacji.
 *
 * Każda federacja może wymagać innego podzbioru pól + sport-specific
 * extras (np. PZSS: license_class, PZPN: position; PZLA: konkurencje).
 * Surowy "raw" array ma być źródłem prawdy dla adapterów, które mapują na
 * format federacji.
 *
 * Konstrukcja przez MemberPayload::fromMemberRow(array $row) — adapter
 * sportu może dorzucić sport-specific pola do $extras.
 */
final class MemberPayload
{
    public function __construct(
        public readonly int     $memberId,
        public readonly int     $clubId,
        public readonly string  $firstName,
        public readonly string  $lastName,
        public readonly ?string $pesel        = null,
        public readonly ?string $birthDate    = null, // YYYY-MM-DD
        public readonly ?string $gender       = null, // 'M' / 'K'
        public readonly ?string $nationality  = 'PL',
        public readonly ?string $email        = null,
        public readonly ?string $phone        = null,
        public readonly ?string $addressStreet = null,
        public readonly ?string $addressCity   = null,
        public readonly ?string $addressPostal = null,
        public readonly ?string $licenseNumber = null,
        public readonly ?string $externalId    = null, // ID w systemie federacji (jeśli wcześniej zarejestrowany)
        /** Sport-specific extras (np. konkurencje PZLA, klasa PZSS, pozycja PZPN). */
        public readonly array   $extras        = [],
    ) {
    }

    /**
     * Stwórz payload z wiersza tabeli `members`.
     *
     * @param array $row  Pełny wiersz z `members` (jak z MemberModel::findById).
     * @param array $extras Sport-specific extras (license_class, position, …).
     */
    public static function fromMemberRow(array $row, array $extras = []): self
    {
        return new self(
            memberId:      (int)($row['id'] ?? 0),
            clubId:        (int)($row['club_id'] ?? 0),
            firstName:     (string)($row['first_name'] ?? ''),
            lastName:      (string)($row['last_name'] ?? ''),
            pesel:         $row['pesel'] ?? null,
            birthDate:     $row['birth_date'] ?? null,
            gender:        $row['gender'] ?? null,
            nationality:   $row['nationality'] ?? 'PL',
            email:         $row['email'] ?? null,
            phone:         $row['phone'] ?? null,
            addressStreet: $row['address_street'] ?? null,
            addressCity:   $row['address_city'] ?? null,
            addressPostal: $row['address_postal'] ?? null,
            licenseNumber: $extras['license_number'] ?? null,
            externalId:    $extras['external_id'] ?? null,
            extras:        $extras,
        );
    }

    public function toArray(): array
    {
        return [
            'member_id'      => $this->memberId,
            'club_id'        => $this->clubId,
            'first_name'     => $this->firstName,
            'last_name'      => $this->lastName,
            'pesel'          => $this->pesel,
            'birth_date'     => $this->birthDate,
            'gender'         => $this->gender,
            'nationality'    => $this->nationality,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'address_street' => $this->addressStreet,
            'address_city'   => $this->addressCity,
            'address_postal' => $this->addressPostal,
            'license_number' => $this->licenseNumber,
            'external_id'    => $this->externalId,
            'extras'         => $this->extras,
        ];
    }
}
