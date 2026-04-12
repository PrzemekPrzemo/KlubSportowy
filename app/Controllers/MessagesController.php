<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Models\MessageModel;
use App\Models\UserModel;

class MessagesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function inbox(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $model = new MessageModel();
        $pagination = $model->inbox((int)Auth::id(), 'user', $page);
        $unread = $model->countUnread((int)Auth::id(), 'user');

        $this->render('messages/inbox', [
            'title'      => 'Wiadomości — Odebrane',
            'pagination' => $pagination,
            'unread'     => $unread,
            'tab'        => 'inbox',
        ]);
    }

    public function sent(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = (new MessageModel())->sent((int)Auth::id(), 'user', $page);

        $this->render('messages/inbox', [
            'title'      => 'Wiadomości — Wysłane',
            'pagination' => $pagination,
            'unread'     => 0,
            'tab'        => 'sent',
        ]);
    }

    public function compose(): void
    {
        $users   = (new UserModel())->findAll('full_name');
        $members = (new MemberModel())->findAll('last_name');

        $parentId = isset($_GET['reply_to']) ? (int)$_GET['reply_to'] : null;
        $parent = null;
        if ($parentId) {
            $parent = (new MessageModel())->findById($parentId);
        }

        $this->render('messages/compose', [
            'title'   => $parent ? 'Odpowiedz' : 'Nowa wiadomość',
            'users'   => $users,
            'members' => $members,
            'parent'  => $parent,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();

        $subject       = trim($_POST['subject'] ?? '');
        $body          = trim($_POST['body'] ?? '');
        $recipientType = in_array($_POST['recipient_type'] ?? '', ['user', 'member', 'group'], true)
            ? $_POST['recipient_type'] : 'user';
        $recipientId   = !empty($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : null;
        $groupScope    = in_array($_POST['group_scope'] ?? '', ['club', 'sport', 'team'], true)
            ? $_POST['group_scope'] : null;
        $groupId       = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;
        $parentId      = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        if ($subject === '' || $body === '') {
            Session::flash('error', 'Temat i treść wiadomości są wymagane.');
            $this->redirect('messages/compose');
        }

        $data = [
            'sender_type'    => 'user',
            'sender_id'      => Auth::id(),
            'recipient_type' => $recipientType,
            'recipient_id'   => $recipientId,
            'group_scope'    => $groupScope,
            'group_id'       => $groupId,
            'subject'        => $subject,
            'body'           => $body,
            'parent_id'      => $parentId,
        ];

        (new MessageModel())->insert($data);
        Session::flash('success', 'Wiadomość została wysłana.');
        $this->redirect('messages');
    }

    public function show(string $id): void
    {
        $model = new MessageModel();
        $message = $model->findById((int)$id);

        if (!$message) {
            Session::flash('error', 'Wiadomość nie została znaleziona.');
            $this->redirect('messages');
        }

        // Oznacz jako przeczytaną jeśli to odbiorca
        if ($message['recipient_type'] === 'user'
            && (int)$message['recipient_id'] === (int)Auth::id()
            && $message['read_at'] === null
        ) {
            $model->markRead((int)$id);
        }

        // Pobierz wątek: jeśli to jest reply, pokaż wątek parenta
        $threadParentId = $message['parent_id'] ? (int)$message['parent_id'] : (int)$id;
        $thread = $model->thread($threadParentId);

        // Oznacz odpowiedzi w wątku jako przeczytane
        foreach ($thread as $msg) {
            if ($msg['recipient_type'] === 'user'
                && (int)$msg['recipient_id'] === (int)Auth::id()
                && $msg['read_at'] === null
            ) {
                $model->markRead((int)$msg['id']);
            }
        }

        $this->render('messages/show', [
            'title'   => $message['subject'],
            'message' => $message,
            'thread'  => $thread,
        ]);
    }

    public function markRead(string $id): void
    {
        Csrf::verify();
        (new MessageModel())->markRead((int)$id);
        $this->redirect('messages');
    }
}
