<?php

namespace App\Controllers\Api;

use App\Helpers\Database;
use App\Helpers\MemberAuth;

/**
 * API auth for mobile app — member portal login via email + password.
 * Returns member data + API key for subsequent requests.
 * POST /api/v1/auth/login (no Bearer required, no CSRF)
 */
class AuthApiController
{
    public function login(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $email    = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if ($email === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Email i hasło są wymagane.']);
            exit;
        }

        $db = Database::pdo();
        $stmt = $db->prepare("SELECT * FROM members WHERE email = ? AND status = 'aktywny' LIMIT 1");
        $stmt->execute([$email]);
        $member = $stmt->fetch();

        if (!$member || !MemberAuth::verifyPassword($member, $password)) {
            http_response_code(401);
            echo json_encode(['error' => 'Nieprawidłowy email lub hasło.']);
            exit;
        }

        // Generate session token
        $token = bin2hex(random_bytes(32));
        $db->prepare("UPDATE members SET portal_last_login = NOW() WHERE id = ?")->execute([$member['id']]);

        // Get sports for member
        $stmt = $db->prepare(
            "SELECT ms.*, s.name AS sport_name, s.`key` AS sport_key, s.icon
             FROM member_sports ms
             JOIN club_sports cs ON cs.id = ms.club_sport_id
             JOIN sports s ON s.id = cs.sport_id
             WHERE ms.member_id = ?"
        );
        $stmt->execute([$member['id']]);
        $sports = $stmt->fetchAll();

        // Get club info
        $stmt = $db->prepare("SELECT id, name, short_name, city, email FROM clubs WHERE id = ?");
        $stmt->execute([$member['club_id']]);
        $club = $stmt->fetch();

        echo json_encode([
            'token' => $token,
            'member' => [
                'id'            => (int)$member['id'],
                'club_id'       => (int)$member['club_id'],
                'member_number' => $member['member_number'],
                'first_name'    => $member['first_name'],
                'last_name'     => $member['last_name'],
                'email'         => $member['email'],
                'phone'         => $member['phone'],
                'gender'        => $member['gender'],
                'birth_date'    => $member['birth_date'],
                'join_date'     => $member['join_date'],
                'status'        => $member['status'],
            ],
            'club'   => $club,
            'sports' => $sports,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
