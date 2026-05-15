<?php

namespace App\Models;

/**
 * Wersjonowane dokumenty prawne (ToS, Privacy, Cookies, DPA, SLA, member_clause).
 *
 * Stored in `legal_documents`. Tylko jedna wersja per (doc_type, locale) ma is_current=1.
 */
class LegalDocumentModel extends BaseModel
{
    protected string $table = 'legal_documents';

    public const TYPES = ['tos', 'privacy', 'cookies', 'dpa', 'sla', 'member_clause'];

    /** Mapowanie slug → doc_type używane w URL-ach (/legal/{slug}). */
    public const SLUG_MAP = [
        'regulamin'         => 'tos',
        'tos'               => 'tos',
        'polityka-prywatnosci' => 'privacy',
        'privacy'           => 'privacy',
        'cookies'           => 'cookies',
        'polityka-cookies'  => 'cookies',
        'dpa'               => 'dpa',
        'sla'               => 'sla',
        'klauzula-rodo'     => 'member_clause',
        'member-clause'     => 'member_clause',
    ];

    public static function slugToType(string $slug): ?string
    {
        $slug = strtolower(trim($slug));
        return self::SLUG_MAP[$slug] ?? null;
    }

    public static function typeToSlug(string $type): string
    {
        $map = [
            'tos'           => 'regulamin',
            'privacy'       => 'polityka-prywatnosci',
            'cookies'       => 'cookies',
            'dpa'           => 'dpa',
            'sla'           => 'sla',
            'member_clause' => 'klauzula-rodo',
        ];
        return $map[$type] ?? $type;
    }

    public static function typeLabel(string $type): string
    {
        $labels = [
            'tos'           => 'Regulamin świadczenia usług',
            'privacy'       => 'Polityka prywatności',
            'cookies'       => 'Polityka cookies',
            'dpa'           => 'Umowa powierzenia (DPA)',
            'sla'           => 'Service Level Agreement (SLA)',
            'member_clause' => 'Klauzula RODO dla członka klubu',
        ];
        return $labels[$type] ?? $type;
    }

    public static function typeDescription(string $type): string
    {
        $d = [
            'tos'           => 'Zasady świadczenia usługi ClubDesk dla klubów (B2B).',
            'privacy'       => 'Sposoby przetwarzania danych osobowych przez Sendormeco Holding Sp. z o.o.',
            'cookies'       => 'Pliki cookie używane przez clubdesk.pl i app.clubdesk.pl.',
            'dpa'           => 'Umowa powierzenia przetwarzania danych osobowych zgodnie z art. 28 RODO.',
            'sla'           => 'Parametry dostępności usługi, czasy reakcji wsparcia, bonifikaty.',
            'member_clause' => 'Wzór klauzuli informacyjnej RODO do udostępnienia członkom klubu.',
        ];
        return $d[$type] ?? '';
    }

    /** Returns the currently active document for given type. */
    public function current(string $type, string $locale = 'pl'): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM legal_documents
              WHERE doc_type = ? AND locale = ? AND is_current = 1
              LIMIT 1"
        );
        $stmt->execute([$type, $locale]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** All current documents (one per type). */
    public function allCurrent(string $locale = 'pl'): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM legal_documents
              WHERE locale = ? AND is_current = 1
              ORDER BY FIELD(doc_type, 'tos','privacy','cookies','dpa','sla','member_clause')"
        );
        $stmt->execute([$locale]);
        return $stmt->fetchAll();
    }

    /** All versions for given type, newest first. */
    public function versionsFor(string $type, string $locale = 'pl'): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM legal_documents
              WHERE doc_type = ? AND locale = ?
              ORDER BY effective_from DESC, id DESC"
        );
        $stmt->execute([$type, $locale]);
        return $stmt->fetchAll();
    }

    public function byTypeAndVersion(string $type, string $version, string $locale = 'pl'): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM legal_documents
              WHERE doc_type = ? AND version = ? AND locale = ?
              LIMIT 1"
        );
        $stmt->execute([$type, $version, $locale]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Inserts new version and marks it as current.
     * Previous current versions for same (doc_type, locale) become is_current=0.
     */
    public function publishNewVersion(array $data): int
    {
        $type    = $data['doc_type']       ?? '';
        $locale  = $data['locale']         ?? 'pl';
        $version = $data['version']        ?? '1.0';
        $effFrom = $data['effective_from'] ?? date('Y-m-d');
        $title   = $data['title']          ?? '';
        $body    = $data['body_md']        ?? '';

        if (!in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException('Nieznany typ dokumentu: ' . $type);
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                "UPDATE legal_documents SET is_current = 0 WHERE doc_type = ? AND locale = ?"
            )->execute([$type, $locale]);

            $stmt = $this->db->prepare(
                "INSERT INTO legal_documents
                    (doc_type, locale, version, effective_from, title, body_md, is_current)
                 VALUES (?, ?, ?, ?, ?, ?, 1)"
            );
            $stmt->execute([$type, $locale, $version, $effFrom, $title, $body]);
            $id = (int)$this->db->lastInsertId();

            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
