<?php
// ============================================================
// Google Calendar OAuth2 — global client (Variant A)
//
// Variant A: jeden globalny OAuth client ClubDesk → wszystkie kluby
// używają tego samego client_id/client_secret. Klub łączy WŁASNE konto
// Google przez OAuth → my dostajemy access/refresh token klubu.
//
// Variant B (per-klub OAuth client, white-label): klub wpisuje własne
// client_id + client_secret w UI — w club_google_calendar.client_id /
// client_secret_enc. Jeśli puste, fallback do tych globalnych.
//
// SETUP w Google Cloud Console (krok-po-kroku):
//   1. https://console.cloud.google.com/projectcreate
//      → utwórz projekt "ClubDesk"
//   2. APIs & Services → Library → "Google Calendar API" → Enable
//   3. APIs & Services → Credentials → Create Credentials → OAuth client ID
//      Application type: Web application
//      Name: ClubDesk Calendar Sync
//      Authorized redirect URIs:
//        https://portal.clubdesk.pl/club/google-calendar/callback
//        https://<your-domain>/club/google-calendar/callback
//   4. Pobierz client_id + client_secret → wpisz do .env / env vars:
//        GOOGLE_OAUTH_CLIENT_ID=...
//        GOOGLE_OAUTH_CLIENT_SECRET=...
//   5. OAuth consent screen → External / Internal (Workspace)
//      Scopes: https://www.googleapis.com/auth/calendar
//      Test users: <dodaj swoje konta>; potem Submit for verification
//      (production trial-mode wystarcza dla <100 users).
// ============================================================

return [
    'client_id'     => getenv('GOOGLE_OAUTH_CLIENT_ID')     ?: '',
    'client_secret' => getenv('GOOGLE_OAUTH_CLIENT_SECRET') ?: '',

    // Redirect URI musi być DOKŁADNIE taki sam jak w Google Console.
    // Pusty = auto-wykryj (BASE_URL + /club/google-calendar/callback).
    'redirect_uri'  => getenv('GOOGLE_OAUTH_REDIRECT_URI')  ?: '',

    // Scope dla Calendar API. Full RW; jeśli chcesz tylko-do-odczytu zmień
    // na 'https://www.googleapis.com/auth/calendar.readonly'.
    'scope'         => 'https://www.googleapis.com/auth/calendar',

    // Timezone domyślny dla eventów wysyłanych do Google.
    'timezone'      => 'Europe/Warsaw',
];
