<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\ClubCustomizationModel;

/**
 * Master Admin: zarządzanie planami cenowymi + branding per-klub + support.
 */
class AdminPlatformController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    // ── Plany cenowe (CRUD) ─────────────────────────────────

    public function plans(): void
    {
        $db    = Database::pdo();
        $plans = $db->query("SELECT * FROM subscription_plans ORDER BY sort_order")->fetchAll();
        $this->render('admin/platform/plans', [
            'title' => 'Zarządzanie planami cenowymi',
            'plans' => $plans,
        ]);
    }

    public function editPlan(string $id): void
    {
        $db   = Database::pdo();
        $stmt = $db->prepare("SELECT * FROM subscription_plans WHERE id = ?");
        $stmt->execute([(int)$id]);
        $plan = $stmt->fetch();
        if (!$plan) { Session::flash('error', 'Nie znaleziono planu.'); $this->redirect('admin/platform/plans'); }

        $this->render('admin/platform/plan_form', [
            'title' => 'Edycja planu: ' . $plan['name'],
            'plan'  => $plan,
        ]);
    }

    public function updatePlan(string $id): void
    {
        Csrf::verify();
        $db = Database::pdo();
        $stmt = $db->prepare(
            "UPDATE subscription_plans SET name = ?, max_members = ?, max_sports = ?,
                    price_monthly = ?, price_yearly = ?, features = ?, is_active = ?, sort_order = ?
             WHERE id = ?"
        );
        $stmt->execute([
            trim($_POST['name'] ?? ''),
            !empty($_POST['max_members']) ? (int)$_POST['max_members'] : null,
            !empty($_POST['max_sports']) ? (int)$_POST['max_sports'] : null,
            (float)($_POST['price_monthly'] ?? 0),
            (float)($_POST['price_yearly'] ?? 0),
            trim($_POST['features'] ?? '') ?: null,
            isset($_POST['is_active']) ? 1 : 0,
            (int)($_POST['sort_order'] ?? 0),
            (int)$id,
        ]);
        Session::flash('success', 'Plan zaktualizowany.');
        $this->redirect('admin/platform/plans');
    }

    public function createPlan(): void
    {
        $this->render('admin/platform/plan_form', [
            'title' => 'Nowy plan',
            'plan'  => null,
        ]);
    }

    public function storePlan(): void
    {
        Csrf::verify();
        $db = Database::pdo();
        $stmt = $db->prepare(
            "INSERT INTO subscription_plans (code, name, max_members, max_sports,
                    price_monthly, price_yearly, features, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $code = strtolower(preg_replace('/[^a-z0-9]/', '_', trim($_POST['name'] ?? 'plan')));
        $stmt->execute([
            $code,
            trim($_POST['name'] ?? ''),
            !empty($_POST['max_members']) ? (int)$_POST['max_members'] : null,
            !empty($_POST['max_sports']) ? (int)$_POST['max_sports'] : null,
            (float)($_POST['price_monthly'] ?? 0),
            (float)($_POST['price_yearly'] ?? 0),
            trim($_POST['features'] ?? '') ?: null,
            isset($_POST['is_active']) ? 1 : 0,
            (int)($_POST['sort_order'] ?? 0),
        ]);
        Session::flash('success', 'Plan utworzony.');
        $this->redirect('admin/platform/plans');
    }

    // ── Branding per-klub (edycja z poziomu master admin) ───

    public function clubBranding(string $clubId): void
    {
        $club   = (new \App\Models\ClubModel())->findById((int)$clubId);
        if (!$club) { Session::flash('error', 'Nie znaleziono klubu.'); $this->redirect('admin/clubs'); }

        $custom = (new ClubCustomizationModel())->findForClub((int)$clubId) ?? ClubCustomizationModel::defaults();
        $this->render('admin/platform/club_branding', [
            'title'  => 'Branding: ' . $club['name'],
            'club'   => $club,
            'custom' => $custom,
        ]);
    }

    public function saveClubBranding(string $clubId): void
    {
        Csrf::verify();
        $data = [
            'primary_color' => trim($_POST['primary_color'] ?? '#EE2C28'),
            'navbar_bg'     => trim($_POST['navbar_bg'] ?? '#232322'),
            'accent_color'  => trim($_POST['accent_color'] ?? '#EE2C28'),
            'custom_css'    => trim($_POST['custom_css'] ?? '') ?: null,
            'motto'         => trim($_POST['motto'] ?? '') ?: null,
            'subdomain'     => trim($_POST['subdomain'] ?? '') ?: null,
        ];

        // W.2: 3 sloty logo klubu — main (logo_path) + alt + dark
        $logoFields = [
            'logo'      => 'logo_path',      // main (legacy field name)
            'logo_alt'  => 'logo_alt_path',
            'logo_dark' => 'logo_dark_path',
        ];
        foreach ($logoFields as $inputName => $dbCol) {
            if (!empty($_FILES[$inputName]['tmp_name'])) {
                $path = $this->saveClubLogo($_FILES[$inputName], (int)$clubId, $inputName);
                if ($path !== null) $data[$dbCol] = $path;
            }
            // Reset (usuń aktualne)
            $variant = $inputName === 'logo' ? 'main' : str_replace('logo_', '', $inputName);
            if (!empty($_POST['reset_' . $variant])) {
                $data[$dbCol] = null;
            }
        }

        (new ClubCustomizationModel())->upsert((int)$clubId, $data);
        Session::flash('success', 'Branding klubu zaktualizowany.');
        $this->redirect('admin/platform/branding/' . $clubId);
    }

    /**
     * W.2: zapisuje plik logo klubu (3 warianty: logo / logo_alt / logo_dark).
     */
    private function saveClubLogo(array $file, int $clubId, string $inputName): ?string
    {
        $variant = $inputName === 'logo' ? 'main' : str_replace('logo_', '', $inputName);
        return \App\Helpers\LogoUploader::save(
            $file,
            ROOT_PATH . '/public/uploads/clubs/' . $clubId,
            "uploads/clubs/{$clubId}",
            $variant
        );
    }

    // ── System branding (W.1) ───────────────────────────────

    /**
     * Master Admin: konfiguracja globalnego logo systemu (color + white).
     */
    public function systemBranding(): void
    {
        $settings = new \App\Models\SettingModel();
        $this->render('admin/platform/system_branding', [
            'title'      => 'Logo systemu',
            'logoColor'  => (string)$settings->get('system_logo_color', ''),
            'logoWhite'  => (string)$settings->get('system_logo_white', ''),
            'logoAlt'    => (string)$settings->get('system_logo_alt',   'ClubDesk'),
        ]);
    }

    public function saveSystemBranding(): void
    {
        Csrf::verify();
        $settings = new \App\Models\SettingModel();

        // Upload color variant
        if (!empty($_FILES['logo_color']['tmp_name'])) {
            $path = $this->saveSystemLogo($_FILES['logo_color'], 'color');
            if ($path !== null) $settings->set('system_logo_color', $path);
        }
        // Upload white variant
        if (!empty($_FILES['logo_white']['tmp_name'])) {
            $path = $this->saveSystemLogo($_FILES['logo_white'], 'white');
            if ($path !== null) $settings->set('system_logo_white', $path);
        }
        // Alt text
        $alt = trim($_POST['logo_alt'] ?? '');
        if ($alt !== '') $settings->set('system_logo_alt', $alt);

        // Reset (powrót do domyślnych wbudowanych SVG)
        if (isset($_POST['reset_color'])) $settings->set('system_logo_color', '');
        if (isset($_POST['reset_white'])) $settings->set('system_logo_white', '');

        Session::flash('success', 'Logo systemu zaktualizowane.');
        $this->redirect('admin/platform/system-branding');
    }

    /** Zapisuje plik do `public/uploads/system/` przez LogoUploader. */
    private function saveSystemLogo(array $file, string $variant): ?string
    {
        return \App\Helpers\LogoUploader::save(
            $file,
            ROOT_PATH . '/public/uploads/system',
            'uploads/system',
            $variant
        );
    }

    // ── Support tickets ─────────────────────────────────────

    public function supportTickets(): void
    {
        $db = Database::pdo();
        $tickets = $db->query(
            "SELECT st.*, c.name AS club_name, u.full_name AS author_name
             FROM support_tickets st
             LEFT JOIN clubs c ON c.id = st.club_id
             LEFT JOIN users u ON u.id = st.user_id
             ORDER BY st.status = 'open' DESC, st.created_at DESC"
        )->fetchAll();

        $this->render('admin/platform/support', [
            'title'   => 'Zgłoszenia techniczne',
            'tickets' => $tickets,
        ]);
    }

    public function viewTicket(string $id): void
    {
        $db   = Database::pdo();
        $stmt = $db->prepare(
            "SELECT st.*, c.name AS club_name, u.full_name AS author_name
             FROM support_tickets st
             LEFT JOIN clubs c ON c.id = st.club_id
             LEFT JOIN users u ON u.id = st.user_id
             WHERE st.id = ?"
        );
        $stmt->execute([(int)$id]);
        $ticket = $stmt->fetch();
        if (!$ticket) { Session::flash('error', 'Nie znaleziono.'); $this->redirect('admin/platform/support'); }

        $replies = $db->prepare(
            "SELECT r.*, u.full_name AS author_name
             FROM support_replies r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.ticket_id = ?
             ORDER BY r.created_at ASC"
        );
        $replies->execute([(int)$id]);

        $this->render('admin/platform/ticket_view', [
            'title'   => 'Zgłoszenie #' . $id,
            'ticket'  => $ticket,
            'replies' => $replies->fetchAll(),
        ]);
    }

    public function replyTicket(string $id): void
    {
        Csrf::verify();
        $body = trim($_POST['body'] ?? '');
        if ($body === '') { Session::flash('error', 'Treść wymagana.'); $this->redirect('admin/platform/support/' . $id); }

        $db = Database::pdo();
        $db->prepare(
            "INSERT INTO support_replies (ticket_id, user_id, body) VALUES (?, ?, ?)"
        )->execute([(int)$id, \App\Helpers\Auth::id(), $body]);

        // Update ticket status
        $newStatus = $_POST['status'] ?? 'in_progress';
        $db->prepare("UPDATE support_tickets SET status = ? WHERE id = ?")->execute([$newStatus, (int)$id]);

        Session::flash('success', 'Odpowiedź wysłana.');
        $this->redirect('admin/platform/support/' . $id);
    }

    public function closeTicket(string $id): void
    {
        Csrf::verify();
        $db = Database::pdo();
        $db->prepare("UPDATE support_tickets SET status = 'closed' WHERE id = ?")->execute([(int)$id]);
        Session::flash('success', 'Zgłoszenie zamknięte.');
        $this->redirect('admin/platform/support');
    }

    // ── Sports catalog ──────────────────────────────────────

    public function sportsCatalog(): void
    {
        $manifests = \App\Helpers\SportModuleLoader::load();
        $db        = Database::pdo();
        $sports    = [];

        foreach ($manifests as $manifest) {
            $key  = $manifest['key'];
            $stmt = $db->prepare(
                "SELECT COUNT(DISTINCT cs.club_id) AS club_count,
                        COUNT(DISTINCT ms.member_id) AS member_count
                 FROM club_sports cs
                 JOIN sports s ON s.id = cs.sport_id
                 LEFT JOIN member_sports ms ON ms.club_sport_id = cs.id
                 WHERE s.key = ?"
            );
            $stmt->execute([$key]);
            $stats = $stmt->fetch();

            $sports[$key] = [
                'manifest'     => $manifest,
                'club_count'   => (int)($stats['club_count'] ?? 0),
                'member_count' => (int)($stats['member_count'] ?? 0),
                'deprecated'   => $key === 'shooting',
            ];
        }

        $this->render('admin/sports_catalog', [
            'title'  => 'Katalog sportów',
            'sports' => $sports,
        ]);
    }

    public function toggleSport(string $key): void
    {
        Csrf::verify();
        if ($key === 'shooting') {
            Session::flash('error', 'Moduł shooting jest obsługiwany przez shotero.pl — nie można go wyłączyć globalnie.');
            $this->redirect('admin/sports');
        }

        $db    = Database::pdo();
        $stmt  = $db->prepare("SELECT is_globally_active FROM sport_module_flags WHERE sport_key = ?");
        $stmt->execute([$key]);
        $row   = $stmt->fetch();

        if ($row) {
            $newVal = $row['is_globally_active'] ? 0 : 1;
            $db->prepare("UPDATE sport_module_flags SET is_globally_active = ? WHERE sport_key = ?")->execute([$newVal, $key]);
        } else {
            $db->prepare("INSERT INTO sport_module_flags (sport_key, is_globally_active) VALUES (?, 0)")->execute([$key]);
        }

        Session::flash('success', 'Status sportu zaktualizowany.');
        $this->redirect('admin/sports/catalog');
    }
}
