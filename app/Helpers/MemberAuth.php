<?php

namespace App\Helpers;

use App\Models\MemberIdentityModel;
use App\Models\MemberModel;

/**
 * Logowanie zawodnika przez jego osobne konto w portalu.
 * Oddzielone od Auth (kontroluje administratorów klubu).
 *
 * Supports unified member_identities (cross-club) and legacy member login.
 *
 * Session keys:
 *   portal_member_id, portal_member_name, portal_member_email, portal_club_id
 *   portal_identity_id, portal_multi_club
 */
class MemberAuth
{
    public static function check(): bool
    {
        return Session::has('portal_member_id') || Session::has('portal_identity_id');
    }

    public static function id(): ?int
    {
        $v = Session::get('portal_member_id');
        return $v !== null ? (int)$v : null;
    }

    public static function identityId(): ?int
    {
        $v = Session::get('portal_identity_id');
        return $v !== null ? (int)$v : null;
    }

    public static function member(): ?array
    {
        if (!self::check()) return null;

        $memberId = self::id();
        if ($memberId !== null) {
            return (new MemberModel())->withoutScope()->findById($memberId);
        }

        // Identity-based: find member in current club
        $identityId = self::identityId();
        $clubId     = self::clubId();
        if ($identityId !== null && $clubId !== null) {
            $db   = Database::pdo();
            $stmt = $db->prepare(
                "SELECT * FROM members WHERE identity_id = ? AND club_id = ? AND status = 'aktywny' LIMIT 1"
            );
            $stmt->execute([$identityId, $clubId]);
            $row = $stmt->fetch();
            if ($row) {
                Session::set('portal_member_id', (int)$row['id']);
                return $row;
            }
        }

        return null;
    }

    public static function clubId(): ?int
    {
        $v = Session::get('portal_club_id');
        return $v !== null ? (int)$v : null;
    }

    public static function isMultiClub(): bool
    {
        return (bool)Session::get('portal_multi_club', false);
    }

    /**
     * Legacy login via members table row.
     */
    public static function login(array $member): void
    {
        Session::start();
        session_regenerate_id(true);
        Session::set('portal_member_id',    (int)$member['id']);
        Session::set('portal_member_name',  $member['first_name'] . ' ' . $member['last_name']);
        Session::set('portal_member_email', $member['email'] ?? '');
        Session::set('portal_club_id',      (int)$member['club_id']);
        Session::set('portal_multi_club',   false);
        ClubContext::set((int)$member['club_id']);
    }

    /**
     * Login via unified member_identities record.
     */
    public static function loginIdentity(array $identity): void
    {
        Session::start();
        session_regenerate_id(true);
        Session::set('portal_identity_id',  (int)$identity['id']);
        Session::set('portal_member_name',  $identity['display_name']);
        Session::set('portal_member_email', $identity['portal_email'] ?? '');

        $identityModel = new MemberIdentityModel();
        $clubs = $identityModel->clubsForIdentity((int)$identity['id']);

        if (count($clubs) > 1) {
            Session::set('portal_multi_club', true);
            // Don't set ClubContext yet — user must choose
        } elseif (count($clubs) === 1) {
            Session::set('portal_multi_club', false);
            $clubId = (int)$clubs[0]['id'];
            Session::set('portal_club_id', $clubId);
            ClubContext::set($clubId);

            // Resolve member_id for this club
            $db = Database::pdo();
            $stmt = $db->prepare(
                "SELECT id FROM members WHERE identity_id = ? AND club_id = ? AND status = 'aktywny' LIMIT 1"
            );
            $stmt->execute([(int)$identity['id'], $clubId]);
            $mid = $stmt->fetchColumn();
            if ($mid) {
                Session::set('portal_member_id', (int)$mid);
            }
        } else {
            Session::set('portal_multi_club', false);
        }
    }

    /**
     * Select a specific club (for multi-club identities).
     * Verifies that the identity has membership in that club.
     */
    public static function selectClub(int $clubId): bool
    {
        $identityId = self::identityId();
        if ($identityId === null) {
            return false;
        }

        $identityModel = new MemberIdentityModel();
        $clubs = $identityModel->clubsForIdentity($identityId);
        $clubIds = array_column($clubs, 'id');

        if (!in_array($clubId, array_map('intval', $clubIds))) {
            return false;
        }

        Session::set('portal_club_id', $clubId);
        ClubContext::set($clubId);

        // Resolve member_id for this club
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT id FROM members WHERE identity_id = ? AND club_id = ? AND status = 'aktywny' LIMIT 1"
        );
        $stmt->execute([$identityId, $clubId]);
        $mid = $stmt->fetchColumn();
        if ($mid) {
            Session::set('portal_member_id', (int)$mid);
        }

        return true;
    }

    /**
     * Return all clubs for the current logged identity.
     */
    public static function currentClubs(): array
    {
        $identityId = self::identityId();
        if ($identityId === null) {
            // Legacy login: return current club
            $clubId = self::clubId();
            if ($clubId !== null) {
                $db = Database::pdo();
                $stmt = $db->prepare("SELECT id, name, short_name, city FROM clubs WHERE id = ?");
                $stmt->execute([$clubId]);
                $row = $stmt->fetch();
                return $row ? [$row] : [];
            }
            return [];
        }
        return (new MemberIdentityModel())->clubsForIdentity($identityId);
    }

    public static function logout(): void
    {
        Session::remove('portal_member_id');
        Session::remove('portal_member_name');
        Session::remove('portal_member_email');
        Session::remove('portal_club_id');
        Session::remove('portal_identity_id');
        Session::remove('portal_multi_club');
        Session::remove('portal_active_membership_id');
        ClubContext::clear();
    }

    /**
     * Aktualnie wybrana przynaleznosc sportowa zawodnika (sport × klub).
     * Zwraca null jesli nie ma jeszcze wybranej (np. legacy login bez identity).
     */
    public static function activeMembershipId(): ?int
    {
        $val = Session::get('portal_active_membership_id');
        return $val !== null ? (int)$val : null;
    }

    /**
     * Ustawia aktualna sekcje sportowa w sesji oraz synchronizuje
     * portal_club_id i portal_member_id z wybranym wpisem ISM.
     * Caller musi zwerifikowac, ze membership nalezy do zalogowanej tozsamosci.
     */
    public static function setActiveMembership(array $membership): void
    {
        Session::set('portal_active_membership_id', (int)$membership['id']);
        Session::set('portal_club_id',  (int)$membership['club_id']);
        Session::set('portal_member_id', (int)$membership['member_id']);
        ClubContext::set((int)$membership['club_id']);
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
