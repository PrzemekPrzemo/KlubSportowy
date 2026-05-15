<?php

namespace App\Controllers\Api;

use App\Helpers\Database;
use App\Models\ClubModel;
use App\Models\MemberApiTokenModel;
use App\Models\MemberIdentityModel;

class IdentityApiController extends BaseApiController
{
    public function clubs(): void
    {
        $this->requireMember();

        if ($this->identityId !== null) {
            $clubs = (new MemberIdentityModel())->clubsForIdentity($this->identityId);
        } else {
            $club = (new ClubModel())->findById($this->clubId);
            $clubs = $club ? [[
                'id'         => (int)$club['id'],
                'name'       => $club['name'],
                'short_name' => $club['short_name'] ?? null,
                'city'       => $club['city'] ?? null,
                'is_active'  => (int)($club['is_active'] ?? 1),
            ]] : [];
        }

        $this->json([
            'data'       => $clubs,
            'current_club_id' => $this->clubId,
        ]);
    }

    public function switchClub(): void
    {
        $this->requireMember();

        if ($this->identityId === null) {
            $this->error('Brak unified identity — przełączanie klubów niedostępne.', 400, 'no_identity');
        }

        $input = json_decode(file_get_contents('php://input') ?: '', true) ?: $_POST;
        $targetClubId = (int)($input['club_id'] ?? 0);
        if ($targetClubId <= 0) {
            $this->error('Brak club_id.', 400, 'missing_club_id');
        }

        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT id FROM members
             WHERE identity_id = ? AND club_id = ? AND status = 'aktywny'
             LIMIT 1"
        );
        $stmt->execute([$this->identityId, $targetClubId]);
        $newMemberId = (int)($stmt->fetchColumn() ?: 0);
        if ($newMemberId <= 0) {
            $this->error('Brak aktywnej przynależności w tym klubie.', 403, 'no_membership_in_club');
        }

        $tokenModel = new MemberApiTokenModel();
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $pair = $tokenModel->issue($newMemberId, $targetClubId, $this->identityId, null, $ua, $ip);

        if ($this->memberTokenId !== null) {
            $db->prepare("UPDATE member_api_tokens SET revoked_at = NOW() WHERE id = ?")
               ->execute([$this->memberTokenId]);
        }

        $this->json($pair);
    }
}
