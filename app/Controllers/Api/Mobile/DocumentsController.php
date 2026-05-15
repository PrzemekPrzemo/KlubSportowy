<?php

namespace App\Controllers\Api\Mobile;

/**
 * Mobile API v1 — documents (stub).
 * Returns a static catalogue of available document types. Generation is
 * deferred to the existing DocumentsController web flow (PDF rendering).
 * Mobile clients open the returned URL in a WebView.
 */
class DocumentsController extends V1Controller
{
    /** GET /api/mobile/v1/documents — list available document types. */
    public function index(): void
    {
        $this->requireAuth();
        $this->json([
            ['type' => 'membership_certificate', 'name' => 'Zaświadczenie o członkostwie'],
            ['type' => 'contract',               'name' => 'Umowa członkowska'],
            ['type' => 'medical_certificate',    'name' => 'Certyfikat badań lekarskich'],
            ['type' => 'fee_confirmation',       'name' => 'Potwierdzenie opłaty składki'],
        ]);
    }

    /** GET /api/mobile/v1/documents/:type — returns a URL to fetch the PDF. */
    public function show(string $type): void
    {
        $this->requireAuth();
        $whitelist = [
            'membership_certificate',
            'contract',
            'medical_certificate',
            'fee_confirmation',
        ];
        if (!in_array($type, $whitelist, true)) {
            $this->error('Nieznany typ dokumentu.', 404, 'unknown_document');
        }
        $this->json([
            'type' => $type,
            // Real PDF rendering re-uses DocumentsController web action.
            'url'  => url('portal/documents/' . $type . '?member_id=' . $this->memberId),
        ]);
    }
}
