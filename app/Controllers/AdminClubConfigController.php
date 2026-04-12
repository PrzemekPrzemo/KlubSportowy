<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\ClubModel;
use App\Models\ClubSettingsModel;

class AdminClubConfigController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    public function settings(string $clubId): void
    {
        $cid  = (int)$clubId;
        $club = (new ClubModel())->findById($cid);
        if (!$club) {
            Session::flash('error', 'Nie znaleziono klubu.');
            $this->redirect('admin/clubs');
        }

        $settings = (new ClubSettingsModel())->getAll($cid);

        $this->render('admin/club_config/settings', [
            'title'    => 'Konfiguracja: ' . $club['name'],
            'club'     => $club,
            'settings' => $settings,
        ]);
    }

    public function saveSettings(string $clubId): void
    {
        Csrf::verify();
        $cid = (int)$clubId;
        $cs  = new ClubSettingsModel();

        $keys   = $_POST['keys'] ?? [];
        $values = $_POST['values'] ?? [];
        $types  = $_POST['types'] ?? [];
        $labels = $_POST['labels'] ?? [];

        if (is_array($keys)) {
            foreach ($keys as $i => $key) {
                $key = trim((string)$key);
                if ($key === '') continue;
                $val   = (string)($values[$i] ?? '');
                $type  = (string)($types[$i] ?? 'text');
                $label = (string)($labels[$i] ?? '');
                $cs->set($cid, $key, $val, $type, $label);
            }
        }

        Session::flash('success', 'Ustawienia zapisane.');
        $this->redirect('admin/clubs/' . $cid . '/config');
    }

    public function features(string $clubId): void
    {
        $cid  = (int)$clubId;
        $club = (new ClubModel())->findById($cid);
        if (!$club) {
            Session::flash('error', 'Nie znaleziono klubu.');
            $this->redirect('admin/clubs');
        }

        $cs = new ClubSettingsModel();
        $modules = [
            'gallery'    => ['label' => 'Galeria',         'desc' => 'Albumy zdjęć i przesyłanie plików multimedialnych.'],
            'messages'   => ['label' => 'Wiadomości',      'desc' => 'Wewnętrzny system wiadomości między użytkownikami.'],
            'bookings'   => ['label' => 'Rezerwacje',      'desc' => 'Rezerwacja obiektów sportowych i sal.'],
            'analytics'  => ['label' => 'Analityka',       'desc' => 'Panel analityczny z wykresami i statystykami.'],
            'shop'       => ['label' => 'Sklep',           'desc' => 'Sklep klubowy z produktami i zamówieniami.'],
            'livestream' => ['label' => 'Transmisje live',  'desc' => 'Streaming na żywo wydarzeń i treningów.'],
        ];

        $flags = [];
        foreach ($modules as $key => $meta) {
            $flags[$key] = [
                'enabled' => $cs->get($cid, 'module_' . $key, '1') === '1',
                'label'   => $meta['label'],
                'desc'    => $meta['desc'],
            ];
        }

        $this->render('admin/club_config/features', [
            'title' => 'Feature flags: ' . $club['name'],
            'club'  => $club,
            'flags' => $flags,
        ]);
    }

    public function saveFeatures(string $clubId): void
    {
        Csrf::verify();
        $cid = (int)$clubId;
        $cs  = new ClubSettingsModel();

        $moduleKeys = ['gallery', 'messages', 'bookings', 'analytics', 'shop', 'livestream'];
        foreach ($moduleKeys as $mk) {
            $val = isset($_POST['module_' . $mk]) ? '1' : '0';
            $cs->set($cid, 'module_' . $mk, $val, 'bool', 'Moduł: ' . $mk);
        }

        Session::flash('success', 'Feature flags zapisane.');
        $this->redirect('admin/clubs/' . $cid . '/features');
    }
}
