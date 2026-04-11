<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\AnnouncementModel;
use App\Models\SportModel;

class AnnouncementsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = (new AnnouncementModel())->listForClub($page, 20);
        $this->render('announcements/index', [
            'title'      => 'Ogłoszenia',
            'pagination' => $pagination,
        ]);
    }

    public function create(): void
    {
        $sports = (new SportModel())->listForClub($this->currentClub());
        $this->render('announcements/form', [
            'title'        => 'Nowe ogłoszenie',
            'announcement' => null,
            'sports'       => $sports,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        $data['author_id'] = Auth::id();
        (new AnnouncementModel())->insert($data);
        Session::flash('success', 'Ogłoszenie opublikowane.');
        $this->redirect('announcements');
    }

    public function edit(string $id): void
    {
        $a = (new AnnouncementModel())->findById((int)$id);
        if (!$a) {
            Session::flash('error', 'Nie znaleziono.');
            $this->redirect('announcements');
        }
        $sports = (new SportModel())->listForClub($this->currentClub());
        $this->render('announcements/form', [
            'title'        => 'Edycja ogłoszenia',
            'announcement' => $a,
            'sports'       => $sports,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        (new AnnouncementModel())->update((int)$id, $data);
        Session::flash('success', 'Zapisano.');
        $this->redirect('announcements');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new AnnouncementModel())->delete((int)$id);
        Session::flash('success', 'Ogłoszenie usunięte.');
        $this->redirect('announcements');
    }

    private function parsePost(): ?array
    {
        $data = [
            'title'        => trim($_POST['title'] ?? ''),
            'content'      => trim($_POST['content'] ?? ''),
            'priority'     => in_array($_POST['priority'] ?? '', ['normal','important','urgent'], true) ? $_POST['priority'] : 'normal',
            'target'       => in_array($_POST['target'] ?? '', ['staff','members','all','public'], true) ? $_POST['target'] : 'members',
            'published'    => isset($_POST['published']) ? 1 : 0,
            'sport_id'     => !empty($_POST['sport_id']) ? (int)$_POST['sport_id'] : null,
            'publish_from' => trim($_POST['publish_from'] ?? '') ?: null,
            'publish_to'   => trim($_POST['publish_to'] ?? '') ?: null,
        ];
        if ($data['title'] === '' || $data['content'] === '') {
            Session::flash('error', 'Tytuł i treść są wymagane.');
            $this->redirect('announcements/create');
            return null;
        }
        if ($data['publish_from']) $data['publish_from'] = str_replace('T', ' ', $data['publish_from']);
        if ($data['publish_to'])   $data['publish_to']   = str_replace('T', ' ', $data['publish_to']);
        return $data;
    }
}
