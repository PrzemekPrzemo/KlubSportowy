<?php

namespace App\Controllers\Api;

use App\Helpers\Database;
use App\Models\DeviceTokenModel;
use App\Models\MemberApiTokenModel;

class DevicesApiController extends BaseApiController
{
    public function register(): void
    {
        $input = json_decode(file_get_contents('php://input') ?: '', true) ?: $_POST;
        $token    = trim((string)($input['token'] ?? ''));
        $platform = in_array($input['platform'] ?? '', ['android','ios','web'], true) ? $input['platform'] : 'android';

        // Member tokens: zawodnik moze rejestrowac tylko swoj wlasny FCM token.
        if ($this->memberId !== null) {
            $memberId = $this->memberId;
        } else {
            $this->requireScope('devices:write');
            $memberId = (int)($input['member_id'] ?? 0);
            if ($memberId <= 0) {
                $this->error('member_id wymagane.', 400, 'missing_member_id');
            }
        }
        if ($token === '') {
            $this->error('token wymagany.', 400, 'missing_token');
        }

        (new DeviceTokenModel())->register($memberId, $token, $platform);

        if ($this->memberId !== null && $this->memberTokenId !== null) {
            $stmt = Database::pdo()->prepare("SELECT id FROM device_tokens WHERE token = ?");
            $stmt->execute([$token]);
            $dtId = (int)($stmt->fetchColumn() ?: 0);
            if ($dtId > 0) {
                (new MemberApiTokenModel())->attachDeviceToken($this->memberTokenId, $dtId);
            }
        }

        $this->json(['status' => 'ok', 'message' => 'Token zarejestrowany.']);
    }

    public function unregister(): void
    {
        $input = json_decode(file_get_contents('php://input') ?: '', true) ?: $_POST;
        $token = trim((string)($input['token'] ?? ''));
        if ($token !== '') {
            (new DeviceTokenModel())->unregister($token);
        }
        $this->json(['status' => 'ok']);
    }
}
