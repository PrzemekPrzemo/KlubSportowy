<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\AdModel;
use App\Models\ClubModel;
use App\Models\SportModel;
use App\Models\SubscriptionModel;

class AdsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    /**
     * List all ads.
     */
    public function index(): void
    {
        $ads = (new AdModel())->listAll();
        $this->render('admin/ads', [
            'title' => 'Reklamy',
            'ads'   => $ads,
        ]);
    }

    /**
     * Show create form.
     */
    public function create(): void
    {
        $this->render('admin/ads_form', $this->formData(null));
    }

    /**
     * Store a new ad.
     */
    public function store(): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) {
            return;
        }
        (new AdModel())->insert($data);
        Session::flash('success', 'Reklama utworzona.');
        $this->redirect('admin/ads');
    }

    /**
     * Show edit form.
     */
    public function edit(string $id): void
    {
        $ad = (new AdModel())->findById((int)$id);
        if (!$ad) {
            Session::flash('error', 'Nie znaleziono reklamy.');
            $this->redirect('admin/ads');
        }
        $this->render('admin/ads_form', $this->formData($ad));
    }

    /**
     * Zwraca paczke danych dla widoku ads_form (lista klubow, sportow, planow).
     * @param array|null $ad istniejaca reklama lub null
     */
    private function formData(?array $ad): array
    {
        return [
            'title'         => $ad ? 'Edycja reklamy' : 'Nowa reklama',
            'ad'            => $ad,
            'clubs'         => (new ClubModel())->listActive(),
            'sports'        => (new SportModel())->listActive(),
            'plans'         => (new SubscriptionModel())->listPlans(),
            'audienceTypes' => [
                'all'    => 'Wszyscy (uniwersalna)',
                'club'   => 'Tylko wskazany klub',
                'sport'  => 'Zawodnicy z wskazana sekcja',
                'member' => 'Tylko wskazany zawodnik',
                'plan'   => 'Wszyscy na planie X+',
            ],
        ];
    }

    /**
     * Update existing ad.
     */
    public function update(string $id): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) {
            return;
        }
        (new AdModel())->update((int)$id, $data);
        Session::flash('success', 'Reklama zaktualizowana.');
        $this->redirect('admin/ads');
    }

    /**
     * Delete an ad.
     */
    public function delete(string $id): void
    {
        Csrf::verify();
        (new AdModel())->delete((int)$id);
        Session::flash('success', 'Reklama usunieta.');
        $this->redirect('admin/ads');
    }

    /**
     * Parse POST data for ad create/update.
     */
    private function parsePost(): ?array
    {
        $targets       = ['club_panel', 'member_portal', 'public'];
        $positions     = ['sidebar', 'top_banner', 'footer'];
        $audienceTypes = ['all', 'club', 'sport', 'member', 'plan'];

        $audience = in_array($_POST['audience_type'] ?? '', $audienceTypes, true)
            ? $_POST['audience_type'] : 'all';

        // Wymuszaj spojnosc audience_type → wymagane FK:
        //   audience='club'   → club_id required
        //   audience='sport'  → sport_id required
        //   audience='member' → member_id required (klub i sport opcjonalne)
        //   audience='plan'   → plan_min required
        //   audience='all'    → wszystko opcjonalne, FK reset
        $clubId   = !empty($_POST['club_id'])   ? (int)$_POST['club_id']   : null;
        $sportId  = !empty($_POST['sport_id'])  ? (int)$_POST['sport_id']  : null;
        $memberId = !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null;
        $planMin  = trim($_POST['plan_min'] ?? '') ?: null;

        if ($audience === 'all') {
            // ad jest globalna — czyscimy specyficzne FK by uniknac niespojnosci
            $sportId = $memberId = null;
            // club_id zostawiamy: NULL = globalna, wartosc = scope per klub
        }

        $data = [
            'title'         => trim($_POST['title'] ?? ''),
            'club_id'       => $clubId,
            'sport_id'      => $sportId,
            'member_id'     => $memberId,
            'audience_type' => $audience,
            'image_path'    => trim($_POST['image_path'] ?? '') ?: null,
            'link_url'      => trim($_POST['link_url'] ?? '') ?: null,
            'target'        => in_array($_POST['target'] ?? '', $targets, true) ? $_POST['target'] : 'club_panel',
            'position'      => in_array($_POST['position'] ?? '', $positions, true) ? $_POST['position'] : 'top_banner',
            'plan_min'      => $planMin,
            'start_date'    => trim($_POST['start_date'] ?? '') ?: null,
            'end_date'      => trim($_POST['end_date'] ?? '') ?: null,
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($data['title'] === '') {
            Session::flash('error', 'Tytul reklamy jest wymagany.');
            $this->redirect('admin/ads/create');
            return null;
        }

        // Walidacja zaleznosci audience → FK
        $missing = match ($audience) {
            'club'   => $clubId   === null ? 'club_id'   : null,
            'sport'  => $sportId  === null ? 'sport_id'  : null,
            'member' => $memberId === null ? 'member_id' : null,
            'plan'   => $planMin  === null ? 'plan_min'  : null,
            default  => null,
        };
        if ($missing !== null) {
            Session::flash('error', 'Targetowanie ' . $audience . ' wymaga ' . $missing . '.');
            $this->redirect('admin/ads/create');
            return null;
        }

        return $data;
    }
}
