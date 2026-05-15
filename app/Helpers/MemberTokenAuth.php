<?php

namespace App\Helpers;

use App\Models\MemberApiTokenModel;

class MemberTokenAuth
{
    /**
     * Zwraca {member_id, club_id, identity_id, token_id} albo null.
     */
    public static function authenticate(string $bearerToken): ?array
    {
        $row = (new MemberApiTokenModel())->authenticate($bearerToken);
        if ($row === null) {
            return null;
        }
        return [
            'member_id'   => (int)$row['member_id'],
            'club_id'     => (int)$row['club_id'],
            'identity_id' => $row['identity_id'] !== null ? (int)$row['identity_id'] : null,
            'token_id'    => (int)$row['id'],
        ];
    }
}
