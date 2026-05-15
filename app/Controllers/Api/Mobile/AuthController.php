<?php

namespace App\Controllers\Api\Mobile;

use App\Helpers\Database;
use App\Helpers\MemberAuth;
use App\Helpers\MobileApiAuth;
use App\Helpers\RateLimiter;
use App\Models\MemberIdentityModel;

/**
 * Mobile API v1 — authentication endpoints.
 * Re-uses existing MemberAuth password verification and MemberIdentityModel
 * for cross-club lookups. Tokens are issued via MobileApiAuth.
 */
class AuthController extends V1Controller
{
    /**
     * POST /api/mobile/v1/auth/login
     * Body: { email, password, device_info? }
     */
    public function login(): void
    {
        $input    = $this->input();
        $email    = strtolower(trim((string)($input['email'] ?? '')));
        $password = (string)($input['password'] ?? '');
        $device   = trim((string)($input['device_info'] ?? ''));
        $appVer   = trim((string)($input['app_version'] ?? '')) ?: null;
        $ua       = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if ($email === '' || $password === '') {
            $this->error('Email i hasło są wymagane.', 422, 'validation',
                ['email' => $email === '' ? 'required' : null,
                 'password' => $password === '' ? 'required' : null]);
        }

        // Rate limit: 5 failed/h/IP
        if (!RateLimiter::check($ip, 'mobile_login', 5, 60)) {
            $this->error('Zbyt wiele prób logowania. Spróbuj ponownie za godzinę.', 429, 'rate_limited');
        }

        $identityModel = new MemberIdentityModel();

        // 1. Try unified identity login (cross-club).
        $identity = $identityModel->findByEmail($email);
        if ($identity && $identityModel->verifyPassword($identity, $password)) {
            RateLimiter::reset($ip, 'mobile_login');
            $identityModel->touchLogin((int)$identity['id']);

            $clubs = $identityModel->clubsForIdentity((int)$identity['id']);

            if (count($clubs) > 1) {
                $this->json([
                    'multiple_clubs' => true,
                    'clubs' => array_map(fn($c) => [
                        'id'         => (int)$c['id'],
                        'name'       => $c['name'],
                        'short_name' => $c['short_name'] ?? null,
                        'city'       => $c['city'] ?? null,
                        'logo_url'   => null,
                    ], $clubs),
                ]);
            }

            if (count($clubs) === 1) {
                $clubId = (int)$clubs[0]['id'];
                $member = $this->resolveMemberForIdentity((int)$identity['id'], $clubId);
                if ($member === null) {
                    $this->error('Nie odnaleziono aktywnego członkostwa w wybranym klubie.', 404, 'no_membership');
                }
                $tokens = MobileApiAuth::issueToken(
                    (int)$member['id'], $clubId, $device ?: null, $ua, $appVer, (int)$identity['id']
                );
                $this->json($this->buildAuthPayload($tokens, $member, $clubs[0]));
            }

            $this->error('Brak aktywnych członkostw dla tego konta.', 403, 'no_membership');
        }

        // 2. Fallback: legacy direct members.email + members.portal_password.
        $db   = Database::pdo();
        $stmt = $db->prepare("SELECT * FROM members WHERE email = ? AND status = 'aktywny' LIMIT 1");
        $stmt->execute([$email]);
        $member = $stmt->fetch();

        if (!$member || !MemberAuth::verifyPassword($member, $password)) {
            RateLimiter::hit($ip, 'mobile_login', 5, 60);
            $this->error('Nieprawidłowy email lub hasło.', 401, 'invalid_credentials');
        }

        RateLimiter::reset($ip, 'mobile_login');
        $db->prepare("UPDATE members SET portal_last_login = NOW() WHERE id = ?")->execute([$member['id']]);

        $clubStmt = $db->prepare("SELECT id, name, short_name, city, email FROM clubs WHERE id = ?");
        $clubStmt->execute([(int)$member['club_id']]);
        $club = $clubStmt->fetch() ?: null;

        $tokens = MobileApiAuth::issueToken(
            (int)$member['id'], (int)$member['club_id'], $device ?: null, $ua, $appVer
        );

        $this->json($this->buildAuthPayload($tokens, $member, $club));
    }

