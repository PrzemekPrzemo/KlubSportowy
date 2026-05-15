<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\SimpleMarkdown;
use App\Models\LegalAcceptanceModel;
use App\Models\LegalDocumentModel;

/**
 * Public Legal documents: lista, render markdown, audytowy log akceptacji.
 *
 *  GET  /legal                   → kafelki 6 dokumentów
 *  GET  /legal/{slug}            → current version (np. /legal/regulamin)
 *  GET  /legal/{slug}/v/{version}→ konkretna wersja
 *  POST /legal/accept            → zapis akceptacji (CSRF)
 *
 * Legacy:
 *  GET  /terms     → /legal/regulamin
 *  GET  /privacy   → /legal/polityka-prywatnosci
 */
class LegalController extends BaseController
{
    /** GET /legal */
    public function index(): void
    {
        $this->view->setLayout('public');
        $model = new LegalDocumentModel();
        $docs  = $model->allCurrent('pl');

        // Indeksujemy po typie + uzupełniamy puste typy z metadanych.
        $byType = [];
        foreach ($docs as $d) {
            $byType[$d['doc_type']] = $d;
        }

        $tiles = [];
        foreach (LegalDocumentModel::TYPES as $type) {
            $row = $byType[$type] ?? null;
            $tiles[] = [
                'type'        => $type,
                'slug'        => LegalDocumentModel::typeToSlug($type),
                'label'       => LegalDocumentModel::typeLabel($type),
                'description' => LegalDocumentModel::typeDescription($type),
                'version'     => $row['version']        ?? null,
                'date'        => $row['effective_from'] ?? null,
                'available'   => $row !== null,
            ];
        }

        $this->view->render('legal/index', [
            'title'   => 'Dokumenty prawne',
            'appName' => 'ClubDesk',
            'tiles'   => $tiles,
        ]);
    }

    /** GET /legal/{slug} — current version of document. */
    public function show(string $slug): void
    {
        $this->view->setLayout('public');
        $type = LegalDocumentModel::slugToType($slug);
        if ($type === null) {
            http_response_code(404);
            $this->view->render('legal/not_found', [
                'title'   => 'Nie znaleziono dokumentu',
                'appName' => 'ClubDesk',
            ]);
            return;
        }

        $model = new LegalDocumentModel();
        $doc   = $model->current($type, 'pl');
        if (!$doc) {
            http_response_code(404);
            $this->view->render('legal/not_found', [
                'title'   => 'Dokument w przygotowaniu',
                'appName' => 'ClubDesk',
            ]);
            return;
        }

        $this->renderDoc($doc, $slug, $model);
    }

    /** GET /legal/{slug}/v/{version} — specific historical version. */
    public function showVersion(string $slug, string $version): void
    {
        $this->view->setLayout('public');
        $type = LegalDocumentModel::slugToType($slug);
        if ($type === null) {
            http_response_code(404);
            $this->view->render('legal/not_found', [
                'title'   => 'Nie znaleziono dokumentu',
                'appName' => 'ClubDesk',
            ]);
            return;
        }
        $model = new LegalDocumentModel();
        $doc   = $model->byTypeAndVersion($type, $version, 'pl');
        if (!$doc) {
            http_response_code(404);
            $this->view->render('legal/not_found', [
                'title'   => 'Nie znaleziono tej wersji dokumentu',
                'appName' => 'ClubDesk',
            ]);
            return;
        }

        $this->renderDoc($doc, $slug, $model);
    }

    private function renderDoc(array $doc, string $slug, LegalDocumentModel $model): void
    {
        $htmlBody = SimpleMarkdown::render((string)$doc['body_md']);
        $toc      = SimpleMarkdown::tableOfContents((string)$doc['body_md']);
        $versions = $model->versionsFor((string)$doc['doc_type'], (string)$doc['locale']);

        $this->view->render('legal/show', [
            'title'    => $doc['title'],
            'appName'  => 'ClubDesk',
            'doc'      => $doc,
            'htmlBody' => $htmlBody,
            'toc'      => $toc,
            'slug'     => $slug,
            'versions' => $versions,
        ]);
    }

    /** POST /legal/accept — record acceptance (CSRF protected). */
    public function accept(): void
    {
        Csrf::verify();

        $docId   = (int)($_POST['document_id'] ?? 0);
        $context = (string)($_POST['context']   ?? 'login_required');

        if ($docId <= 0) {
            Session::flash('error', 'Brak identyfikatora dokumentu.');
            $this->redirect('legal');
        }

        $userId = Auth::id();
        $clubId = ClubContext::current();

        try {
            (new LegalAcceptanceModel())->record([
                'user_id'    => $userId ? (int)$userId : null,
                'club_id'    => $clubId ? (int)$clubId : null,
                'document_id'=> $docId,
                'ip_address' => $_SERVER['REMOTE_ADDR']     ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'context'    => $context,
            ]);
            Session::flash('success', 'Akceptacja została zapisana.');
        } catch (\Throwable $e) {
            error_log('[legal-accept] ' . $e->getMessage());
            Session::flash('error', 'Nie udało się zapisać akceptacji.');
        }

        $back = (string)($_POST['return'] ?? '/legal');
        if (!str_starts_with($back, '/')) { $back = '/legal'; }
        $this->redirect(ltrim($back, '/'));
    }

    /** POST /legal/accept-cookies — anonymous endpoint (no CSRF user session needed). */
    public function acceptCookies(): void
    {
        // Public anonymous endpoint, no CSRF (called from cookie banner JS without session).
        $model = new LegalDocumentModel();
        $doc   = $model->current('cookies', 'pl');
        if ($doc) {
            try {
                (new LegalAcceptanceModel())->record([
                    'user_id'    => Auth::id() ? (int)Auth::id() : null,
                    'club_id'    => ClubContext::current() ? (int)ClubContext::current() : null,
                    'document_id'=> (int)$doc['id'],
                    'ip_address' => $_SERVER['REMOTE_ADDR']     ?? null,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    'context'    => 'registration',
                ]);
            } catch (\Throwable $e) {
                error_log('[legal-accept-cookies] ' . $e->getMessage());
            }
        }
        $this->json(['status' => 'ok']);
    }

    // ── Legacy redirects ────────────────────────────────────────

    public function terms(): void
    {
        header('Location: ' . url('legal/regulamin'), true, 301);
        exit;
    }

    public function privacy(): void
    {
        header('Location: ' . url('legal/polityka-prywatnosci'), true, 301);
        exit;
    }
}
