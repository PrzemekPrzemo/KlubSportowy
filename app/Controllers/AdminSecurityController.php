<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\RateLimiter;
use App\Helpers\Session;
use App\Models\ActivityLogModel;
use App\Models\SecurityEventModel;

class AdminSecurityController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    public function index(): void
    {
        $type = $_GET['type'] ?? null;
        $ip   = $_GET['ip'] ?? null;
        $from = $_GET['from'] ?? null;
        $to   = $_GET['to'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));

        $model = new SecurityEventModel();
        $pagination = $model->listFiltered($type, $ip, $from, $to, $page, 30);
        $stats = $model->stats24h();

        $this->render('admin/security/index', [
            'title'      => 'Bezpieczeństwo — dziennik zdarzeń',
            'pagination' => $pagination,
            'stats'      => $stats,
            'filter'     => compact('type', 'ip', 'from', 'to'),
        ]);
    }

    public function blockedIps(): void
    {
        $ips = (new SecurityEventModel())->blockedIps();

        $this->render('admin/security/blocked_ips', [
            'title' => 'Zablokowane / podejrzane IP',
            'ips'   => $ips,
        ]);
    }

    public function unblockIp(string $ip): void
    {
        Csrf::verify();
        $ip = urldecode($ip);
        RateLimiter::reset($ip, 'login');
        (new ActivityLogModel())->log('security_unblock_ip', 'rate_limits', null, "ip={$ip}");
        Session::flash('success', "Odblokowano IP: {$ip}");
        $this->redirect(url('admin/security/blocked-ips'));
    }
}