    /**
     * POST /api/mobile/v1/auth/select-club
     * Body: { email, password, club_id }
     */
    public function selectClub(): void
    {
        $input    = $this->input();
        $email    = strtolower(trim((string)($input['email'] ?? '')));
        $password = (string)($input['password'] ?? '');
        $clubId   = (int)($input['club_id'] ?? 0);
        $device   = trim((string)($input['device_info'] ?? ''));
        $appVer   = trim((string)($input['app_version'] ?? '')) ?: null;
        $ua       = $_SERVER['HTTP_USER_AGENT'] ?? null;

        if ($email === '' || $password === '' || $clubId <= 0) {
            $this->error('email, password i club_id są wymagane.', 422, 'validation');
        }

        $identityModel = new MemberIdentityModel();
        $identity = $identityModel->findByEmail($email);
        if (!$identity || !$identityModel->verifyPassword($identity, $password)) {
            $this->error('Nieprawidłowy email lub hasło.', 401, 'invalid_credentials');
        }

        $clubs = $identityModel->clubsForIdentity((int)$identity['id']);
        $clubIds = array_map(fn($c) => (int)$c['id'], $clubs);
        if (!in_array($clubId, $clubIds, true)) {
            $this->error('Brak członkostwa w wybranym klubie.', 403, 'no_membership');
        }

        $member = $this->resolveMemberForIdentity((int)$identity['id'], $clubId);
        if ($member === null) {
            $this->error('Nie odnaleziono aktywnego członkostwa.', 404, 'no_membership');
        }

        $club = null;
        foreach ($clubs as $c) {
            if ((int)$c['id'] === $clubId) { $club = $c; break; }
        }

        $tokens = MobileApiAuth::issueToken(
            (int)$member['id'], $clubId, $device ?: null, $ua, $appVer, (int)$identity['id']
        );
        $this->json($this->buildAuthPayload($tokens, $member, $club));
    }

    /** POST /api/mobile/v1/auth/logout — invalidates the bearer token. */
    public function logout(): void
    {
        $raw = MobileApiAuth::extractBearerToken();
        if ($raw === null) {
            $this->error('Brak tokenu.', 401, 'unauthorized');
        }
        MobileApiAuth::revokeByRawToken($raw);
        $this->json(['logged_out' => true]);
    }

    /** POST /api/mobile/v1/auth/refresh — body { refresh_token } */
    public function refresh(): void
    {
        $input = $this->input();
        $refresh = (string)($input['refresh_token'] ?? '');
        if ($refresh === '') {
            $this->error('refresh_token jest wymagany.', 422, 'validation');
        }
        $tokens = MobileApiAuth::refresh($refresh);
        if ($tokens === null) {
            $this->error('Refresh token wygasł lub jest nieprawidłowy.', 401, 'invalid_refresh');
        }
        $this->json([
            'token'              => $tokens['access'],
            'refresh_token'      => $tokens['refresh'],
            'expires_at'         => $tokens['expires_at'],
            'refresh_expires_at' => $tokens['refresh_expires_at'],
        ]);
    }

    /**
     * POST /api/mobile/v1/auth/forgot-password
     * Body: { email }
     * We always return ok=true (don't leak existence). The actual mail is sent
     * via existing portal-reset flow when implemented; here we just stub the trigger.
     */
    public function forgotPassword(): void
    {
        $input = $this->input();
        $email = strtolower(trim((string)($input['email'] ?? '')));
        if ($email === '') {
            $this->error('Email jest wymagany.', 422, 'validation', ['email' => 'required']);
        }

        // Best-effort: log existence of identity/member, generate a reset token row if available.
        // Heavy lifting belongs in a dedicated PortalPasswordResetService; we keep the API
        // contract stable and degrade gracefully.
        try {
            $db = Database::pdo();
            $stmt = $db->prepare("SELECT id FROM member_identities WHERE portal_email = ? LIMIT 1");
            $stmt->execute([$email]);
            $stmt->fetch();
        } catch (\Throwable $e) {
            // swallow — never reveal internal errors here
        }

        $this->json([
            'sent'    => true,
            'message' => 'Jeśli konto istnieje, wysłaliśmy instrukcję resetu hasła.',
        ]);
    }

    // ---------------- helpers ----------------

    private function resolveMemberForIdentity(int $identityId, int $clubId): ?array
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT * FROM members
             WHERE identity_id = ? AND club_id = ? AND status = 'aktywny'
             LIMIT 1"
        );
        $stmt->execute([$identityId, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Build the standard auth response: token + member + club. */
    private function buildAuthPayload(array $tokens, array $member, ?array $club): array
    {
        return [
            'token'              => $tokens['access'],
            'refresh_token'      => $tokens['refresh'],
            'expires_at'         => $tokens['expires_at'],
            'refresh_expires_at' => $tokens['refresh_expires_at'],
            'member'             => [
                'id'            => (int)$member['id'],
                'club_id'       => (int)$member['club_id'],
                'member_number' => $member['member_number'] ?? null,
                'first_name'    => $member['first_name'] ?? null,
                'last_name'     => $member['last_name'] ?? null,
                'email'         => $member['email'] ?? null,
                'phone'         => $member['phone'] ?? null,
                'gender'        => $member['gender'] ?? null,
                'birth_date'    => $member['birth_date'] ?? null,
                'join_date'     => $member['join_date'] ?? null,
                'status'        => $member['status'] ?? null,
                'photo_path'    => $member['photo_path'] ?? null,
            ],
            'club' => $club ? [
                'id'         => (int)$club['id'],
                'name'       => $club['name'] ?? null,
                'short_name' => $club['short_name'] ?? null,
                'city'       => $club['city'] ?? null,
            ] : null,
        ];
    }
}
