<?php

namespace App\Controllers\Api;

use App\Helpers\ClubBranding;
use App\Helpers\Database;
use App\Helpers\MemberAuth;
use App\Helpers\MemberTokenAuth;
use App\Helpers\RateLimiter;
use App\Models\ClubModel;
use App\Models\DeviceTokenModel;
use App\Models\MemberApiTokenModel;

/**
 * Endpointy public (bez Bearer) i logout (z Bearer).
 * Nie dziedziczy z BaseApiController bo wymaga obslugi przypadku "brak tokenu".
 */
class AuthApiController
{
    public function login(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        // 5 prob / minute / IP — chroni przed credential stuffing
        if (!RateLimiter::check($ip, 'mobile_login', 5, 1)) {
            self::out(429, ['error' => 'Zbyt wiele prób logowania.', 'code' => 'rate_limited']);
        }
        RateLimiter::hit($ip, 'mobile_login', 5, 1);

        $input = json_decode(file_get_contents('php://input') ?: '', true) ?: $_POST;
        $email    = strtolower(trim($input['email'] ?? ''));
        $password = (string)($input['password'] ?? '');

        if ($email === '' || $password === '') {
            self::out(400, ['error' => 'Email i hasło są wymagane.', 'code' => 'missing_credentials']);
        }

        $db = Database::pdo();
        $stmt = $db->prepare("SELECT * FROM members WHERE LOWER(email) = ? AND status = 'aktywny' LIMIT 1");
        $stmt->execute([$email]);
        $member = $stmt->fetch();

        $identityId = null;
        if (!$member || empty($member['portal_password']) || !MemberAuth::verifyPassword($member, $password)) {
            // Fallback: identity-level credentials
            $stmt = $db->prepare("SELECT * FROM member_identities WHERE portal_email = ? LIMIT 1");
            $stmt->execute([$email]);
            $identity = $stmt->fetch();
            if (!$identity || empty($identity['portal_password']) || !password_verify($password, $identity['portal_password'])) {
                self::out(401, ['error' => 'Nieprawidłowy email lub hasło.', 'code' => 'invalid_credentials']);
            }
            $identityId = (int)$identity['id'];

            // Resolve a member record for this identity; if multi-club, pick first active.
            $stmt = $db->prepare(
                "SELECT * FROM members WHERE identity_id = ? AND status = 'aktywny' ORDER BY id ASC LIMIT 1"
            );
            $stmt->execute([$identityId]);
            $member = $stmt->fetch();
            if (!$member) {
                self::out(403, ['error' => 'Brak aktywnej przynależności klubowej.', 'code' => 'no_active_membership']);
            }
        } else {
            $identityId = $member['identity_id'] !== null ? (int)$member['identity_id'] : null;
        }

        $memberId = (int)$member['id'];
        $clubId   = (int)$member['club_id'];

        $db->prepare("UPDATE members SET portal_last_login = NOW() WHERE id = ?")->execute([$memberId]);

        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : null;
        $pair = (new MemberApiTokenModel())->issue($memberId, $clubId, $identityId, null, $ua, $ip);

        self::out(200, self::buildLoginResponse($db, $member, $clubId, $pair));
    }

    public function refresh(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $input = json_decode(file_get_contents('php://input') ?: '', true) ?: $_POST;
        $raw   = trim((string)($input['refresh_token'] ?? ''));
        if ($raw === '') {
            self::out(400, ['error' => 'Brak refresh_token.', 'code' => 'missing_refresh_token']);
        }

        $pair = (new MemberApiTokenModel())->refresh($raw);
        if ($pair === null) {
            self::out(401, ['error' => 'Refresh token nieprawidłowy lub wygasły.', 'code' => 'invalid_refresh_token']);
        }

        self::out(200, $pair);
    }

    public function logout(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
            self::out(401, ['error' => 'Brak tokenu.', 'code' => 'missing_token']);
        }
        $raw = trim($m[1]);
        $auth = MemberTokenAuth::authenticate($raw);
        if ($auth === null) {
            self::out(401, ['error' => 'Nieprawidłowy token.', 'code' => 'invalid_token']);
        }

        (new MemberApiTokenModel())->revoke($raw);

        $input = json_decode(file_get_contents('php://input') ?: '', true) ?: [];
        $deviceToken = trim((string)($input['device_token'] ?? ''));
        if ($deviceToken !== '') {
            (new DeviceTokenModel())->unregister($deviceToken);
        }

        self::out(200, ['status' => 'ok']);
    }

    private static function buildLoginResponse(\PDO $db, array $member, int $clubId, array $pair): array
    {
        $stmt = $db->prepare(
            "SELECT ms.*, s.name AS sport_name, s.`key` AS sport_key, s.icon
             FROM member_sports ms
             JOIN club_sports cs ON cs.id = ms.club_sport_id
             JOIN sports s ON s.id = cs.sport_id
             WHERE ms.member_id = ? AND ms.is_active = 1"
        );
        $stmt->execute([(int)$member['id']]);
        $sports = $stmt->fetchAll();

        $club    = (new ClubModel())->findById($clubId);
        $branding = ClubBranding::forClub($clubId)->toArray();

        return [
            'token'              => $pair['token'],
            'refresh_token'      => $pair['refresh_token'],
            'expires_at'         => $pair['expires_at'],
            'refresh_expires_at' => $pair['refresh_expires_at'],
            'member' => [
                'id'            => (int)$member['id'],
                'club_id'       => (int)$member['club_id'],
                'identity_id'   => $member['identity_id'] !== null ? (int)$member['identity_id'] : null,
                'member_number' => $member['member_number'],
                'first_name'    => $member['first_name'],
                'last_name'     => $member['last_name'],
                'email'         => $member['email'],
                'phone'         => $member['phone'],
                'gender'        => $member['gender'],
                'birth_date'    => $member['birth_date'],
                'join_date'     => $member['join_date'],
                'status'        => $member['status'],
                'photo_path'    => $member['photo_path'] ?? null,
            ],
            'club' => [
                'id'         => (int)($club['id'] ?? $clubId),
                'name'       => $club['name'] ?? null,
                'short_name' => $club['short_name'] ?? null,
                'city'       => $club['city'] ?? null,
                'email'      => $club['email'] ?? null,
                'branding'   => self::brandingPayload($branding, $club),
            ],
            'sports' => $sports,
        ];
    }

    public static function brandingPayload(array $branding, ?array $club): array
    {
        return [
            'primary_color'   => $branding['primary_color'] ?? '#0d6efd',
            'accent_color'    => $branding['accent_color']  ?? '#198754',
            'navbar_bg'       => $branding['navbar_bg']     ?? '#212529',
            'logo_url'        => isset($branding['logo_path']) && $branding['logo_path']
                ? '/' . ltrim((string)$branding['logo_path'], '/')
                : null,
            'favicon_url'     => isset($branding['favicon_path']) && $branding['favicon_path']
                ? '/' . ltrim((string)$branding['favicon_path'], '/')
                : null,
            'motto'           => $branding['motto'] ?? null,
            'club_name'       => $club['name'] ?? null,
            'club_short_name' => $club['short_name'] ?? null,
        ];
    }

    private static function out(int $status, array $body): never
    {
        http_response_code($status);
        echo json_encode($body, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
