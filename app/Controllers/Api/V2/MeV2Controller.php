<?php

namespace App\Controllers\Api\V2;

class MeV2Controller extends ApiV2BaseController
{
    /**
     * Zwraca metadane biezacego tokenu (introspection-lite).
     * Nie wymaga zadnego konkretnego scope — auth wystarczy.
     */
    public function show(): void
    {
        $this->json([
            'data' => [
                'token_id'    => $this->tokenId,
                'club_id'     => $this->clubId,
                'name'        => $this->token['name'] ?? null,
                'scopes'      => $this->scopes,
                'expires_at'  => $this->token['expires_at'] ?? null,
                'api_version' => 'v2',
            ],
        ]);
    }
}
