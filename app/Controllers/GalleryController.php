<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\GalleryAlbumModel;
use App\Models\GalleryPhotoModel;
use App\Models\SportModel;
use App\Models\EventModel;

class GalleryController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $pagination = (new GalleryAlbumModel())->listForClub($page, 20);

        $this->render('gallery/index', [
            'title'      => 'Galeria',
            'pagination' => $pagination,
        ]);
    }

    public function create(): void
    {
        $sports = (new SportModel())->listForClub($this->currentClub());
        $events = (new EventModel())->findAll('id', 'DESC');

        $this->render('gallery/form', [
            'title'  => 'Nowy album',
            'album'  => null,
            'sports' => $sports,
            'events' => $events,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            Session::flash('error', 'Tytuł albumu jest wymagany.');
            $this->redirect('gallery/create');
        }

        $data = [
            'title'       => $title,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'sport_id'    => !empty($_POST['sport_id']) ? (int)$_POST['sport_id'] : null,
            'event_id'    => !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null,
            'is_public'   => isset($_POST['is_public']) ? 1 : 0,
            'created_by'  => Auth::id(),
        ];

        (new GalleryAlbumModel())->insert($data);
        Session::flash('success', 'Album utworzony.');
        $this->redirect('gallery');
    }

    public function show(string $id): void
    {
        $album = (new GalleryAlbumModel())->withPhotos((int)$id);
        if (!$album) {
            Session::flash('error', 'Album nie istnieje.');
            $this->redirect('gallery');
        }

        $this->render('gallery/show', [
            'title' => $album['title'],
            'album' => $album,
        ]);
    }

    public function upload(string $id): void
    {
        Csrf::verify();

        $albumModel = new GalleryAlbumModel();
        $album      = $albumModel->findById((int)$id);
        if (!$album) {
            Session::flash('error', 'Album nie istnieje.');
            $this->redirect('gallery');
        }

        if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Nie wybrano pliku lub wystapil blad przesylania.');
            $this->redirect('gallery/' . (int)$id);
        }

        $photoModel = new GalleryPhotoModel();
        $path       = $photoModel->upload($_FILES['photo'], $this->currentClub(), (int)$id);

        if ($path === null) {
            Session::flash('error', 'Nieprawidlowy format pliku. Dozwolone: jpg, jpeg, png, gif, webp.');
            $this->redirect('gallery/' . (int)$id);
        }

        $photoModel->insert([
            'album_id'    => (int)$id,
            'file_path'   => $path,
            'caption'     => trim($_POST['caption'] ?? '') ?: null,
            'uploaded_by' => Auth::id(),
        ]);

        // Set as cover if album has no cover yet
        if (empty($album['cover_path'])) {
            $albumModel->update((int)$id, ['cover_path' => $path]);
        }

        Session::flash('success', 'Zdjecie dodane.');
        $this->redirect('gallery/' . (int)$id);
    }

    public function delete(string $id): void
    {
        Csrf::verify();

        $albumModel = new GalleryAlbumModel();
        $album      = $albumModel->withPhotos((int)$id);

        if ($album) {
            // Remove photo files
            foreach ($album['photos'] as $photo) {
                $filePath = ROOT_PATH . '/public/' . $photo['file_path'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                if (!empty($photo['thumbnail_path'])) {
                    $thumbPath = ROOT_PATH . '/public/' . $photo['thumbnail_path'];
                    if (file_exists($thumbPath)) {
                        @unlink($thumbPath);
                    }
                }
            }

            // Remove album directory
            $dir = ROOT_PATH . '/public/uploads/gallery/' . (int)$album['club_id'] . '/' . (int)$album['id'];
            if (is_dir($dir)) {
                @rmdir($dir);
            }
        }

        $albumModel->delete((int)$id);
        Session::flash('success', 'Album usuniety.');
        $this->redirect('gallery');
    }

    public function deletePhoto(string $id): void
    {
        Csrf::verify();

        $photoModel = new GalleryPhotoModel();
        $photo      = $photoModel->findById((int)$id);

        if (!$photo) {
            Session::flash('error', 'Zdjecie nie istnieje.');
            $this->redirect('gallery');
        }

        $albumId = (int)$photo['album_id'];

        // Verify album belongs to current club
        $album = (new GalleryAlbumModel())->findById($albumId);
        if (!$album) {
            Session::flash('error', 'Brak dostepu.');
            $this->redirect('gallery');
        }

        // Remove file
        $filePath = ROOT_PATH . '/public/' . $photo['file_path'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        if (!empty($photo['thumbnail_path'])) {
            $thumbPath = ROOT_PATH . '/public/' . $photo['thumbnail_path'];
            if (file_exists($thumbPath)) {
                @unlink($thumbPath);
            }
        }

        // If this was the cover, clear it
        if ($album['cover_path'] === $photo['file_path']) {
            (new GalleryAlbumModel())->update($albumId, ['cover_path' => null]);
        }

        $photoModel->delete((int)$id);
        Session::flash('success', 'Zdjecie usuniete.');
        $this->redirect('gallery/' . $albumId);
    }
}
