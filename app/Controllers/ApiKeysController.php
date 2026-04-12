<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\ApiKeyModel;

class ApiKeysController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad']);
    }

    public function index(): void
    {
        $keys = (new ApiKeyModel())->findAll('created_at', 'DESC');
        $this->render('club/api_keys', [
            'title' => 'Klucze API',
            'keys'  => $keys,
        ]);
    }

    public function generate(): void
    {
        Csrf::verify();
        $name  = trim($_POST['name'] ?? '');
        $limit = max(1, (int)($_POST['rate_limit'] ?? 60));
        if ($name === '') {
            Session::flash('error', 'Nazwa klucza jest wymagana.');
            $this->redirect('club/api-keys');
        }

        $scopes = $_POST['scopes'] ?? [];
        if (!is_array($scopes)) $scopes = [];

        $model  = new ApiKeyModel();
        $result = $model->generate($this->currentClub(), $name, $scopes, $limit, Auth::id());

        Session::flash('success', 'Klucz wygenerowany. SKOPIUJ GO TERAZ — nie zobaczysz go ponownie: ' . $result['raw_key']);
        $this->redirect('club/api-keys');
    }

    public function revoke(string $id): void
    {
        Csrf::verify();
        (new ApiKeyModel())->update((int)$id, ['is_active' => 0]);
        Session::flash('success', 'Klucz dezaktywowany.');
        $this->redirect('club/api-keys');
    }
}
