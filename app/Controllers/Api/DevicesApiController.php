<?php

namespace App\Controllers\Api;

use App\Models\DeviceTokenModel;

class DevicesApiController extends BaseApiController
{
    public function register(): void
    {
        $this->requireScope('devices:write');
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $token    = trim($input['token'] ?? '');
        $platform = in_array($input['platform'] ?? '', ['android','ios','web'], true) ? $input['platform'] : 'android';
        $memberId = (int)($input['member_id'] ?? 0);

        if ($token === '' || $memberId <= 0) {
            $this->error('token i member_id wymagane.', 400);
        }

        (new DeviceTokenModel())->register($memberId, $token, $platform);
        $this->json(['status' => 'ok', 'message' => 'Token zarejestrowany.']);
    }

    public function unregister(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $token = trim($input['token'] ?? '');
        if ($token !== '') {
            (new DeviceTokenModel())->unregister($token);
        }
        $this->json(['status' => 'ok']);
    }
}
