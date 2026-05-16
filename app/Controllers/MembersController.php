<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\CsvExporter;
use App\Helpers\Csrf;
use App\Helpers\MemberFilter;
use App\Helpers\RateLimiter;
use App\Helpers\Session;
use App\Models\ClubOnboardingConfigModel;
use App\Models\EmailEventCatalogModel;
use App\Models\MemberFeeAssignmentModel;
use App\Models\MemberIdentityModel;
use App\Models\MemberModel;
use App\Models\MemberSportModel;
use App\Models\SportModel;
use App\Models\TenantAccessLogModel;

class MembersController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $q      = trim($_GET['q'] ?? '');
        $status = $_GET['status'] ?? '';
        $sport  = isset($_GET['sport']) ? (int)$_GET['sport'] : null;
        $page   = max(1, (int)($_GET['page'] ?? 1));

        $pagination = (new MemberModel())->search($q, $status ?: null, $sport, $page, 25);
        $clubSports = (new SportModel())->listForClub($this->currentClub());

        $this->render('members/index', [
            'title'       => 'Zawodnicy',
            'pagination'  => $pagination,
            'q'           => $q,
            'status'      => $status,
            'sportFilter' => $sport,
            'clubSports'  => $clubSports,
        ]);
    }

    public function create(): void
    {
        $clubSports = (new SportModel())->listForClub($this->currentClub());
        $next       = (new MemberModel())->nextMemberNumber($this->currentClub());
        $onboardingConfig = (new ClubOnboardingConfigModel())->forClub($this->currentClub());
        $this->render('members/form', [
            'title'             => 'Nowy zawodnik',
            'member'            => null,
            'sports'            => [],
            'clubSports'        => $clubSports,
            'nextNumber'        => $next,
            'onboardingConfig'  => $onboardingConfig,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();

        $onboardingModel = new ClubOnboardingConfigModel();
        $config = $onboardingModel->forClub($this->currentClub());

        $data = $this->parseMemberPost(true, $config);
        if ($data === null) return;

        $model = new MemberModel();
        $id    = $model->insert($data);

        // Auto-assign sport jesli klub skonfigurowal default i uzytkownik nie wybral
        $selectedSports = $_POST['club_sport_ids'] ?? [];
        if (empty($selectedSports) && !empty($config['auto_assign_sport_id'])) {
            $_POST['club_sport_ids'] = [(int)$config['auto_assign_sport_id']];
        }
        $this->syncSports($id);

        // Save custom field values
        $this->saveOnboardingCustomFields($id, $config, $onboardingModel);

        // Log akceptacji zgod
        $this->logOnboardingConsents($id, $config, $onboardingModel);

        // Auto-assign fee rate
        $this->applyAutoFeeAssignment($id, $config);

        // Link to unified identity (cross-club)
        $email       = $data['email'] ?? '';
        $displayName = ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '');
        if ($email !== '') {
            try {
                $identityModel = new MemberIdentityModel();
                $identity = $identityModel->findOrCreate(
                    $email,
                    $data['pesel'] ?? null,
                    $data['phone'] ?? null,
                    trim($displayName)
                );
                $identityModel->linkMember((int)$identity['id'], $id);
            } catch (\Throwable $e) {
                // Identity linking is non-critical, log and continue
                error_log('MemberIdentity link failed: ' . $e->getMessage());
            }
        }

        // Auto welcome email
        if (!empty($config['auto_send_welcome_email']) && $email !== '') {
            $this->sendWelcomeEmail($id, $data, $config);
        }

        // Achievements: ewaluuj dla nowego czlonka (profile_complete, etc.).
        try {
            if (class_exists(\App\Helpers\Achievements\AchievementEvaluator::class)) {
                \App\Helpers\Achievements\AchievementEvaluator::evaluateForMember((int)$id);
            }
        } catch (\Throwable $e) {
            error_log('Achievements trigger after member store failed: ' . $e->getMessage());
        }

        // Webhook: member.created (Public API v2 subscribers).
        \App\Helpers\Webhooks\WebhookDispatcher::publish($this->currentClub(), 'member.created', [
            'member_id'    => (int)$id,
            'first_name'   => $data['first_name'] ?? null,
            'last_name'    => $data['last_name'] ?? null,
            'member_number'=> $data['member_number'] ?? null,
            'status'       => $data['status'] ?? null,
        ]);

        Session::flash('success', 'Zawodnik dodany.');
        $this->redirect('members/' . $id);
    }

    private function saveOnboardingCustomFields(int $memberId, array $config, ClubOnboardingConfigModel $model): void
    {
        $fields = $config['custom_fields'] ?? [];
        if (!is_array($fields) || empty($fields)) return;
        $posted = $_POST['custom_field'] ?? [];
        if (!is_array($posted)) return;
        foreach ($fields as $f) {
            $key = $f['key'] ?? '';
            if ($key === '') continue;
            $val = $posted[$key] ?? null;
            if (is_array($val)) $val = implode(',', array_map('strval', $val));
            $val = $val !== null ? (string)$val : null;
            try {
                $model->saveMemberFieldValue($memberId, $key, $val);
            } catch (\Throwable $e) {
                error_log('Onboarding custom field save failed: ' . $e->getMessage());
            }
        }
    }

    private function logOnboardingConsents(int $memberId, array $config, ClubOnboardingConfigModel $model): void
    {
        $consents = $config['custom_consents'] ?? [];
        if (!is_array($consents) || empty($consents)) return;
        $posted = $_POST['consent'] ?? [];
        if (!is_array($posted)) $posted = [];
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        foreach ($consents as $c) {
            $key = $c['key'] ?? '';
            if ($key === '') continue;
            if (!empty($posted[$key])) {
                try {
                    $model->logConsent($memberId, $key, $c['version'] ?? null, $ip);
                } catch (\Throwable $e) {
                    error_log('Onboarding consent log failed: ' . $e->getMessage());
                }
            }
        }
    }

    private function applyAutoFeeAssignment(int $memberId, array $config): void
    {
        $rateId = (int)($config['auto_assign_fee_rate_id'] ?? 0);
        if ($rateId <= 0) return;
        try {
            (new MemberFeeAssignmentModel())->insert([
                'member_id'   => $memberId,
                'fee_rate_id' => $rateId,
                'valid_from'  => date('Y-m-d'),
                'valid_to'    => null,
                'status'      => 'active',
                'notes'       => 'Auto-przypisane wg konfiguracji onboardingu',
                'created_by'  => \App\Helpers\Auth::id(),
            ]);
        } catch (\Throwable $e) {
            error_log('Auto fee assignment failed: ' . $e->getMessage());
        }
    }

    private function sendWelcomeEmail(int $memberId, array $data, array $config): void
    {
        try {
            $clubId = $this->currentClub();
            $templateCode = $config['welcome_email_template'] ?? null;

            // Probuj zaladowac event z katalogu (nowy format {{var}})
            $event = null;
            if ($templateCode) {
                $event = (new EmailEventCatalogModel())->findByCode($templateCode);
            }

            $club = (new \App\Models\ClubModel())->findById($clubId);
            $context = [
                'member' => [
                    'first_name'    => $data['first_name'] ?? '',
                    'last_name'     => $data['last_name'] ?? '',
                    'member_number' => $data['member_number'] ?? '',
                    'email'         => $data['email'] ?? '',
                ],
                'club' => [
                    'name'  => $club['name'] ?? '',
                    'email' => $club['email'] ?? '',
                ],
            ];

            if ($event) {
                $rendered = \App\Helpers\EmailTemplateRenderer::renderTemplate([
                    'subject' => $event['default_subject'] ?? '',
                    'body'    => $event['default_body'] ?? '',
                ], $context);
                \App\Helpers\EmailService::queue(
                    $clubId,
                    (string)$data['email'],
                    $rendered['subject'],
                    $rendered['body'],
                    trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
                    $event['code']
                );
                return;
            }

            // Fallback: klasyczny "welcome" przez EmailService::queueFromTemplate (legacy {placeholder} format)
            \App\Helpers\EmailService::queueFromTemplate(
                $clubId,
                'welcome',
                (string)$data['email'],
                [
                    'first_name'    => $data['first_name'] ?? '',
                    'last_name'     => $data['last_name'] ?? '',
                    'member_number' => $data['member_number'] ?? '',
                    'club_name'     => $club['name'] ?? '',
                ],
                trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''))
            );
        } catch (\Throwable $e) {
            error_log('Welcome email send failed: ' . $e->getMessage());
        }
    }

    public function show(string $id): void
    {
        $member = (new MemberModel())->withSports((int)$id);
        if (empty($member)) {
            Session::flash('error', 'Nie znaleziono zawodnika.');
            $this->redirect('members');
        }
        $this->render('members/show', [
            'title'  => $member['first_name'] . ' ' . $member['last_name'],
            'member' => $member,
        ]);
    }

    public function edit(string $id): void
    {
        $member = (new MemberModel())->withSports((int)$id);
        if (empty($member)) {
            Session::flash('error', 'Nie znaleziono zawodnika.');
            $this->redirect('members');
        }
        $clubSports = (new SportModel())->listForClub($this->currentClub());
        $onboardingConfig = (new ClubOnboardingConfigModel())->forClub($this->currentClub());
        $this->render('members/form', [
            'title'            => 'Edycja zawodnika',
            'member'           => $member,
            'sports'           => $member['sports'] ?? [],
            'clubSports'       => $clubSports,
            'nextNumber'       => $member['member_number'],
            'onboardingConfig' => $onboardingConfig,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $onboardingConfig = (new ClubOnboardingConfigModel())->forClub($this->currentClub());
        $data = $this->parseMemberPost(false, $onboardingConfig);
        if ($data === null) return;

        $model = new MemberModel();
        $model->update((int)$id, $data);
        $this->syncSports((int)$id);

        Session::flash('success', 'Zapisano zmiany.');
        $this->redirect('members/' . $id);
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new MemberModel())->delete((int)$id);
        Session::flash('success', 'Zawodnik usunięty.');
        $this->redirect('members');
    }

    public function bulkAction(): void
    {
        Csrf::verify();

        $ids    = $_POST['member_ids'] ?? [];
        $action = $_POST['action'] ?? '';

        if (!is_array($ids) || empty($ids)) {
            Session::flash('error', __('members.bulk_no_selection'));
            $this->redirect('members');
        }

        $ids   = array_map('intval', $ids);
        $model = new MemberModel();
        $count = 0;

        switch ($action) {
            case 'delete':
                foreach ($ids as $mid) {
                    $model->delete($mid);
                    $count++;
                }
                Session::flash('success', __('members.bulk_deleted', ['count' => $count]));
                break;

            case 'suspend':
                foreach ($ids as $mid) {
                    $model->update($mid, ['status' => 'zawieszony']);
                    $count++;
                }
                Session::flash('success', __('members.bulk_suspended', ['count' => $count]));
                break;

            case 'activate':
                foreach ($ids as $mid) {
                    $model->update($mid, ['status' => 'aktywny']);
                    $count++;
                }
                Session::flash('success', __('members.bulk_activated', ['count' => $count]));
                break;

            case 'export_csv':
                $rows    = [];
                $headers = ['Nr', __('form.first_name'), __('form.last_name'), __('form.email'), __('form.phone'), __('form.status')];
                foreach ($ids as $mid) {
                    $m = $model->findById($mid);
                    if ($m) {
                        $rows[] = [
                            $m['member_number'] ?? '',
                            $m['first_name']    ?? '',
                            $m['last_name']     ?? '',
                            $m['email']         ?? '',
                            $m['phone']         ?? '',
                            $m['status']        ?? '',
                        ];
                    }
                }
                CsvExporter::download('members_export.csv', $headers, $rows);
                return; // CsvExporter calls exit

            case 'send_message':
                // Z.3 — przekieruj do formularza z preselected IDs
                Session::set('bulk_message_member_ids', $ids);
                $this->redirect('members/bulk-message');
                return;

            default:
                Session::flash('error', __('members.bulk_invalid_action'));
                break;
        }

        $this->redirect('members');
    }

    /**
     * Z.3 — Form do bulk-message: szablon wiadomości + lista odbiorców.
     */
    public function bulkMessageForm(): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $ids = Session::get('bulk_message_member_ids') ?? [];
        if (empty($ids) || !is_array($ids)) {
            Session::flash('error', 'Najpierw zaznacz zawodników na liście.');
            $this->redirect('members');
        }

        $model = new MemberModel();
        $recipients = [];
        foreach ($ids as $mid) {
            $m = $model->findById((int)$mid);
            if ($m && !empty($m['email'])) {
                $recipients[] = [
                    'id'    => (int)$m['id'],
                    'name'  => trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? '')),
                    'email' => $m['email'],
                ];
            }
        }

        $this->render('members/bulk_message', [
            'title'      => 'Wyślij wiadomość — bulk',
            'recipients' => $recipients,
            'totalIds'   => count($ids),
        ]);
    }

    /**
     * Z.3 — Wysyłka bulk message przez EmailService::queue.
     */
    public function bulkMessageSend(): void
    {
        Csrf::verify();
        $this->requireLogin();
        $this->requireClubContext();

        $ids = Session::get('bulk_message_member_ids') ?? [];
        if (empty($ids)) {
            Session::flash('error', 'Brak odbiorców. Zaznacz zawodników na liście.');
            $this->redirect('members');
        }

        $subject = trim($_POST['subject'] ?? '');
        $body    = trim($_POST['body'] ?? '');
        if ($subject === '' || $body === '') {
            Session::flash('error', 'Temat i treść wiadomości są wymagane.');
            $this->redirect('members/bulk-message');
        }

        $clubId = $this->currentClub();
        $model  = new MemberModel();
        $sent   = 0;
        $skipped = 0;
        foreach ($ids as $mid) {
            $m = $model->findById((int)$mid);
            if (!$m || empty($m['email'])) { $skipped++; continue; }

            // Personalizacja: {{first_name}} → imię
            $personalizedBody = str_replace(
                ['{{first_name}}', '{{last_name}}', '{{member_number}}'],
                [$m['first_name'] ?? '', $m['last_name'] ?? '', $m['member_number'] ?? ''],
                $body
            );

            \App\Helpers\EmailService::queue(
                $clubId,
                $m['email'],
                $subject,
                $personalizedBody,
                trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? '')),
                'bulk_message'
            );
            $sent++;
        }

        // Wyczyść session preselection
        Session::remove('bulk_message_member_ids');

        Session::flash('success', "Zakolejkowano {$sent} wiadomości. Worker email-owy wyśle je w tle." . ($skipped > 0 ? " {$skipped} pominięto (brak email)." : ''));
        $this->redirect('members');
    }

    /**
     * Bulk export: formularz wyboru filtrów + kolumn.
     * Dostępny dla zarząd / księgowy / admin / super admin.
     */
    public function exportBulkForm(): void
    {
        $this->requireRole(['zarzad', 'ksiegowy', 'admin']);
        $clubSports = (new SportModel())->listForClub($this->currentClub());
        $this->render('members/export_bulk', [
            'title'      => 'Eksport członków',
            'clubSports' => $clubSports,
            'canSensitive' => Auth::canAccessSensitiveData(),
        ]);
    }

    /**
     * Bulk export: generuje CSV (zgodne z Excel) z wybranymi kolumnami i filtrami.
     *
     * Bezpieczeństwo:
     *   - rate limit (5 / godz)
     *   - eksport PESEL/medyczne wymagają canAccessSensitiveData
     *   - sensitive eksport logujemy do tenant_access_log
     */
    public function exportBulk(): void
    {
        Csrf::verify();
        $this->requireRole(['zarzad', 'ksiegowy', 'admin']);

        // Rate limit: max 5 bulk operacji / godz per IP.
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!RateLimiter::check($ip, 'bulk_export', 5, 60)) {
            Session::flash('error', 'Przekroczono limit eksportów (5/godz). Spróbuj później.');
            $this->redirect('members/export');
        }
        RateLimiter::hit($ip, 'bulk_export', 5, 60);

        $filter  = MemberFilter::fromRequest($_POST);
        $columns = is_array($_POST['columns'] ?? null) ? $_POST['columns'] : [];
        if (empty($columns)) {
            Session::flash('error', 'Wybierz przynajmniej jedną kolumnę do eksportu.');
            $this->redirect('members/export');
        }
        $columns = array_values(array_intersect(
            $columns,
            ['member_number','first_name','last_name','email','phone','pesel',
             'birth_date','gender','address_street','address_city','address_postal',
             'join_date','status','notes']
        ));

        $rows = MemberFilter::query($this->currentClub(), $filter, 5000);

        $sensitiveCols = array_intersect($columns, ['pesel']);
        if (!empty($sensitiveCols) && !Auth::canAccessSensitiveData()) {
            Session::flash('error', 'Brak uprawnień do eksportu danych szczególnej kategorii (PESEL).');
            $this->redirect('members/export');
        }

        // Audit sensitive bulk export
        if (!empty($sensitiveCols)) {
            try {
                (new TenantAccessLogModel())->logBypass(
                    'members',
                    'bulk_export_sensitive',
                    __FILE__,
                    __LINE__,
                    self::class,
                    'warning',
                    'cols=' . implode(',', $sensitiveCols) . ';filter=' . MemberFilter::describe($filter)
                );
            } catch (\Throwable) {}
        }

        // Build headers + rows
        $labels = [
            'member_number'   => 'Nr czlonkowski',
            'first_name'      => 'Imie',
            'last_name'       => 'Nazwisko',
            'email'           => 'Email',
            'phone'           => 'Telefon',
            'pesel'           => 'PESEL',
            'birth_date'      => 'Data urodzenia',
            'gender'          => 'Plec',
            'address_street'  => 'Ulica',
            'address_city'    => 'Miasto',
            'address_postal'  => 'Kod pocztowy',
            'join_date'       => 'Data wstapienia',
            'status'          => 'Status',
            'notes'           => 'Uwagi',
        ];
        $headers = array_map(fn($c) => $labels[$c] ?? $c, $columns);

        $outRows = [];
        foreach ($rows as $r) {
            $row = [];
            foreach ($columns as $c) {
                $row[] = (string)($r[$c] ?? '');
            }
            $outRows[] = $row;
        }

        $filename = 'members_export_' . date('Ymd_His') . '.csv';
        CsvExporter::download($filename, $headers, $outRows);
    }

    /** Ustawia lub resetuje hasło portalu zawodnika. */
    public function setPortalPassword(string $id): void
    {
        Csrf::verify();
        $password = $_POST['portal_password'] ?? '';
        if (strlen($password) < 8) {
            Session::flash('error', 'Hasło musi mieć co najmniej 8 znaków.');
            $this->redirect('members/' . $id);
        }
        \App\Helpers\MemberAuth::setPassword((int)$id, $password);
        Session::flash('success', 'Hasło portalu zawodnika ustawione.');
        $this->redirect('members/' . $id);
    }

    private function parseMemberPost(bool $forCreate = true, ?array $onboardingConfig = null): ?array
    {
        $redirect = $forCreate ? 'members/create' : 'members';

        $rules = [
            'first_name'  => 'required|min:2|max:60',
            'last_name'   => 'required|min:2|max:60',
            'email'       => 'email|max:120',
            'pesel'       => 'pesel',
            'birth_date'  => 'date',
            'phone'       => 'phone',
            'join_date'   => 'required|date',
            'status'      => 'required|in:aktywny,zawieszony,wykreslony,urlop',
        ];

        // Wymuszone wymagania per onboarding config (BC: jesli null, brak zmian)
        if ($onboardingConfig) {
            if (!empty($onboardingConfig['require_pesel'])) {
                $rules['pesel'] = 'required|pesel';
            }
        }

        $v = \App\Helpers\Validator::make($_POST, $rules);

        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            Session::flash('_old_input', $_POST);
            $this->redirect($redirect);
            return null;
        }

        // Walidacja adresu/emergency contact/foto/medical wg config (poza Validatorem)
        if ($onboardingConfig) {
            $err = $this->validateOnboardingRequirements($_POST, $onboardingConfig);
            if ($err !== null) {
                Session::flash('error', $err);
                Session::flash('_old_input', $_POST);
                $this->redirect($redirect);
                return null;
            }
        }

        $clean = $v->validated();
        $data = [
            'member_number'   => trim($_POST['member_number'] ?? ''),
            'first_name'      => $clean['first_name'],
            'last_name'       => $clean['last_name'],
            'pesel'           => $clean['pesel'],
            'birth_date'      => $clean['birth_date'],
            'gender'          => in_array($_POST['gender'] ?? '', ['M','K'], true) ? $_POST['gender'] : null,
            'email'           => $clean['email'],
            'phone'           => $clean['phone'],
            'address_street'  => trim($_POST['address_street'] ?? '') ?: null,
            'address_city'    => trim($_POST['address_city'] ?? '') ?: null,
            'address_postal'  => trim($_POST['address_postal'] ?? '') ?: null,
            'join_date'       => $clean['join_date'],
            'status'          => $clean['status'],
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
        ];

        if ($forCreate && $data['member_number'] === '') {
            $data['member_number'] = (new MemberModel())->nextMemberNumber($this->currentClub());
        }

        if ($forCreate) {
            $data['created_by'] = \App\Helpers\Auth::id();
        }

        return $data;
    }

    /**
     * Sprawdza wymagania onboarding configu nie objęte standardowym Validatorem.
     * Zwraca komunikat bledu lub null jesli OK.
     */
    private function validateOnboardingRequirements(array $post, array $cfg): ?string
    {
        if (!empty($cfg['require_address'])) {
            $street = trim($post['address_street'] ?? '');
            $city   = trim($post['address_city'] ?? '');
            if ($street === '' || $city === '') {
                return 'Adres (ulica i miasto) jest wymagany przez konfiguracje klubu.';
            }
        }
        if (!empty($cfg['require_emergency_contact'])) {
            $ec = trim($post['emergency_contact'] ?? '');
            if ($ec === '') {
                return 'Kontakt awaryjny jest wymagany przez konfiguracje klubu.';
            }
        }
        if (!empty($cfg['require_medical_consent'])) {
            if (empty($post['consent']['medical'])) {
                return 'Zgoda medyczna jest wymagana przez konfiguracje klubu.';
            }
        }
        if (!empty($cfg['require_photo'])) {
            if (empty($_FILES['photo']['name'] ?? '') && empty($post['photo_url'] ?? '')) {
                return 'Zdjecie jest wymagane przez konfiguracje klubu.';
            }
        }

        // Walidacja wieku
        $birth = trim($post['birth_date'] ?? '');
        if ($birth !== '' && (!empty($cfg['min_age_years']) || !empty($cfg['max_age_years']))) {
            try {
                $bd = new \DateTime($birth);
                $age = (int)$bd->diff(new \DateTime('today'))->y;
                if (!empty($cfg['min_age_years']) && $age < (int)$cfg['min_age_years']) {
                    return 'Wiek mniejszy niz minimalny dla klubu (' . (int)$cfg['min_age_years'] . ' lat).';
                }
                if (!empty($cfg['max_age_years']) && $age > (int)$cfg['max_age_years']) {
                    return 'Wiek wiekszy niz maksymalny dla klubu (' . (int)$cfg['max_age_years'] . ' lat).';
                }
            } catch (\Throwable) {
                // ignore parse errors — birth_date validator juz to sprawdzil
            }
        }

        // Wymagane zgody konfigurowalne
        $consents = $cfg['custom_consents'] ?? [];
        if (is_array($consents)) {
            $posted = $post['consent'] ?? [];
            foreach ($consents as $c) {
                if (!empty($c['required'])) {
                    $key = $c['key'] ?? '';
                    if ($key !== '' && empty($posted[$key])) {
                        return 'Wymagana zgoda: ' . ($c['label'] ?? $key);
                    }
                }
            }
        }

        // Wymagane custom fields
        $fields = $cfg['custom_fields'] ?? [];
        if (is_array($fields)) {
            $postedFields = $post['custom_field'] ?? [];
            foreach ($fields as $f) {
                if (!empty($f['required'])) {
                    $k = $f['key'] ?? '';
                    if ($k !== '' && (empty($postedFields[$k]) && $postedFields[$k] !== '0')) {
                        return 'Wymagane pole: ' . ($f['label'] ?? $k);
                    }
                }
            }
        }

        return null;
    }

    private function syncSports(int $memberId): void
    {
        $ids = $_POST['club_sport_ids'] ?? [];
        if (!is_array($ids)) return;

        $ms  = new MemberSportModel();
        $db  = \App\Helpers\Database::pdo();
        $stmt = $db->prepare("DELETE FROM member_sports WHERE member_id = ?");
        $stmt->execute([$memberId]);

        foreach ($ids as $csId) {
            $csId = (int)$csId;
            if ($csId > 0) {
                $ms->assign($memberId, $csId);
            }
        }
    }
}
