<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\ReferralCodeService;
use App\Helpers\Session;
use App\Models\ClubCustomizationModel;
use App\Models\ClubModel;
use App\Models\ClubSportModel;
use App\Models\FeeRateModel;
use App\Models\ReferralCodeModel;
use App\Models\SportModel;
use App\Models\UserClubModel;
use App\Models\UserModel;

/**
 * Public self-service trial signup wizard.
 *
 * 5-step flow for a brand-new club (no auth required):
 *   1. Dane klubu     (name, city, NIP, contact)
 *   2. Branding       (logo, color, subdomain)
 *   3. Sporty         (1-3 sports from catalog)
 *   4. Skladki        (1-5 fee rates)
 *   5. Admin          (zarzad credentials)
 * After step 5 commits a single DB transaction creating clubs +
 * club_customization + club_sports + fee_rates + users + user_clubs
 * + club_subscriptions (trial 30 days). Auto-login + redirect to welcome.
 *
 * State lives in $_SESSION['onboarding_wizard'] with a 1h timeout.
 */
class OnboardingWizardController extends BaseController
{
    private const SESSION_KEY = 'onboarding_wizard';
    private const SESSION_TIMEOUT_SECONDS = 3600;

    public function __construct()
    {
        parent::__construct();
        // Use the public "landing" layout so the wizard renders without auth.
        $this->view->setLayout('landing');
    }

    // =========================================================
    // Landing
    // =========================================================

    /** GET /trial — public landing page promoting the trial signup. */
    public function landing(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }
        // Capture ?ref=... — zapamietaj w stanie wizarda nawet jesli user
        // porzuci landing (uzytkownik moze wrocic za godzine, mamy session).
        $this->captureRefParam();

