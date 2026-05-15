<?php

namespace App\Controllers\Api;

use App\Helpers\ClubBranding;
use App\Models\ClubModel;

class ClubBrandingApiController extends BaseApiController
{
    public function show(): void
    {
        $branding = ClubBranding::forClub($this->clubId)->toArray();
        $club     = (new ClubModel())->findById($this->clubId);

        $payload = AuthApiController::brandingPayload($branding, $club);

        // ETag: prefer column `updated_at` (jeśli istnieje), fallback do MD5 payloadu.
        $etagSource = (string)($branding['updated_at'] ?? '');
        $etag = '"' . md5($etagSource !== '' ? $etagSource : json_encode($payload)) . '"';

        $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if ($ifNoneMatch !== '' && trim($ifNoneMatch) === $etag) {
            http_response_code(304);
            header('ETag: ' . $etag);
            exit;
        }

        header('ETag: ' . $etag);
        $this->json($payload);
    }
}
