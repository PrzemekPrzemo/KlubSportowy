<?php

namespace App\Controllers\Api\Mobile;

use App\Models\MemberModel;

/**
 * Mobile API v1 — current-member profile endpoints.
 * Re-uses MemberModel for read/update.
 */
class MeController extends V1Controller
{
    /** GET /api/mobile/v1/me */
    public function show(): void
    {
        $this->requireAuth();
        $m = $this->member;

        $this->json([
            'id'             => (int)$m['id'],
            'club_id'        => (int)$m['club_id'],
            'member_number'  => $m['member_number'] ?? null,
            'first_name'     => $m['first_name'] ?? null,
            'last_name'      => $m['last_name'] ?? null,
            'email'          => $m['email'] ?? null,
            'phone'          => $m['phone'] ?? null,
            'gender'         => $m['gender'] ?? null,
            'birth_date'     => $m['birth_date'] ?? null,
            'nationality'    => $m['nationality'] ?? null,
            'address_street' => $m['address_street'] ?? null,
            'address_city'   => $m['address_city'] ?? null,
            'address_postal' => $m['address_postal'] ?? null,
            'photo_path'     => $m['photo_path'] ?? null,
            'join_date'      => $m['join_date'] ?? null,
            'status'         => $m['status'] ?? null,
            'club'           => $this->club,
        ]);
    }

    /** PATCH /api/mobile/v1/me */
    public function update(): void
    {
        $this->requireAuth();
        $input = $this->input();

        // Whitelist editable fields only.
        $allowed = ['phone', 'address_street', 'address_city', 'address_postal'];
        $data = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $input)) {
                $v = $input[$k];
                $data[$k] = ($v === '' || $v === null) ? null : trim((string)$v);
            }
        }
        if (!$data) {
            $this->error('Brak pól do aktualizacji.', 422, 'validation');
        }

        (new MemberModel())->withoutScope()->update($this->memberId, $data);

        $this->json(['updated' => array_keys($data)]);
    }

    /** POST /api/mobile/v1/me/avatar — multipart upload (field: avatar) */
    public function uploadAvatar(): void
    {
        $this->requireAuth();
        if (empty($_FILES['avatar']) || !is_uploaded_file($_FILES['avatar']['tmp_name'])) {
            $this->error('Brak pliku avatar.', 422, 'validation', ['avatar' => 'required']);
        }
        $file = $_FILES['avatar'];

        $maxBytes = 5 * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            $this->error('Plik za duży (max 5MB).', 422, 'file_too_large');
        }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = function_exists('mime_content_type')
            ? mime_content_type($file['tmp_name'])
            : ($file['type'] ?? '');
        if (!isset($allowed[$mime])) {
            $this->error('Dozwolone formaty: JPEG, PNG, WebP.', 422, 'invalid_file_type');
        }

        $dir = ROOT_PATH . '/storage/uploads/members/' . $this->memberId;
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            $this->error('Nie udało się utworzyć katalogu.', 500, 'storage_error');
        }
        $filename = 'avatar_' . time() . '.' . $allowed[$mime];
        $target = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            $this->error('Nie udało się zapisać pliku.', 500, 'storage_error');
        }

        $relative = 'uploads/members/' . $this->memberId . '/' . $filename;
        (new MemberModel())->withoutScope()->update($this->memberId, ['photo_path' => $relative]);

        $this->json(['photo_path' => $relative]);
    }
}
