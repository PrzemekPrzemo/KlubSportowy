<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\SportModuleLoader;
use App\Models\ActivityLogModel;
use App\Models\ClubModel;
use App\Models\ClubSettingsModel;
use App\Models\RolePermissionModel;

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

    // ── Uprawnienia per-klub (Batch A4) ──────────────────────────────────────
    private const ROLES = ['zarzad', 'trener', 'instruktor', 'sedzia', 'lekarz', 'ksiegowy'];
    private const MODULES = [
        'members'       => 'Zawodnicy',
        'sports'        => 'Sporty',
        'fees'          => 'Składki',
        'events'        => 'Wydarzenia',
        'trainings'     => 'Treningi',
        'calendar'      => 'Kalendarz',
        'medical'       => 'Medyczne',
        'announcements' => 'Ogłoszenia',
        'club'          => 'Klub',
    ];

    public function permissions(string $clubId): void
    {
        $cid  = (int)$clubId;
        $club = (new ClubModel())->findById($cid);
        if (!$club) {
            Session::flash('error', 'Nie znaleziono klubu.');
            $this->redirect('admin/clubs');
        }

        $rpm = new RolePermissionModel();

        $this->render('admin/club_config/permissions', [
            'title'     => 'Uprawnienia: ' . $club['name'],
            'club'      => $club,
            'roles'     => self::ROLES,
            'modules'   => self::MODULES,
            'defaults'  => $rpm->globalDefaultsMatrix(),
            'overrides' => $rpm->clubOverrideMatrix($cid),
        ]);
    }

    public function savePermissions(string $clubId): void
    {
        Csrf::verify();
        $cid = (int)$clubId;
        $rpm = new RolePermissionModel();

        // Build matrix: always write override for the club (no partial overrides in UI).
        $matrix = [];
        $perm = $_POST['perm'] ?? [];
        foreach (self::ROLES as $role) {
            foreach (array_keys(self::MODULES) as $module) {
                $view = !empty($perm[$role][$module]['view']) ? 1 : 0;
                $edit = !empty($perm[$role][$module]['edit']) ? 1 : 0;
                $matrix[$role][$module] = ['view' => $view, 'edit' => $edit];
            }
        }
        $rpm->setAll($matrix, $cid);
        (new ActivityLogModel())->log('club_permissions_save', 'club', $cid);
        Session::flash('success', 'Uprawnienia klubu zapisane.');
        $this->redirect('admin/clubs/' . $cid . '/permissions');
    }

    public function resetPermissions(string $clubId): void
    {
        Csrf::verify();
        $cid = (int)$clubId;
        $deleted = (new RolePermissionModel())->resetForClub($cid);
        (new ActivityLogModel())->log('club_permissions_reset', 'club', $cid, "deleted={$deleted}");
        Session::flash('success', 'Uprawnienia zresetowane do domyślnych globalnych.');
        $this->redirect('admin/clubs/' . $cid . '/permissions');
    }

    // ── Ustawienia per sport per klub (Batch S0) ──────────────────────────────

    public function sportSettings(string $clubId, string $sportKey = ''): void
    {
        $cid  = (int)$clubId;
        $club = (new ClubModel())->findById($cid);
        if (!$club) {
            Session::flash('error', 'Nie znaleziono klubu.');
            $this->redirect('admin/clubs');
        }

        $cs = new ClubSettingsModel();

        if ($sportKey === '') {
            // Overview: all sports with their federation_id and member count
            $allModules = SportModuleLoader::load();
            $db = Database::pdo();

            $sports = [];
            foreach ($allModules as $key => $manifest) {
                $federationId  = $cs->get($cid, 'sport_' . $key . '_federation_id', '');
                $ageCategories = $cs->get($cid, 'sport_' . $key . '_age_categories', '');

                // Count members in this sport section for the club
                $stmt = $db->prepare(
                    'SELECT COUNT(DISTINCT ms.member_id) AS cnt
                       FROM member_sports ms
                       JOIN club_sports cs ON cs.id = ms.club_sport_id
                       JOIN sports s ON s.id = cs.sport_id
                      WHERE cs.club_id = ? AND s.key = ?'
                );
                $stmt->execute([$cid, $key]);
                $memberCount = (int)($stmt->fetchColumn() ?: 0);

                $sports[] = [
                    'key'            => $key,
                    'name'           => $manifest['name'],
                    'federation_id'  => $federationId,
                    'age_categories' => $ageCategories,
                    'member_count'   => $memberCount,
                ];
            }

            $this->render('admin/club_config/sport_settings', [
                'title'    => 'Sekcje sportowe: ' . $club['name'],
                'club'     => $club,
                'sports'   => $sports,
                'sportKey' => '',
                'manifest' => [],
                'currentSettings' => [],
            ]);
            return;
        }

        $manifest = SportModuleLoader::get($sportKey);
        if (!$manifest) {
            Session::flash('error', 'Nieznany sport: ' . $sportKey);
            $this->redirect('admin/clubs/' . $cid . '/sports');
        }

        $currentSettings = [
            'federation_id'  => $cs->get($cid, 'sport_' . $sportKey . '_federation_id', ''),
            'age_categories' => $cs->get($cid, 'sport_' . $sportKey . '_age_categories', ''),
            'custom_fields'  => $cs->get($cid, 'sport_' . $sportKey . '_custom_fields', ''),
        ];

        $this->render('admin/club_config/sport_settings', [
            'title'           => 'Ustawienia sportu ' . $manifest['name'] . ': ' . $club['name'],
            'club'            => $club,
            'sportKey'        => $sportKey,
            'manifest'        => $manifest,
            'currentSettings' => $currentSettings,
            'sports'          => [],
        ]);
    }

    public function saveSportSettings(string $clubId, string $sportKey): void
    {
        Csrf::verify();
        $cid = (int)$clubId;

        $manifest = SportModuleLoader::get($sportKey);
        if (!$manifest) {
            Session::flash('error', 'Nieznany sport.');
            $this->redirect('admin/clubs/' . $cid . '/sports');
        }

        $cs  = new ClubSettingsModel();
        $key = $sportKey;

        $federationId  = trim($_POST['federation_id'] ?? '');
        $ageCategories = trim($_POST['age_categories'] ?? '');
        $customFields  = trim($_POST['custom_fields'] ?? '');

        if ($federationId !== '') {
            $cs->set($cid, 'sport_' . $key . '_federation_id', $federationId, 'text', 'ID federacyjny: ' . $manifest['name']);
        }
        if ($ageCategories !== '') {
            json_decode($ageCategories);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Session::flash('error', 'Nieprawidłowy JSON w kategoriach wiekowych.');
                $this->redirect('admin/clubs/' . $cid . '/sports/' . urlencode($key));
            }
            $cs->set($cid, 'sport_' . $key . '_age_categories', $ageCategories, 'json', 'Kategorie wiekowe: ' . $manifest['name']);
        }
        if ($customFields !== '') {
            json_decode($customFields);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Session::flash('error', 'Nieprawidłowy JSON w polach własnych.');
                $this->redirect('admin/clubs/' . $cid . '/sports/' . urlencode($key));
            }
            $cs->set($cid, 'sport_' . $key . '_custom_fields', $customFields, 'json', 'Pola własne: ' . $manifest['name']);
        }

        (new ActivityLogModel())->log('club_sport_settings_save', 'club', $cid, "sport={$key}");
        Session::flash('success', 'Ustawienia sportu ' . $manifest['name'] . ' zapisane.');
        $this->redirect('admin/clubs/' . $cid . '/sports/' . urlencode($key));
    }
}
