<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\EmailQueueModel;
use App\Models\EmailTemplateModel;

class EmailTemplatesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $templates = (new EmailTemplateModel())->listForClub($this->currentClub());
        $this->render('email/templates', [
            'title'     => 'Szablony e-mail',
            'templates' => $templates,
        ]);
    }

    public function edit(string $type): void
    {
        $clubId   = $this->currentClub();
        $template = (new EmailTemplateModel())->resolve($type, $clubId);
        $this->render('email/template_form', [
            'title'        => 'Edycja szablonu: ' . $type,
            'template'     => $template,
            'template_type'=> $type,
        ]);
    }

    public function save(string $type): void
    {
        Csrf::verify();
        $clubId   = $this->currentClub();
        $subject  = trim($_POST['subject'] ?? '');
        $body     = trim($_POST['body'] ?? '');
        $name     = trim($_POST['name'] ?? $type);
        if ($subject === '' || $body === '') {
            Session::flash('error', 'Tytuł i treść wymagane.');
            $this->redirect('email/templates/' . $type);
        }
        $db = \App\Helpers\Database::pdo();
        $stmt = $db->prepare(
            "INSERT INTO email_templates (club_id, template_type, name, subject, body)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE subject = VALUES(subject), body = VALUES(body), name = VALUES(name)"
        );
        $stmt->execute([$clubId, $type, $name, $subject, $body]);
        Session::flash('success', 'Szablon zapisany.');
        $this->redirect('email/templates');
    }

    public function queue(): void
    {
        $status = $_GET['status'] ?? '';
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $pagination = (new EmailQueueModel())->listForClub($status ?: null, $page, 30);
        $this->render('email/queue', [
            'title'      => 'Kolejka e-mail',
            'pagination' => $pagination,
            'status'     => $status,
        ]);
    }
}
