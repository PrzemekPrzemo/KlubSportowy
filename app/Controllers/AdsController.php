<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\AdModel;

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
        $this->render('admin/ads_form', [
            'title' => 'Nowa reklama',
            'ad'    => null,
        ]);
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
        $this->render('admin/ads_form', [
            'title' => 'Edycja reklamy',
            'ad'    => $ad,
        ]);
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
        $targets   = ['club_panel', 'member_portal', 'public'];
        $positions = ['sidebar', 'top_banner', 'footer'];

        $data = [
            'title'      => trim($_POST['title'] ?? ''),
            'club_id'    => !empty($_POST['club_id']) ? (int)$_POST['club_id'] : null,
            'image_path' => trim($_POST['image_path'] ?? '') ?: null,
            'link_url'   => trim($_POST['link_url'] ?? '') ?: null,
            'target'     => in_array($_POST['target'] ?? '', $targets, true) ? $_POST['target'] : 'club_panel',
            'position'   => in_array($_POST['position'] ?? '', $positions, true) ? $_POST['position'] : 'top_banner',
            'plan_min'   => trim($_POST['plan_min'] ?? '') ?: null,
            'start_date' => trim($_POST['start_date'] ?? '') ?: null,
            'end_date'   => trim($_POST['end_date'] ?? '') ?: null,
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($data['title'] === '') {
            Session::flash('error', 'Tytul reklamy jest wymagany.');
            $this->redirect('admin/ads/create');
            return null;
        }

        return $data;
    }
}
