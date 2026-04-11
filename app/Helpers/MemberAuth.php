<?php

namespace App\Helpers;

use App\Models\MemberModel;

/**
 * Logowanie zawodnika przez jego osobne konto w portalu.
 * Oddzielone od Auth (kontroluje administratorów klubu).
 *
 * Klucze sesji:
 *   portal_member_id, portal_member_name, portal_member_email, portal_club_id
 */
class MemberAuth
{
    public static function check(): bool
    {
        return Session::has('portal_member_id');
    }

    public static function id(): ?int
    {
        $v = Session::get('portal_member_id');
        return $v !== null ? (int)$v : null;
    }

    public static function member(): ?array
    {
        if (!self::check()) return null;
        return (new MemberModel())->withoutScope()->findById((int)self::id());
    }

    public static function clubId(): ?int
    {
        $v = Session::get('portal_club_id');
        return $v !== null ? (int)$v : null;
    }

    public static function login(array $member): void
    {
        Session::start();
        session_regenerate_id(true);
        Session::set('portal_member_id',    (int)$member['id']);
        Session::set('portal_member_name',  $member['first_name'] . ' ' . $member['last_name']);
        Session::set('portal_member_email', $member['email'] ?? '');
        Session::set('portal_club_id',      (int)$member['club_id']);
        ClubContext::set((int)$member['club_id']);
    }

    public static function logout(): void
    {
        Session::remove('portal_member_id');
        Session::remove('portal_member_name');
        Session::remove('portal_member_email');
        Session::remove('portal_club_id');
        ClubContext::clear();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Session::flash('error', 'Zaloguj się do portalu zawodnika.');
            header('Location: ' . url('portal/login'));
            exit;
        }
    }

    public static function verifyPassword(array $member, string $password): bool
    {
        if (empty($member['portal_password'])) return false;
        return password_verify($password, $member['portal_password']);
    }

    public static function setPassword(int $memberId, string $password): void
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db   = Database::pdo();
        $stmt = $db->prepare("UPDATE members SET portal_password = ? WHERE id = ?");
        $stmt->execute([$hash, $memberId]);
    }
}
