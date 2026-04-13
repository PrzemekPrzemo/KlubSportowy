<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;

/**
 * Support tickets — dostępne dla zarządu klubu.
 * Tworzenie zgłoszeń do master admina.
 */
class SupportController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $db     = Database::pdo();
        $clubId = $this->currentClub();
        $stmt   = $db->prepare(
            "SELECT * FROM support_tickets WHERE club_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$clubId]);
        $this->render('support/index', [
            'title'   => 'Zgłoszenia do supportu',
            'tickets' => $stmt->fetchAll(),
        ]);
    }

    public function create(): void
    {
        $this->render('support/form', ['title' => 'Nowe zgłoszenie']);
    }

    public function store(): void
    {
        Csrf::verify();
        $db = Database::pdo();
        $db->prepare(
            "INSERT INTO support_tickets (club_id, user_id, subject, body, priority, category)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([
            $this->currentClub(),
            Auth::id(),
            trim($_POST['subject'] ?? ''),
            trim($_POST['body'] ?? ''),
            in_array($_POST['priority'] ?? '', ['low','normal','high','urgent'], true) ? $_POST['priority'] : 'normal',
            in_array($_POST['category'] ?? '', ['technical','billing','feature','bug','other'], true) ? $_POST['category'] : 'technical',
        ]);
        Session::flash('success', 'Zgłoszenie wysłane do supportu.');
        $this->redirect('support');
    }

    public function show(string $id): void
    {
        $db   = Database::pdo();
        $stmt = $db->prepare("SELECT * FROM support_tickets WHERE id = ? AND club_id = ?");
        $stmt->execute([(int)$id, $this->currentClub()]);
        $ticket = $stmt->fetch();
        if (!$ticket) { Session::flash('error', 'Nie znaleziono.'); $this->redirect('support'); }

        $replies = $db->prepare(
            "SELECT r.*, u.full_name AS author_name
             FROM support_replies r LEFT JOIN users u ON u.id = r.user_id
             WHERE r.ticket_id = ? ORDER BY r.created_at ASC"
        );
        $replies->execute([(int)$id]);

        $this->render('support/show', [
            'title'   => 'Zgłoszenie #' . $id,
            'ticket'  => $ticket,
            'replies' => $replies->fetchAll(),
        ]);
    }

    public function reply(string $id): void
    {
        Csrf::verify();
        $db = Database::pdo();
        $db->prepare("INSERT INTO support_replies (ticket_id, user_id, body) VALUES (?, ?, ?)")
           ->execute([(int)$id, Auth::id(), trim($_POST['body'] ?? '')]);
        Session::flash('success', 'Odpowiedź wysłana.');
        $this->redirect('support/' . $id);
    }
}
