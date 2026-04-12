<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\EventModel;
use App\Models\LivestreamModel;

class LivestreamController extends BaseController
{
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }

    public function index(): void
    {
        $status = $_GET['status'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = (new LivestreamModel())->listForClub($status ?: null, $page, 20);
        $this->render('livestream/index', ['title' => 'Transmisje', 'pagination' => $pagination, 'statusFilter' => $status]);
    }

    public function create(): void
    {
        $events = (new EventModel())->upcomingForClub(50);
        $this->render('livestream/form', ['title' => 'Nowa transmisja', 'stream' => null, 'events' => $events]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = [
            'event_id'     => !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null,
            'title'        => trim($_POST['title'] ?? ''),
            'platform'     => in_array($_POST['platform'] ?? '', ['youtube','twitch','facebook','inne'], true) ? $_POST['platform'] : 'youtube',
            'stream_url'   => trim($_POST['stream_url'] ?? ''),
            'status'       => in_array($_POST['status'] ?? '', ['zaplanowana','na_zywo','zakonczona'], true) ? $_POST['status'] : 'zaplanowana',
            'scheduled_at' => trim($_POST['scheduled_at'] ?? '') ?: null,
            'is_public'    => isset($_POST['is_public']) ? 1 : 0,
            'created_by'   => Auth::id(),
        ];
        if ($data['title'] === '' || $data['stream_url'] === '') {
            Session::flash('error', 'Tytuł i URL transmisji wymagane.');
            $this->redirect('livestream/create');
        }
        $data['embed_code'] = LivestreamModel::generateEmbed($data['stream_url'], $data['platform']);
        if ($data['scheduled_at']) $data['scheduled_at'] = str_replace('T', ' ', $data['scheduled_at']);
        (new LivestreamModel())->insert($data);
        Session::flash('success', 'Transmisja dodana.');
        $this->redirect('livestream');
    }

    public function watch(string $id): void
    {
        $stream = (new LivestreamModel())->findById((int)$id);
        if (!$stream) { Session::flash('error', 'Nie znaleziono.'); $this->redirect('livestream'); }
        $this->render('livestream/watch', ['title' => $stream['title'], 'stream' => $stream]);
    }

    public function setStatus(string $id): void
    {
        Csrf::verify();
        $status = in_array($_POST['status'] ?? '', ['zaplanowana','na_zywo','zakonczona'], true) ? $_POST['status'] : 'zaplanowana';
        $data = ['status' => $status];
        if ($status === 'na_zywo') $data['started_at'] = date('Y-m-d H:i:s');
        if ($status === 'zakonczona') $data['ended_at'] = date('Y-m-d H:i:s');
        (new LivestreamModel())->update((int)$id, $data);
        Session::flash('success', 'Status zmieniony.');
        $this->redirect('livestream');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new LivestreamModel())->delete((int)$id);
        Session::flash('success', 'Transmisja usunięta.');
        $this->redirect('livestream');
    }
}
