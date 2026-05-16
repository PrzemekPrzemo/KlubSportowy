<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\LogoUploader;
use App\Helpers\Session;
use App\Models\SponsorModel;

/**
 * CRUD sponsorów per-klub. Dostepne dla roli zarzad/admin
 * (sponsoring + kontrakty to dane finansowe).
 */
class ClubSponsorsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad', 'admin']);
    }

    /**
     * GET /club/sponsors — lista + statystyki + filtry (tier / expiring).
     */
    public function index(): void
    {
        $clubId = $this->currentClub();
        $model  = new SponsorModel();

        $sponsors      = $model->forClub($clubId);
        $stats         = $model->statsForClub($clubId);
        $expiringSoon  = $model->expiringSoon($clubId, 30);

        // Server-side filters (GET ?tier=gold, ?expiring=1)
        $tierFilter = trim((string)($_GET['tier'] ?? ''));
        if ($tierFilter !== '') {
            $sponsors = array_values(array_filter($sponsors, fn($s) => ($s['tier'] ?? '') === $tierFilter));
        }
        if (!empty($_GET['expiring'])) {
            $sponsors = array_values(array_filter($sponsors, function ($s) {
                $d = $s['days_to_expiry'] ?? null;
                return $d !== null && (int)$d >= 0 && (int)$d <= 30;
            }));
        }

        $this->render('club/sponsors/index', [
            'title'         => 'Sponsorzy',
            'sponsors'      => $sponsors,
            'stats'         => $stats,
            'expiringSoon'  => $expiringSoon,
            'tierFilter'    => $tierFilter,
            'tiers'         => $this->tierLabels(),
        ]);
    }

    /**
     * GET /club/sponsors/create
     */
    public function create(): void
    {
        $this->render('club/sponsors/form', [
            'title'   => 'Nowy sponsor',
            'sponsor' => null,
            'tiers'   => $this->tierLabels(),
        ]);
    }

    /**
     * POST /club/sponsors/store
     */
    public function store(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();

        $data = $this->parsePost();
        if ($data === null) {
            return;
        }
        $data['club_id'] = $clubId;

        $model = new SponsorModel();
        $id    = $model->insert($data);

        // Handle logo upload (after insert — potrzebujemy sponsor_id w sciezce)
        $logoPath = $this->handleLogoUpload($clubId, $id);
        if ($logoPath !== null) {
            $model->updateForClub($id, $clubId, ['logo_path' => $logoPath]);
        }

        Session::flash('success', 'Sponsor został dodany.');
        $this->redirect('club/sponsors');
    }

    /**
     * GET /club/sponsors/:id/edit
     */
    public function edit(string $id): void
    {
        $clubId  = $this->currentClub();
        $sponsor = (new SponsorModel())->findByIdForClub((int)$id, $clubId);
        if (!$sponsor) {
            Session::flash('error', 'Nie znaleziono sponsora.');
            $this->redirect('club/sponsors');
        }
        $this->render('club/sponsors/form', [
            'title'   => 'Edycja sponsora',
            'sponsor' => $sponsor,
            'tiers'   => $this->tierLabels(),
        ]);
    }

    /**
     * POST /club/sponsors/:id/update
     */
    public function update(string $id): void
    {
        Csrf::verify();
        $clubId    = $this->currentClub();
        $sponsorId = (int)$id;

        $model = new SponsorModel();
        $existing = $model->findByIdForClub($sponsorId, $clubId);
        if (!$existing) {
            Session::flash('error', 'Nie znaleziono sponsora.');
            $this->redirect('club/sponsors');
        }

        $data = $this->parsePost();
        if ($data === null) {
            return;
        }
        // club_id zawsze pochodzi z kontekstu — usuń ewentualne post override
        unset($data['club_id']);

        // Logo upload (tylko jak user wgrał nowy)
        $logoPath = $this->handleLogoUpload($clubId, $sponsorId);
        if ($logoPath !== null) {
            $data['logo_path'] = $logoPath;
        }

        $model->updateForClub($sponsorId, $clubId, $data);
        Session::flash('success', 'Sponsor został zaktualizowany.');
        $this->redirect('club/sponsors');
    }

    /**
     * POST /club/sponsors/:id/delete
     */
    public function destroy(string $id): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        (new SponsorModel())->deleteForClub((int)$id, $clubId);
        Session::flash('success', 'Sponsor usunięty.');
        $this->redirect('club/sponsors');
    }

    /**
     * Parse + validate POST. Zwraca dane do insert/update lub null + flash + redirect.
     */
    private function parsePost(): ?array
    {
        $tiers = ['platinum', 'gold', 'silver', 'bronze', 'partner'];

        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            Session::flash('error', 'Nazwa sponsora jest wymagana.');
            $this->redirect('club/sponsors/create');
            return null;
        }

        $email = trim((string)($_POST['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Nieprawidłowy format email.');
            $this->redirect('club/sponsors/create');
            return null;
        }

        $website = trim((string)($_POST['website'] ?? ''));
        if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
            Session::flash('error', 'Nieprawidłowy format URL strony.');
            $this->redirect('club/sponsors/create');
            return null;
        }

        $tier = in_array($_POST['tier'] ?? '', $tiers, true) ? $_POST['tier'] : 'partner';

        $contractValue = trim((string)($_POST['contract_value'] ?? ''));
        $contractValue = $contractValue === '' ? null : (float)str_replace(',', '.', $contractValue);

        $start = trim((string)($_POST['contract_start'] ?? '')) ?: null;
        $end   = trim((string)($_POST['contract_end'] ?? '')) ?: null;
        if ($start !== null && $end !== null && $end < $start) {
            Session::flash('error', 'Data końca kontraktu musi być po dacie startu.');
            $this->redirect('club/sponsors/create');
            return null;
        }

        $weight = (int)($_POST['display_weight'] ?? 100);
        if ($weight < 0) $weight = 0;

        return [
            'name'              => $name,
            'contact_person'    => trim((string)($_POST['contact_person'] ?? '')) ?: null,
            'email'             => $email ?: null,
            'phone'             => trim((string)($_POST['phone'] ?? '')) ?: null,
            'website'           => $website ?: null,
            'tier'              => $tier,
            'contract_value'    => $contractValue,
            'contract_start'    => $start,
            'contract_end'      => $end,
            'notes'             => trim((string)($_POST['notes'] ?? '')) ?: null,
            'display_in_portal' => isset($_POST['display_in_portal']) ? 1 : 0,
            'display_in_emails' => isset($_POST['display_in_emails']) ? 1 : 0,
            'display_weight'    => $weight,
            'active'            => isset($_POST['active']) ? 1 : 0,
        ];
    }

    /**
     * Handle logo upload do storage/sponsors/{club_id}/.
     * Zwraca relatywną sciezkę albo null jak brak pliku / błąd (flash już ustawiony).
     */
    private function handleLogoUpload(int $clubId, int $sponsorId): ?string
    {
        $file = $_FILES['logo'] ?? null;
        if (!is_array($file) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        // Web-accessible (public/uploads/...) — sponsorskie logo musi być
        // serwowalne przez HTTP (do <img src=...>); storage/ nie jest pod webrootem.
        $absDir    = ROOT_PATH . '/public/uploads/sponsors/' . $clubId;
        $relPrefix = 'uploads/sponsors/' . $clubId;

        return LogoUploader::save($file, $absDir, $relPrefix, 'sponsor_' . $sponsorId);
    }

    private function tierLabels(): array
    {
        return [
            'platinum' => 'Platinum',
            'gold'     => 'Gold',
            'silver'   => 'Silver',
            'bronze'   => 'Bronze',
            'partner'  => 'Partner',
        ];
    }
}
