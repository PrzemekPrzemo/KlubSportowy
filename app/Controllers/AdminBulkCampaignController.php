<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\EmailService;
use App\Helpers\MemberFilter;
use App\Helpers\RateLimiter;
use App\Helpers\Session;
use App\Helpers\SmsService;
use App\Models\CampaignModel;
use App\Models\CampaignRecipientModel;
use App\Models\SportModel;

/**
 * Bulk email/SMS marketing campaigns dla zarządu klubu.
 *
 * Flow:
 *   GET  /admin/campaigns                  → lista kampanii klubu
 *   GET  /admin/campaigns/new              → formularz nowej kampanii
 *   POST /admin/campaigns/send             → utwórz + zakolejkuj
 *   GET  /admin/campaigns/:id              → szczegóły + statystyki
 *
 * Wysyłka faktyczna idzie przez CLI: cli/send_campaigns.php
 * (lub bezpośrednio z controllera dla małych kampanii).
 */
class AdminBulkCampaignController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad', 'ksiegowy', 'admin']);
    }

    public function index(): void
    {
        $campaigns = (new CampaignModel())->listForClub(100);
        $this->render('admin/campaigns/index', [
            'title'     => 'Kampanie email/SMS',
            'campaigns' => $campaigns,
        ]);
    }

    public function create(): void
    {
        $clubSports = (new SportModel())->listForClub($this->currentClub());
        $this->render('admin/campaigns/new', [
            'title'      => 'Nowa kampania',
            'clubSports' => $clubSports,
        ]);
    }

    public function send(): void
    {
        Csrf::verify();

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!RateLimiter::check($ip, 'bulk_campaign', 5, 60)) {
            Session::flash('error', 'Przekroczono limit bulk operacji (5/godz). Spróbuj później.');
            $this->redirect('admin/campaigns');
        }
        RateLimiter::hit($ip, 'bulk_campaign', 5, 60);

        $name    = trim((string)($_POST['name'] ?? ''));
        $channel = in_array($_POST['channel'] ?? '', ['email','sms','both'], true)
            ? $_POST['channel'] : 'email';
        $subject = trim((string)($_POST['subject'] ?? ''));
        $body    = trim((string)($_POST['body'] ?? ''));
        $schedule = trim((string)($_POST['schedule_at'] ?? ''));

        if ($name === '' || $body === '') {
            Session::flash('error', 'Nazwa kampanii i treść są wymagane.');
            $this->redirect('admin/campaigns/new');
        }
        if (in_array($channel, ['email','both'], true) && $subject === '') {
            Session::flash('error', 'Temat e-maila jest wymagany.');
            $this->redirect('admin/campaigns/new');
        }

        $filter = MemberFilter::fromRequest($_POST);
        $rows   = MemberFilter::query($this->currentClub(), $filter, 5000);

        if (empty($rows)) {
            Session::flash('warning', 'Filtr nie zwrócił żadnych odbiorców.');
            $this->redirect('admin/campaigns/new');
        }

        // Status: scheduled (if future date) or sending (immediately)
        $scheduledAt = null;
        $status      = 'sending';
        if ($schedule !== '') {
            $ts = strtotime($schedule);
            if ($ts !== false && $ts > time()) {
                $scheduledAt = date('Y-m-d H:i:s', $ts);
                $status      = 'scheduled';
            }
        }

        $campaignModel = new CampaignModel();
        $campaignId = $campaignModel->insert([
            'name'              => substr($name, 0, 120),
            'channel'           => $channel,
            'template_subject'  => $subject !== '' ? substr($subject, 0, 200) : null,
            'template_body'     => $body,
            'recipients_filter' => json_encode($filter, JSON_UNESCAPED_UNICODE),
            'recipients_count'  => 0,
            'status'            => $status,
            'scheduled_at'      => $scheduledAt,
            'created_by'        => Auth::id(),
        ]);

        // Build recipients list per channel
        $recipients = [];
        $countRcp   = 0;
        foreach ($rows as $m) {
            if (in_array($channel, ['email','both'], true) && !empty($m['email'])) {
                $recipients[] = [
                    'member_id'  => (int)$m['id'],
                    'channel'    => 'email',
                    'to_address' => (string)$m['email'],
                ];
                $countRcp++;
            }
            if (in_array($channel, ['sms','both'], true) && !empty($m['phone'])) {
                $recipients[] = [
                    'member_id'  => (int)$m['id'],
                    'channel'    => 'sms',
                    'to_address' => (string)$m['phone'],
                ];
                $countRcp++;
            }
        }

        if (empty($recipients)) {
            $campaignModel->update($campaignId, ['status' => 'failed']);
            Session::flash('error', 'Żaden z odbiorców nie ma uzupełnionego adresu/telefonu.');
            $this->redirect('admin/campaigns');
        }

        (new CampaignRecipientModel())->insertBatch($campaignId, $recipients);
        $campaignModel->update($campaignId, ['recipients_count' => $countRcp]);

        // Auto-send teraz tylko gdy nie zaplanowano w przyszłości
        $autoSent = 0;
        if ($status === 'sending') {
            $autoSent = self::dispatchCampaign($campaignId, 100);
        }

        $msg = "Utworzono kampanię {$name}: " . count($rows) . ' członków, ' . $countRcp . ' wysyłek.';
        if ($status === 'scheduled') {
            $msg .= ' Zaplanowano na ' . $scheduledAt . '.';
        } elseif ($autoSent > 0) {
            $msg .= " Wysłano natychmiast: {$autoSent}.";
        } else {
            $msg .= ' Worker dokończy w tle (cli/send_campaigns.php).';
        }
        Session::flash('success', $msg);
        $this->redirect('admin/campaigns/' . $campaignId);
    }

    public function show(string $id): void
    {
        $campaign = (new CampaignModel())->findById((int)$id);
        if (!$campaign) {
            Session::flash('error', 'Kampania nie istnieje.');
            $this->redirect('admin/campaigns');
        }
        $recipients = (new CampaignRecipientModel())->listForCampaign((int)$id, 500);

        $this->render('admin/campaigns/show', [
            'title'      => 'Kampania: ' . $campaign['name'],
            'campaign'   => $campaign,
            'recipients' => $recipients,
        ]);
    }

    /**
     * Statyczna metoda do wysłki paczki — używana zarówno przez controler
     * (auto-send małych kampanii) jak i CLI worker.
     *
     * Zwraca liczbę wysłanych w tej paczce.
     */
    public static function dispatchCampaign(int $campaignId, int $batchSize = 50): int
    {
        $cModel = new CampaignModel();
        $rModel = new CampaignRecipientModel();

        $campaign = $cModel->withoutScope()->findById($campaignId);
        if (!$campaign) return 0;

        $clubId = (int)$campaign['club_id'];
        $subject = (string)($campaign['template_subject'] ?? '');
        $body    = (string)$campaign['template_body'];

        $pending = $rModel->nextQueued($campaignId, $batchSize);
        $sent    = 0;

        foreach ($pending as $r) {
            try {
                $rendered = self::renderTemplate($body, (int)$r['member_id']);
                $renderedSubj = $subject !== ''
                    ? self::renderTemplate($subject, (int)$r['member_id'])
                    : '';

                if ($r['channel'] === 'email') {
                    EmailService::queue($clubId, $r['to_address'], $renderedSubj, $rendered, null, 'campaign');
                    $rModel->markSent((int)$r['id']);
                    $sent++;
                } elseif ($r['channel'] === 'sms') {
                    SmsService::queue($clubId, $r['to_address'], $rendered);
                    $rModel->markSent((int)$r['id']);
                    $sent++;
                } else {
                    $rModel->markFailed((int)$r['id'], 'unknown channel');
                }
            } catch (\Throwable $e) {
                $rModel->markFailed((int)$r['id'], $e->getMessage());
            }
        }

        $cModel->refreshStats($campaignId);

        // Jeśli już wszystkie obsłużone — finalizuj
        if ($rModel->countQueued($campaignId) === 0) {
            $cModel->markStatus($campaignId, 'sent');
        }
        return $sent;
    }

    /**
     * Podstawiamy placeholdery {{first_name}}, {{last_name}}, {{member_number}}.
     * Defensywnie — pobieramy via MemberModel (deszyfruje email/phone w razie potrzeby).
     */
    private static function renderTemplate(string $tpl, int $memberId): string
    {
        try {
            $m = (new \App\Models\MemberModel())->withoutScope()->findById($memberId);
        } catch (\Throwable) {
            $m = null;
        }
        if (!$m) return $tpl;
        return str_replace(
            ['{{first_name}}','{{last_name}}','{{member_number}}','{{member.first_name}}','{{member.last_name}}','{{member.number}}'],
            [
                (string)($m['first_name']   ?? ''),
                (string)($m['last_name']    ?? ''),
                (string)($m['member_number'] ?? ''),
                (string)($m['first_name']   ?? ''),
                (string)($m['last_name']    ?? ''),
                (string)($m['member_number'] ?? ''),
            ],
            $tpl
        );
    }
}
