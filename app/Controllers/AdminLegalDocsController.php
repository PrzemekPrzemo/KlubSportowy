<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\LegalAcceptanceModel;
use App\Models\LegalDocumentModel;

/**
 * Super-admin only: zarządzanie wersjami dokumentów prawnych.
 *
 *  /admin/platform/legal-docs                — przegląd wszystkich typów + bieżąca wersja
 *  /admin/platform/legal-docs/{type}         — wszystkie wersje danego typu
 *  /admin/platform/legal-docs/{type}/new     — formularz publikacji nowej wersji
 *  /admin/platform/legal-docs/{type}/publish — POST: publikacja
 *  /admin/platform/legal-docs/acceptances    — log akceptacji (audytowy)
 */
class AdminLegalDocsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    public function index(): void
    {
        $model = new LegalDocumentModel();
        $rows  = $model->allCurrent('pl');
        $byType = [];
        foreach ($rows as $r) { $byType[$r['doc_type']] = $r; }

        // Liczba akceptacji per typ.
        $db    = Database::pdo();
        $stats = $db->query(
            "SELECT ld.doc_type, COUNT(la.id) AS cnt
               FROM legal_documents ld
               LEFT JOIN legal_acceptances la ON la.document_id = ld.id
              WHERE ld.is_current = 1
              GROUP BY ld.doc_type"
        )->fetchAll();
        $statMap = [];
        foreach ($stats as $s) { $statMap[$s['doc_type']] = (int)$s['cnt']; }

        $items = [];
        foreach (LegalDocumentModel::TYPES as $type) {
            $cur = $byType[$type] ?? null;
            $items[] = [
                'type'        => $type,
                'label'       => LegalDocumentModel::typeLabel($type),
                'description' => LegalDocumentModel::typeDescription($type),
                'current'     => $cur,
                'acceptances' => $statMap[$type] ?? 0,
            ];
        }

        $this->render('admin/platform/legal_docs/index', [
            'title' => 'Dokumenty prawne — wersje',
            'items' => $items,
        ]);
    }

    public function versions(string $type): void
    {
        $type = (string)$type;
        if (!in_array($type, LegalDocumentModel::TYPES, true)) {
            Session::flash('error', 'Nieznany typ dokumentu.');
            $this->redirect('admin/platform/legal-docs');
        }
        $model = new LegalDocumentModel();
        $versions = $model->versionsFor($type, 'pl');

        $this->render('admin/platform/legal_docs/versions', [
            'title'    => 'Wersje: ' . LegalDocumentModel::typeLabel($type),
            'docType'  => $type,
            'label'    => LegalDocumentModel::typeLabel($type),
            'versions' => $versions,
        ]);
    }

    public function createForm(string $type): void
    {
        $type = (string)$type;
        if (!in_array($type, LegalDocumentModel::TYPES, true)) {
            Session::flash('error', 'Nieznany typ dokumentu.');
            $this->redirect('admin/platform/legal-docs');
        }
        $model   = new LegalDocumentModel();
        $current = $model->current($type, 'pl');

        $this->render('admin/platform/legal_docs/form', [
            'title'   => 'Nowa wersja: ' . LegalDocumentModel::typeLabel($type),
            'docType' => $type,
            'label'   => LegalDocumentModel::typeLabel($type),
            'current' => $current,
        ]);
    }

    public function publish(string $type): void
    {
        Csrf::verify();
        $type = (string)$type;
        if (!in_array($type, LegalDocumentModel::TYPES, true)) {
            Session::flash('error', 'Nieznany typ dokumentu.');
            $this->redirect('admin/platform/legal-docs');
        }

        $version = trim((string)($_POST['version']        ?? ''));
        $effFrom = trim((string)($_POST['effective_from'] ?? ''));
        $title   = trim((string)($_POST['title']          ?? ''));
        $body    = (string)($_POST['body_md']             ?? '');

        $errors = [];
        if ($version === '' || !preg_match('/^[0-9]+\.[0-9]+(\.[0-9]+)?$/', $version)) {
            $errors[] = 'Podaj numer wersji w formacie X.Y lub X.Y.Z.';
        }
        if ($effFrom === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $effFrom)) {
            $errors[] = 'Podaj datę wejścia w życie (YYYY-MM-DD).';
        }
        if ($title === '' || mb_strlen($title) > 200) {
            $errors[] = 'Podaj tytuł (max 200 znaków).';
        }
        if (trim($body) === '') {
            $errors[] = 'Treść dokumentu nie może być pusta.';
        }

        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            $this->redirect('admin/platform/legal-docs/' . $type . '/new');
        }

        try {
            $model = new LegalDocumentModel();
            $id = $model->publishNewVersion([
                'doc_type'       => $type,
                'locale'         => 'pl',
                'version'        => $version,
                'effective_from' => $effFrom,
                'title'          => $title,
                'body_md'        => $body,
            ]);
            Session::flash('success', 'Opublikowano nową wersję dokumentu (id=' . $id . ').');
        } catch (\Throwable $e) {
            Session::flash('error', 'Nie udało się opublikować: ' . $e->getMessage());
            $this->redirect('admin/platform/legal-docs/' . $type . '/new');
        }
        $this->redirect('admin/platform/legal-docs/' . $type);
    }

    public function acceptances(): void
    {
        $db = Database::pdo();
        $rows = $db->query(
            "SELECT la.*, ld.doc_type, ld.version, ld.title,
                    u.email AS user_email, c.name AS club_name
               FROM legal_acceptances la
               JOIN legal_documents ld ON ld.id = la.document_id
               LEFT JOIN users u  ON u.id = la.user_id
               LEFT JOIN clubs c  ON c.id = la.club_id
              ORDER BY la.accepted_at DESC
              LIMIT 500"
        )->fetchAll();

        $this->render('admin/platform/legal_docs/acceptances', [
            'title' => 'Log akceptacji dokumentów',
            'rows'  => $rows,
        ]);
    }
}
