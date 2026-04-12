<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\WebhookService;
use App\Models\WebhookEndpointModel;

class WebhooksController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad']);
    }

    /**
     * Lista endpointow + ostatnie logi.
     */
    public function index(): void
    {
        $clubId = $this->currentClub();
        $model  = new WebhookEndpointModel();

        $endpoints = $model->findAll('id', 'DESC');
        $logs      = $model->recentLogs($clubId, 30);

        $this->render('webhooks/index', [
            'title'     => 'Webhooki',
            'endpoints' => $endpoints,
            'logs'      => $logs,
        ]);
    }

    /**
     * Formularz nowego endpointu.
     */
    public function create(): void
    {
        $this->render('webhooks/form', [
            'title'           => 'Nowy webhook',
            'endpoint'        => null,
            'availableEvents' => WebhookService::availableEvents(),
        ]);
    }

    /**
     * Zapis nowego endpointu.
     */
    public function store(): void
    {
        Csrf::verify();

        $url    = trim($_POST['url'] ?? '');
        $secret = trim($_POST['secret'] ?? '');
        $events = $_POST['events'] ?? [];

        if ($url === '' || $secret === '' || empty($events)) {
            Session::flash('error', 'URL, secret i przynajmniej jeden event sa wymagane.');
            $this->redirect('club/webhooks/create');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Session::flash('error', 'Podaj prawidlowy URL.');
            $this->redirect('club/webhooks/create');
        }

        $allowed = WebhookService::availableEvents();
        $events  = array_values(array_intersect((array)$events, $allowed));

        (new WebhookEndpointModel())->insert([
            'url'    => $url,
            'secret' => $secret,
            'events' => json_encode($events),
        ]);

        Session::flash('success', 'Webhook dodany.');
        $this->redirect('club/webhooks');
    }

    /**
     * Usun endpoint.
     */
    public function delete(string $id): void
    {
        Csrf::verify();
        (new WebhookEndpointModel())->delete((int)$id);
        Session::flash('success', 'Webhook usuniety.');
        $this->redirect('club/webhooks');
    }
}
