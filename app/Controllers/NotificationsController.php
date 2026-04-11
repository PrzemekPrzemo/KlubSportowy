<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\NotificationModel;

class NotificationsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
    }

    public function markRead(string $id): void
    {
        Csrf::verify();
        (new NotificationModel())->markRead((int)$id, (int)Auth::id());
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref && parse_url($ref, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? '')) {
            header('Location: ' . $ref);
        } else {
            $this->redirect('dashboard');
        }
        exit;
    }
}
