<?php

namespace App\Models;

/**
 * Konfiguracja workflow onboardingu czlonka per klub.
 *
 * Pozwala administratorowi klubu okreslic:
 *  - ktore standardowo opcjonalne pola sa wymagane
 *  - jakie zgody nalezy zebrac (RODO, regulamin, marketing, custom)
 *  - limity wieku i wiek do zgody rodzica
 *  - auto-assign sportu/skladki po dodaniu czlonka
 *  - czy wysylac welcome email i wg ktorego szablonu
 *  - dodatkowe (custom) pola formularza
 *
 * Brak konfiguracji = wszystkie ustawienia domyslne (BC-safe).
 */
class ClubOnboardingConfigModel extends ClubScopedModel
{
    protected string $table = 'club_onboarding_config';

    /**
     * Pobierz konfiguracje dla biezacego klubu (lub podanego),
     * z merge z defaultami. Zawsze zwraca tablice (defaults gdy brak rekordu).
     */
    public function forClub(?int $clubId = null): array
    {
        $clubId = $clubId ?? $this->clubId();
        $defaults = self::defaults();
        if ($clubId === null) return $defaults;

        $stmt = $this->db->prepare(
            "SELECT * FROM `club_onboarding_config` WHERE club_id = ? LIMIT 1"
        );
        $stmt->execute([$clubId]);
        $row = $stmt->fetch();
        if (!$row) return $defaults;

        // Decode JSON kolumn
        $row['custom_consents'] = self::decodeJson($row['custom_consents'] ?? null);
        $row['custom_fields']   = self::decodeJson($row['custom_fields'] ?? null);

        return array_merge($defaults, $row);
    }

    /** Wartosci domyslne uzywane gdy klub nie ma swojej konfiguracji. */
    public static function defaults(): array
    {
        return [
            'id'                                => null,
            'club_id'                           => null,
            'require_pesel'                     => 0,
            'require_address'                   => 0,
            'require_emergency_contact'         => 0,
            'require_medical_consent'           => 0,
            'require_photo'                     => 0,
            'require_parent_data_for_minors'    => 1,
            'custom_consents'                   => [],
            'auto_assign_sport_id'              => null,
            'auto_assign_fee_rate_id'           => null,
            'auto_send_welcome_email'           => 1,
            'welcome_email_template'            => null,
            'min_age_years'                     => null,
            'max_age_years'                     => null,
            'require_parent_consent_under_age'  => 18,
            'custom_fields'                     => [],
        ];
    }

    /**
     * Zapisz konfiguracje dla biezacego klubu (INSERT lub UPDATE).
     * Dane JSON (custom_consents, custom_fields) ocekiwane jako tablice.
     */
    public function upsert(array $data): bool
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            throw new \RuntimeException('Cannot upsert onboarding config without club context');
        }

        // Sanity coercion
        $boolCols = [
            'require_pesel', 'require_address', 'require_emergency_contact',
            'require_medical_consent', 'require_photo', 'require_parent_data_for_minors',
            'auto_send_welcome_email',
        ];
        foreach ($boolCols as $c) {
            $data[$c] = !empty($data[$c]) ? 1 : 0;
        }

        $intCols = ['auto_assign_sport_id', 'auto_assign_fee_rate_id', 'min_age_years', 'max_age_years'];
        foreach ($intCols as $c) {
            $data[$c] = isset($data[$c]) && $data[$c] !== '' ? (int)$data[$c] : null;
        }

        $data['require_parent_consent_under_age'] = isset($data['require_parent_consent_under_age'])
            ? max(0, min(99, (int)$data['require_parent_consent_under_age']))
            : 18;

        $data['welcome_email_template'] = isset($data['welcome_email_template']) && $data['welcome_email_template'] !== ''
            ? substr((string)$data['welcome_email_template'], 0, 80)
            : null;

        // JSON cols — accept array or JSON string
        $data['custom_consents'] = self::encodeJson($data['custom_consents'] ?? []);
        $data['custom_fields']   = self::encodeJson($data['custom_fields'] ?? []);

        $cols = [
            'club_id',
            'require_pesel', 'require_address', 'require_emergency_contact',
            'require_medical_consent', 'require_photo', 'require_parent_data_for_minors',
            'custom_consents',
            'auto_assign_sport_id', 'auto_assign_fee_rate_id',
            'auto_send_welcome_email', 'welcome_email_template',
            'min_age_years', 'max_age_years', 'require_parent_consent_under_age',
            'custom_fields',
        ];

        $values = [$clubId];
        foreach (array_slice($cols, 1) as $c) {
            $values[] = $data[$c] ?? null;
        }

        $placeholders = implode(', ', array_fill(0, count($cols), '?'));
        $updateParts  = [];
        foreach (array_slice($cols, 1) as $c) {
            $updateParts[] = "`{$c}` = VALUES(`{$c}`)";
        }
        $colList = implode(', ', array_map(fn($c) => "`{$c}`", $cols));

        $sql = "INSERT INTO `club_onboarding_config` ({$colList}) VALUES ({$placeholders})
                ON DUPLICATE KEY UPDATE " . implode(', ', $updateParts);

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    /** Custom fields jako array obiektow. */
    public function customFieldsArray(?int $clubId = null): array
    {
        $cfg = $this->forClub($clubId);
        $fields = $cfg['custom_fields'] ?? [];
        return is_array($fields) ? $fields : [];
    }

    /** Custom consents jako array obiektow. */
    public function customConsentsArray(?int $clubId = null): array
    {
        $cfg = $this->forClub($clubId);
        $c = $cfg['custom_consents'] ?? [];
        return is_array($c) ? $c : [];
    }

    /** Zapis pojedynczego custom field value dla czlonka. */
    public function saveMemberFieldValue(int $memberId, string $key, ?string $value): void
    {
        $clubId = $this->clubId();
        if ($clubId === null) return;
        $stmt = $this->db->prepare(
            "INSERT INTO `member_custom_field_values` (club_id, member_id, field_key, field_value)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE field_value = VALUES(field_value)"
        );
        $stmt->execute([$clubId, $memberId, $key, $value]);
    }

    /** Log akceptacji zgody (RODO audit trail). */
    public function logConsent(int $memberId, string $consentKey, ?string $version = null, ?string $ip = null): void
    {
        $clubId = $this->clubId();
        if ($clubId === null) return;
        $stmt = $this->db->prepare(
            "INSERT INTO `member_consent_acceptances` (club_id, member_id, consent_key, consent_version, accepted_ip)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$clubId, $memberId, $consentKey, $version, $ip]);
    }

    private static function decodeJson(?string $json): array
    {
        if ($json === null || $json === '') return [];
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Zwraca JSON string lub null. Akceptuje array | string JSON.
     * Rzuca InvalidArgumentException dla nieparsowalnego stringa.
     */
    private static function encodeJson(mixed $value): ?string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
            }
            return $value;
        }
        return null;
    }
}
