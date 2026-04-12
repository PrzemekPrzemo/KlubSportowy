<?php

namespace App\Helpers;

class Auth
{
    public static function check(): bool
    {
        return Session::has('user_id');
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id'             => Session::get('user_id'),
            'username'       => Session::get('username'),
            'full_name'      => Session::get('full_name'),
            'email'          => Session::get('email'),
            'role'           => Session::get('role'),
            'club_id'        => Session::get('club_id'),
            'is_super_admin' => (bool)Session::get('is_super_admin', false),
        ];
    }

    public static function id(): ?int
    {
        $id = Session::get('user_id');
        return $id !== null ? (int)$id : null;
    }

    public static function role(): ?string
    {
        return Session::get('role');
    }

    public static function hasRole(string|array $roles): bool
    {
        $current = self::role();
        if ($current === null) return false;
        $roles = (array)$roles;
        return in_array($current, $roles, true);
    }

    public static function isSuperAdmin(): bool
    {
        return (bool)Session::get('is_super_admin', false);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Session::flash('error', 'Musisz być zalogowany, aby uzyskać dostęp.');
            header('Location: ' . url('auth/login'));
            exit;
        }
    }

    public static function requireRole(string|array $roles): void
    {
        self::requireLogin();
        // Super admin omija wszystkie sprawdzenia
        if (self::isSuperAdmin()) return;
        if (!self::hasRole($roles)) {
            http_response_code(403);
            die('Brak uprawnień do tej sekcji.');
        }
    }

    public static function requireSuperAdmin(): void
    {
        self::requireLogin();
        if (!self::isSuperAdmin()) {
            http_response_code(403);
            die('Wymagany dostęp super administratora.');
        }
    }

    public static function login(array $user): void
    {
        Session::start();
        session_regenerate_id(true);

        Session::set('user_id',        (int)$user['id']);
        Session::set('username',       $user['username']);
        Session::set('full_name',      $user['full_name']);
        Session::set('email',          $user['email'] ?? '');
        Session::set('is_super_admin', !empty($user['is_super_admin']));

        // club_id ustawiany osobno przez setClub() po wyborze klubu
    }

    /** Ustaw aktywny klub w sesji (po zalogowaniu lub przełączeniu). */
    public static function setClub(int $clubId, string $roleInClub): void
    {
        Session::set('club_id', $clubId);
        Session::set('role', $roleInClub);
        ClubContext::set($clubId);
        SportContext::clear();
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    // ── Impersonation (super admin → user klubu) ──────────────────────────
    public static function impersonateClubUser(array $targetUser, int $clubId, string $roleInClub): void
    {
        Session::set('impersonation_original', [
            'user_id'        => Session::get('user_id'),
            'username'       => Session::get('username'),
            'full_name'      => Session::get('full_name'),
            'email'          => Session::get('email'),
            'role'           => Session::get('role'),
            'club_id'        => Session::get('club_id'),
            'is_super_admin' => Session::get('is_super_admin'),
        ]);

        Session::set('user_id',        (int)$targetUser['id']);
        Session::set('username',       $targetUser['username']);
        Session::set('full_name',      $targetUser['full_name']);
        Session::set('email',          $targetUser['email'] ?? '');
        Session::set('role',           $roleInClub);
        Session::set('club_id',        $clubId);
        Session::set('is_super_admin', false);
        Session::set('impersonating',  'club_user');
        ClubContext::set($clubId);
    }

    /** Super admin impersonates a member (portal session). */
    public static function impersonateMember(array $member): void
    {
        Session::set('impersonation_original', [
            'user_id'        => Session::get('user_id'),
            'username'       => Session::get('username'),
            'full_name'      => Session::get('full_name'),
            'email'          => Session::get('email'),
            'role'           => Session::get('role'),
            'club_id'        => Session::get('club_id'),
            'is_super_admin' => Session::get('is_super_admin'),
        ]);

        Session::set('portal_member_id',    (int)$member['id']);
        Session::set('portal_member_name',  $member['first_name'] . ' ' . $member['last_name']);
        Session::set('portal_member_email', $member['email'] ?? '');
        Session::set('portal_club_id',      (int)$member['club_id']);
        Session::set('impersonating',       'member');
        // Clear admin keys so portal works cleanly
        Session::set('user_id', null);
        Session::set('is_super_admin', false);
        ClubContext::set((int)$member['club_id']);
    }

    public static function stopImpersonation(): void
    {
        $original = Session::get('impersonation_original');
        if (!$original) return;

        // Clean up portal member keys if impersonating member
        Session::remove('portal_member_id');
        Session::remove('portal_member_name');
        Session::remove('portal_member_email');
        Session::remove('portal_club_id');
        Session::remove('portal_identity_id');

        Session::remove('impersonating');
        Session::remove('impersonation_original');
        foreach ($original as $k => $v) {
            if ($v !== null) {
                Session::set($k, $v);
            } else {
                Session::remove($k);
            }
        }
        if (!empty($original['club_id'])) {
            ClubContext::set((int)$original['club_id']);
        } else {
            ClubContext::clear();
        }
    }

    public static function isImpersonating(): bool
    {
        return Session::get('impersonating') !== null;
    }
}
