<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\EventModel;
use App\Models\MemberModel;
use App\Models\ResultImageModel;
use App\Models\SportModel;

class ResultImageController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    /**
     * List all result images.
     */
    public function index(): void
    {
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $pagination = (new ResultImageModel())->listImages($page, 20);

        $this->render('results/index', [
            'title'      => 'Zdjecia wynikow',
            'pagination' => $pagination,
        ]);
    }

    /**
     * Upload form.
     */
    public function upload(): void
    {
        $events  = (new EventModel())->findAll('event_date', 'DESC');
        $members = (new MemberModel())->findAll('last_name', 'ASC');
        $sports  = (new SportModel())->listForClub($this->currentClub());

        $this->render('results/upload', [
            'title'   => 'Dodaj zdjecie wyniku',
            'events'  => $events,
            'members' => $members,
            'sports'  => $sports,
        ]);
    }

    /**
     * Handle file upload (POST).
     */
    public function storeUpload(): void
    {
        Csrf::verify();

        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Nie wybrano pliku lub wystapil blad przesylania.');
            $this->redirect('results/upload');
        }

        $model   = new ResultImageModel();
        $clubId  = $this->currentClub();
        $path    = $model->uploadFile($_FILES['image'], $clubId);

        if ($path === null) {
            Session::flash('error', 'Nieprawidlowy format pliku. Dozwolone: jpg, jpeg, png, gif, webp, bmp.');
            $this->redirect('results/upload');
        }

        $model->insert([
            'club_id'           => $clubId,
            'event_id'          => !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null,
            'member_id'         => !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null,
            'sport_id'          => !empty($_POST['sport_id']) ? (int)$_POST['sport_id'] : null,
            'image_path'        => $path,
            'original_filename' => $_FILES['image']['name'],
            'status'            => 'uploaded',
            'uploaded_by'       => Auth::id(),
        ]);

        Session::flash('success', 'Zdjecie przeslane.');
        $this->redirect('results');
    }

    /**
     * Show single result image with manual data entry form.
     */
    public function show(string $id): void
    {
        $model = new ResultImageModel();
        $image = $model->findWithRelations((int)$id);

        if (!$image) {
            Session::flash('error', 'Zdjecie nie istnieje.');
            $this->redirect('results');
        }

        $events  = (new EventModel())->findAll('event_date', 'DESC');
        $members = (new MemberModel())->findAll('last_name', 'ASC');
        $sports  = (new SportModel())->listForClub($this->currentClub());

        $this->render('results/show', [
            'title'   => 'Zdjecie wyniku #' . $image['id'],
            'image'   => $image,
            'events'  => $events,
            'members' => $members,
            'sports'  => $sports,
        ]);
    }

    /**
     * Save extracted data (manual entry).
     */
    public function save(string $id): void
    {
        Csrf::verify();

        $model = new ResultImageModel();
        $image = $model->findById((int)$id);

        if (!$image) {
            Session::flash('error', 'Zdjecie nie istnieje.');
            $this->redirect('results');
        }

        // Build extracted data JSON from form fields
        $extractedData = [];
        if (!empty($_POST['scores'])) {
            $extractedData['scores'] = $_POST['scores'];
        }
        if (!empty($_POST['position'])) {
            $extractedData['position'] = $_POST['position'];
        }
        if (!empty($_POST['time_result'])) {
            $extractedData['time_result'] = $_POST['time_result'];
        }
        if (!empty($_POST['distance'])) {
            $extractedData['distance'] = $_POST['distance'];
        }
        if (!empty($_POST['points'])) {
            $extractedData['points'] = $_POST['points'];
        }
        if (!empty($_POST['notes'])) {
            $extractedData['notes'] = $_POST['notes'];
        }

        $data = [
            'extracted_data' => !empty($extractedData) ? json_encode($extractedData, JSON_UNESCAPED_UNICODE) : null,
            'status'         => 'processed',
            'event_id'       => !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null,
            'member_id'      => !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null,
            'sport_id'       => !empty($_POST['sport_id']) ? (int)$_POST['sport_id'] : null,
        ];

        if (isset($_POST['status']) && in_array($_POST['status'], ['uploaded', 'processed', 'verified'], true)) {
            $data['status'] = $_POST['status'];
        }

        $model->update((int)$id, $data);
        Session::flash('success', 'Dane zapisane.');
        $this->redirect('results/' . (int)$id);
    }

    /**
     * Delete result image.
     */
    public function deleteImage(string $id): void
    {
        Csrf::verify();

        $model = new ResultImageModel();
        $image = $model->findById((int)$id);

        if ($image) {
            // Remove physical file
            $filePath = ROOT_PATH . '/public/' . $image['image_path'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            $model->delete((int)$id);
            Session::flash('success', 'Zdjecie usuniete.');
        } else {
            Session::flash('error', 'Zdjecie nie istnieje.');
        }

        $this->redirect('results');
    }
}