        $this->render('onboarding_wizard/landing', [
            'title' => 'Zaloz klub online - 30 dni trial',
        ]);
    }

    // =========================================================
    // Step 1 - Club data
    // =========================================================

    /** GET /trial/start */
    public function step1(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }
        $this->resetIfTimedOut();
        // Capture ?ref=... rowniez tutaj — useful gdy ktos lapie /trial/start bezposrednio.
        $this->captureRefParam();
        $state = $this->state();

        $this->render('onboarding_wizard/step1_club', [
            'title'        => 'Krok 1/5 - Dane klubu',
            'currentStep'  => 1,
            'data'         => $state['club'] ?? [],
            'referralCode' => $state['referral_code'] ?? '',
        ]);
    }

    /** POST /trial/club-data */
    public function saveStep1(): void
    {
        Csrf::verify();

        $name  = trim($_POST['name']  ?? '');
        $city  = trim($_POST['city']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $nip   = trim($_POST['nip']   ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $referralCode = ReferralCodeService::normalize((string)($_POST['referral_code'] ?? ''));

        $errors = [];
        if (mb_strlen($name) < 3 || mb_strlen($name) > 120) {
            $errors[] = 'Nazwa klubu musi miec 3-120 znakow.';
        }
        if ($city === '') {
            $errors[] = 'Miasto jest wymagane.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Podaj prawidlowy adres email klubu.';
        } else {
            // Unique check against existing clubs.email (best-effort).
            try {
                $db = Database::pdo();
                $stmt = $db->prepare("SELECT id FROM clubs WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn()) {
                    $errors[] = 'Klub z tym adresem email jest juz zarejestrowany.';
                }
            } catch (\Throwable) {
                // table not available - skip uniqueness check
            }
        }
        if ($nip !== '' && !$this->validNip($nip)) {
            $errors[] = 'NIP jest niepoprawny (oczekiwano 10 cyfr).';
        }

        // Walidacja kodu polecajacego (jesli podany — pole opcjonalne).
        $validatedRefCode = null;
        if ($referralCode !== '') {
            $codeRow = ReferralCodeService::validateForReferred($referralCode);
            if ($codeRow === null) {
                $errors[] = 'Kod polecajacy jest nieprawidlowy lub nieaktywny.';
            } else {
                $validatedRefCode = $referralCode;
            }
        }

        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            $this->saveState([
                'club' => compact('name','city','email','nip','phone'),
                'referral_code' => $referralCode,
            ]);
            $this->redirect('trial/start');
        }

        $this->saveState([
            'club' => [
                'name'  => $name,
                'city'  => $city,
                'email' => $email,
                'nip'   => $nip ?: null,
                'phone' => $phone ?: null,
            ],
            'referral_code' => $validatedRefCode,
        ]);
        $this->redirect('trial/branding');
    }

    // =========================================================
    // Step 2 - Branding
    // =========================================================

    /** GET /trial/branding */
    public function step2(): void
    {
        if (Auth::check()) { $this->redirect('dashboard'); }
        if (!$this->hasStep('club')) { $this->redirect('trial/start'); }

        $state = $this->state();
        $this->render('onboarding_wizard/step2_branding', [
            'title'       => 'Krok 2/5 - Branding',
            'currentStep' => 2,
            'data'        => $state['branding'] ?? [],
            'clubName'    => $state['club']['name'] ?? '',
        ]);
    }

    /** POST /trial/branding */
    public function saveStep2(): void
    {
        Csrf::verify();

        $subdomain    = strtolower(trim($_POST['subdomain'] ?? ''));
        $primaryColor = trim($_POST['primary_color'] ?? '#0d6efd');
        $accentColor  = trim($_POST['accent_color']  ?? '#198754');
        $motto        = trim($_POST['motto'] ?? '');

        $errors = [];
        if (!preg_match('/^[a-z0-9-]{3,40}$/', $subdomain)) {
            $errors[] = 'Subdomena musi miec 3-40 znakow: male litery, cyfry, mysliniki.';
        } else {
            try {
                $db = Database::pdo();
                $stmt = $db->prepare("SELECT club_id FROM club_customization WHERE subdomain = ? LIMIT 1");
                $stmt->execute([$subdomain]);
                if ($stmt->fetchColumn()) {
                    $errors[] = 'Ta subdomena jest juz zajeta.';
                }
            } catch (\Throwable) {}
        }
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $primaryColor)) {
            $errors[] = 'Kolor podstawowy musi byc w formacie #XXXXXX.';
        }
        if ($accentColor !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $accentColor)) {
            $errors[] = 'Kolor akcentu musi byc w formacie #XXXXXX.';
        }

        // Logo upload (optional)
        $logoPath = null;
        if (!empty($_FILES['logo']) && ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $tmp = $_FILES['logo']['tmp_name'];
            $size = (int)($_FILES['logo']['size'] ?? 0);
            $ext  = strtolower(pathinfo((string)$_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowed = ['png','jpg','jpeg','webp'];
            if (!in_array($ext, $allowed, true)) {
                $errors[] = 'Logo musi byc PNG/JPG/WEBP.';
            } elseif ($size > 2 * 1024 * 1024) {
                $errors[] = 'Logo nie moze byc wieksze niz 2MB.';
            } else {
                $dir = ROOT_PATH . '/public/uploads/logos';
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $filename = 'trial_' . bin2hex(random_bytes(6)) . '_' . time() . '.' . $ext;
                $dest = $dir . '/' . $filename;
                if (@move_uploaded_file($tmp, $dest)) {
                    $logoPath = 'uploads/logos/' . $filename;
                } else {
                    $errors[] = 'Nie udalo sie zapisac logo.';
                }
            }
        }

        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            $this->saveState(['branding' => [
                'subdomain'     => $subdomain,
                'primary_color' => $primaryColor,
                'accent_color'  => $accentColor,
                'motto'         => $motto,
            ]]);
            $this->redirect('trial/branding');
        }

        $existing = $this->state()['branding'] ?? [];
        $this->saveState(['branding' => [
            'subdomain'     => $subdomain,
            'primary_color' => $primaryColor,
            'accent_color'  => $accentColor ?: '#198754',
            'motto'         => $motto ?: null,
            'logo_path'     => $logoPath ?? ($existing['logo_path'] ?? null),
        ]]);
        $this->redirect('trial/sports');
    }

    // =========================================================
    // Step 3 - Sports
    // =========================================================

    /** GET /trial/sports */
    public function step3(): void
    {
        if (Auth::check()) { $this->redirect('dashboard'); }
        if (!$this->hasStep('branding')) { $this->redirect('trial/branding'); }

        $sports = (new SportModel())->listActive();
        $state  = $this->state();

        $this->render('onboarding_wizard/step3_sports', [
            'title'         => 'Krok 3/5 - Sporty',
            'currentStep'   => 3,
            'sports'        => $sports,
            'selectedIds'   => $state['sports'] ?? [],
        ]);
    }

    /** POST /trial/sports */
    public function saveStep3(): void
    {
        Csrf::verify();

        $raw = (array)($_POST['sports'] ?? []);
        $selected = array_values(array_unique(array_filter(array_map('intval', $raw), fn($v) => $v > 0)));

        if (count($selected) < 1) {
            Session::flash('error', 'Wybierz przynajmniej jeden sport.');
            $this->redirect('trial/sports');
        }
        if (count($selected) > 3) {
            Session::flash('error', 'Trial pozwala maks. 3 sekcje sportowe.');
            $this->redirect('trial/sports');
        }

        // Whitelist against sports table
        $valid = [];
        try {
            $db = Database::pdo();
            $place = implode(',', array_fill(0, count($selected), '?'));
            $stmt = $db->prepare("SELECT id FROM sports WHERE id IN ($place) AND is_active = 1");
            $stmt->execute($selected);
            $valid = array_map('intval', array_column($stmt->fetchAll(), 'id'));
        } catch (\Throwable) {}

        if (empty($valid)) {
            Session::flash('error', 'Nie znaleziono wybranych sportow w katalogu.');
            $this->redirect('trial/sports');
        }

        $this->saveState(['sports' => $valid]);
        $this->redirect('trial/fees');
    }

    // =========================================================
    // Step 4 - Fee rates
    // =========================================================

    /** GET /trial/fees */
    public function step4(): void
    {
        if (Auth::check()) { $this->redirect('dashboard'); }
        if (!$this->hasStep('sports')) { $this->redirect('trial/sports'); }

        $state = $this->state();
        $fees  = $state['fees'] ?? [
            ['name' => 'Skladka miesieczna', 'amount' => '100.00', 'period' => 'monthly'],
            ['name' => 'Skladka roczna',     'amount' => '1000.00','period' => 'yearly'],
        ];

        $this->render('onboarding_wizard/step4_fees', [
            'title'       => 'Krok 4/5 - Skladki',
            'currentStep' => 4,
            'fees'        => $fees,
        ]);
    }

    /** POST /trial/fees */
    public function saveStep4(): void
    {
        Csrf::verify();

        $names   = (array)($_POST['fee_name']   ?? []);
        $amounts = (array)($_POST['fee_amount'] ?? []);
        $periods = (array)($_POST['fee_period'] ?? []);
        $allowedPeriods = ['monthly','quarterly','yearly','one_time'];

        $rows = [];
        for ($i = 0, $n = count($names); $i < $n; $i++) {
            $name = trim((string)$names[$i]);
            $amt  = (string)($amounts[$i] ?? '');
            $per  = (string)($periods[$i] ?? 'monthly');
            if ($name === '' && $amt === '') { continue; }
            $amtFloat = (float)str_replace(',', '.', $amt);
            if ($name === '' || $amtFloat <= 0) { continue; }
            if (!in_array($per, $allowedPeriods, true)) { $per = 'monthly'; }
            $rows[] = [
                'name'   => mb_substr($name, 0, 120),
                'amount' => number_format($amtFloat, 2, '.', ''),
                'period' => $per,
            ];
            if (count($rows) >= 5) { break; }
        }

        if (count($rows) < 1) {
            Session::flash('error', 'Dodaj przynajmniej jedna stawke (nazwa + kwota > 0).');
            $this->redirect('trial/fees');
        }

        $this->saveState(['fees' => $rows]);
        $this->redirect('trial/admin');
    }

    // =========================================================
    // Step 5 - Admin & commit
    // =========================================================

    /** GET /trial/admin */
    public function step5(): void
    {
        if (Auth::check()) { $this->redirect('dashboard'); }
        if (!$this->hasStep('fees')) { $this->redirect('trial/fees'); }

        $state = $this->state();
        $this->render('onboarding_wizard/step5_admin', [
            'title'       => 'Krok 5/5 - Twoje konto',
            'currentStep' => 5,
            'data'        => $state['admin'] ?? [],
            'summary'     => $state,
        ]);
    }

    /** POST /trial/admin — commits the whole wizard transaction. */
    public function saveStep5(): void
    {
        Csrf::verify();

        if (!$this->hasStep('fees')) { $this->redirect('trial/fees'); }

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name']  ?? '');
        $email     = trim($_POST['email']      ?? '');
        $password  = (string)($_POST['password'] ?? '');
        $accept    = !empty($_POST['accept_terms']);
        $acceptDpa = !empty($_POST['accept_dpa']);
        $marketing = !empty($_POST['accept_marketing']);

        $errors = [];
        if ($firstName === '' || $lastName === '') {
            $errors[] = 'Podaj imie i nazwisko.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Podaj prawidlowy email.';
        }
        if (mb_strlen($password) < 8
            || !preg_match('/[A-Za-z]/', $password)
            || !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Haslo musi miec min. 8 znakow, litera + cyfra.';
        }
        if (!$accept) {
            $errors[] = 'Musisz zaakceptowac regulamin i polityke prywatnosci.';
        }
        if (!$acceptDpa) {
            $errors[] = 'Musisz zaakceptowac umowe powierzenia przetwarzania danych (DPA).';
        }

        // Pre-flight uniqueness check (transaction will re-check).
        $userModel = new UserModel();
        if ($email !== '' && $userModel->findByEmail($email) !== null) {
            $errors[] = 'Konto z tym emailem juz istnieje. Zaloguj sie przez /auth/login.';
        }

        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            $this->saveState(['admin' => [
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $email,
                'marketing'  => $marketing,
            ]]);
            $this->redirect('trial/admin');
        }

        $state = $this->state();
        $db    = Database::pdo();
        $db->beginTransaction();

        try {
            // 1) clubs
            $clubModel = new ClubModel();
            $clubId    = $clubModel->insert([
                'name'      => $state['club']['name'],
                'city'      => $state['club']['city'],
                'nip'       => $state['club']['nip']   ?? null,
                'email'     => $state['club']['email'],
                'phone'     => $state['club']['phone'] ?? null,
                'is_active' => 1,
            ]);

            // 2) club_customization (ensure row + apply branding)
            $cust = new ClubCustomizationModel();
            $cust->ensureExists($clubId);
            $brand = $state['branding'] ?? [];
            $custData = [
                'subdomain'     => $brand['subdomain']     ?? null,
                'primary_color' => $brand['primary_color'] ?? '#0d6efd',
                'accent_color'  => $brand['accent_color']  ?? '#198754',
                'motto'         => $brand['motto']         ?? null,
            ];
            if (!empty($brand['logo_path'])) {
                $custData['logo_path'] = $brand['logo_path'];
            }
            $cust->upsert($clubId, $custData);

            // 3) club_sports
            $csModel = new ClubSportModel();
            foreach (($state['sports'] ?? []) as $sportId) {
                $csModel->addSportToClub($clubId, (int)$sportId);
            }

            // 4) fee_rates
            $feeModel = new FeeRateModel();
            foreach (($state['fees'] ?? []) as $fee) {
                $feeModel->insert([
                    'club_id'   => $clubId,
                    'name'      => $fee['name'],
                    'amount'    => $fee['amount'],
                    'period'    => $fee['period'],
                    'fee_type'  => 'skladka',
                    'is_active' => 1,
                ]);
            }

            // 5) users (admin) + 6) user_clubs (zarzad)
            $userId = $userModel->create([
                'username'  => $email,
                'email'     => $email,
                'password'  => $password,
                'full_name' => trim($firstName . ' ' . $lastName),
                'phone'     => $state['club']['phone'] ?? null,
                'is_active' => 1,
            ]);
            (new UserClubModel())->grantRole($userId, $clubId, 'zarzad');

            // 7) club_subscriptions — trial 30 dni
            $plan = $db->query(
                "SELECT id FROM subscription_plans
                  WHERE code IN ('trial_v2','trial') AND is_active = 1
                  ORDER BY (code='trial_v2') DESC, id ASC
                  LIMIT 1"
            )->fetchColumn();
            if ($plan) {
                $stmt = $db->prepare(
                    "INSERT INTO club_subscriptions
                        (club_id, plan_id, valid_until, status, billing_cycle)
                     VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'trial', 'monthly')"
                );
                $stmt->execute([$clubId, (int)$plan]);
            }

            // 8) Referral tracking — jesli kod podany w step 1 i walidowany.
            $refCode = $state['referral_code'] ?? null;
            if (is_string($refCode) && $refCode !== '') {
                $this->insertReferral($db, $clubId, $refCode);
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('[onboarding-wizard] commit failed: ' . $e->getMessage());
            Session::flash('error', 'Nie udalo sie zalozyc klubu: ' . $e->getMessage());
            $this->redirect('trial/admin');
        }

        // ── Post-commit: auto-login and clear wizard state ──
        $user = $userModel->findById($userId);
        if ($user) {
            Auth::login($user);
            Auth::setClub($clubId, 'zarzad');
        }

        // ── Legal acceptance log (ToS + Privacy + DPA) ──
        try {
            $legalDocs = new \App\Models\LegalDocumentModel();
            $acceptModel = new \App\Models\LegalAcceptanceModel();
            $ip = $_SERVER['REMOTE_ADDR']     ?? null;
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
            foreach (['tos', 'privacy', 'dpa'] as $type) {
                $doc = $legalDocs->current($type, 'pl');
                if ($doc) {
                    $acceptModel->record([
                        'user_id'     => (int)$userId,
                        'club_id'     => (int)$clubId,
                        'document_id' => (int)$doc['id'],
                        'ip_address'  => $ip,
                        'user_agent'  => $ua,
                        'context'     => 'onboarding',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            error_log('[onboarding-wizard] legal acceptance log failed: ' . $e->getMessage());
        }

        // Best-effort welcome email (template optional, ignore failures).
        try {
            if (class_exists('\\App\\Helpers\\EmailService')) {
                @\App\Helpers\EmailService::queueFromTemplate(
                    $clubId,
                    'admin_welcome',
                    (string)($user['email'] ?? ''),
                    [
                        'club_name'  => $state['club']['name'] ?? '',
                        'first_name' => $firstName,
                    ],
                    trim($firstName . ' ' . $lastName)
                );
            }
        } catch (\Throwable) {}

        Session::remove(self::SESSION_KEY);
        $this->redirect('trial/welcome');
    }

    // =========================================================
    // Welcome
    // =========================================================

    /** GET /trial/welcome */
    public function welcome(): void
    {
        if (!Auth::check()) {
            $this->redirect('trial/start');
        }
        // Use the authenticated layout for a smooth transition.
        $this->view->setLayout('main');
        $this->render('onboarding_wizard/welcome', [
            'title' => 'Witaj w ClubDesk!',
        ]);
    }

    // =========================================================
    // Helpers
    // =========================================================

    /** @return array<string,mixed> */
    private function state(): array
    {
        $s = Session::get(self::SESSION_KEY);
        return is_array($s) ? $s : [];
    }

    /** @param array<string,mixed> $patch */
    private function saveState(array $patch): void
    {
        $s = $this->state();
        foreach ($patch as $k => $v) {
            $s[$k] = $v;
        }
        $s['_updated_at'] = time();
        Session::set(self::SESSION_KEY, $s);
    }

    private function hasStep(string $key): bool
    {
        $s = $this->state();
        return isset($s[$key]) && !empty($s[$key]);
    }

    private function resetIfTimedOut(): void
    {
        $s = Session::get(self::SESSION_KEY);
        if (is_array($s) && isset($s['_updated_at'])
            && (time() - (int)$s['_updated_at']) > self::SESSION_TIMEOUT_SECONDS) {
            Session::remove(self::SESSION_KEY);
            Session::flash('warning', 'Sesja kreatora wygasla - zacznij od poczatku.');
        }
    }

    /**
     * Wyciaga `?ref=` z query i zapisuje w stanie wizarda. Walidacja
     * miekka — jesli kod nieprawidlowy, po prostu nie zapisujemy.
     */
    private function captureRefParam(): void
    {
        $raw = $_GET['ref'] ?? null;
        if (!is_string($raw) || $raw === '') {
            return;
        }
        $code = ReferralCodeService::normalize($raw);
        if ($code === '') {
            return;
        }
        $codeRow = ReferralCodeService::validateForReferred($code);
        if ($codeRow === null) {
            return;
        }
        $this->saveState(['referral_code' => $code]);
    }

    /**
     * Wstawia rekord do club_referrals + inkrementuje times_used.
     * Walidacja anti-abuse: kod aktywny, referrer != referred.
     */
    private function insertReferral(\PDO $db, int $referredClubId, string $code): void
    {
        $codeRow = ReferralCodeService::validateForReferred($code, $referredClubId);
        if ($codeRow === null) {
            return; // best-effort
        }
        $referrerClubId = (int)$codeRow['club_id'];
        if ($referrerClubId === $referredClubId) {
            return; // self-referral
        }
        try {
            $stmt = $db->prepare(
                "INSERT INTO club_referrals
                    (referrer_club_id, referred_club_id, referral_code, status)
                 VALUES (?, ?, ?, 'pending')"
            );
            $stmt->execute([$referrerClubId, $referredClubId, $code]);

            // Increment licznika uzyc kodu.
            $upd = $db->prepare(
                "UPDATE club_referral_codes SET times_used = times_used + 1 WHERE id = ?"
            );
            $upd->execute([(int)$codeRow['id']]);
        } catch (\Throwable $e) {
            // UNIQUE uniq_referred — klub juz polecony. Cichno.
            error_log('[onboarding-wizard] referral insert skipped: ' . $e->getMessage());
        }
    }

    /** Validate Polish NIP: 10 digits + checksum. */
    private function validNip(string $nip): bool
    {
        $nip = preg_replace('/\D/', '', $nip);
        if (strlen($nip) !== 10) { return false; }
        $w  = [6,5,7,2,3,4,5,6,7];
        $sum = 0;
        for ($i = 0; $i < 9; $i++) { $sum += (int)$nip[$i] * $w[$i]; }
        $check = $sum % 11;
        if ($check === 10) { return false; }
        return $check === (int)$nip[9];
    }
}
