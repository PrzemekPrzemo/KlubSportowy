<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\ReferralModel;
use App\Models\ReferralRewardsConfigModel;

/**
 * Affiliate program — UI dla super admina.
 *
 * Routes:
 *   GET  /admin/platform/referrals                  — lista wszystkich referrali
 *   GET  /admin/platform/referrals/rewards          — CRUD konfiguracji rewardow
 *   POST /admin/platform/referrals/rewards/store    — create reward config
 *   POST /admin/platform/referrals/rewards/:id/update
 *   POST /admin/platform/referrals/rewards/:id/toggle
 */
class AdminReferralsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    /** GET /admin/platform/referrals */
    public function index(): void
    {
        $status = (string)($_GET['status'] ?? '');
        $allowed = ['', 'pending', 'qualified', 'paid', 'expired', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            $status = '';
        }

        $rows = (new ReferralModel())->listForAdmin($status !== '' ? $status : null);
        $stats = $this->platformStats();

        $this->render('admin/platform/referrals/index', [
            'title'  => 'Affiliate program',
            'rows'   => $rows,
            'status' => $status,
            'stats'  => $stats,
        ]);
    }

    /** GET /admin/platform/referrals/rewards */
    public function rewards(): void
    {
        $rewards = (new ReferralRewardsConfigModel())->listAll();
        $this->render('admin/platform/referrals/rewards', [
            'title'   => 'Konfiguracja rewardow',
            'rewards' => $rewards,
        ]);
    }

    /** POST /admin/platform/referrals/rewards/store */
    public function storeReward(): void
    {
        Csrf::verify();
        $data = $this->validateRewardForm();
        if ($data === null) {
            $this->redirect('admin/platform/referrals/rewards');
        }

        (new ReferralRewardsConfigModel())->insert($data);
        Session::flash('success', 'Dodano konfiguracje rewardu.');
        $this->redirect('admin/platform/referrals/rewards');
    }

    /** POST /admin/platform/referrals/rewards/:id/update */
    public function updateReward(string $id): void
    {
        Csrf::verify();
        $id = (int)$id;
        $data = $this->validateRewardForm();
        if ($data === null) {
            $this->redirect('admin/platform/referrals/rewards');
        }

        (new ReferralRewardsConfigModel())->update($id, $data);
        Session::flash('success', 'Zaktualizowano reward.');
        $this->redirect('admin/platform/referrals/rewards');
    }

    /** POST /admin/platform/referrals/rewards/:id/toggle */
    public function toggleReward(string $id): void
    {
        Csrf::verify();
        $id = (int)$id;
        $model = new ReferralRewardsConfigModel();
        $row = $model->findById($id);
        if (!$row) {
            Session::flash('error', 'Reward nie znaleziony.');
            $this->redirect('admin/platform/referrals/rewards');
        }
        $model->update($id, ['is_active' => (int)!$row['is_active']]);
        Session::flash('success', 'Zmieniono status.');
        $this->redirect('admin/platform/referrals/rewards');
    }

    private function validateRewardForm(): ?array
    {
        $name        = trim((string)($_POST['name']        ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $rewardType  = (string)($_POST['reward_type']      ?? 'discount');
        $rewardValue = (float)str_replace(',', '.', (string)($_POST['reward_value'] ?? '0'));
        $minMonths   = max(1, (int)($_POST['min_paid_months'] ?? 1));
        $maxPerRef   = ($_POST['max_per_referrer'] ?? '') === ''
            ? null
            : max(1, (int)$_POST['max_per_referrer']);
        $validFrom   = trim((string)($_POST['valid_from'] ?? ''));
        $validUntil  = trim((string)($_POST['valid_until'] ?? ''));
        $isActive    = !empty($_POST['is_active']) ? 1 : 0;

        $errors = [];
        if (mb_strlen($name) < 3) {
            $errors[] = 'Nazwa min. 3 znaki.';
        }
        if (!in_array($rewardType, ['discount', 'months_free', 'credit'], true)) {
            $errors[] = 'Nieprawidlowy typ rewardu.';
        }
        if ($rewardValue <= 0) {
            $errors[] = 'Wartosc musi byc > 0.';
        }
        if ($validFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $validFrom)) {
            $errors[] = 'Data od: format YYYY-MM-DD.';
        }
        if ($validUntil !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $validUntil)) {
            $errors[] = 'Data do: format YYYY-MM-DD.';
        }
        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            return null;
        }

        return [
            'name'             => mb_substr($name, 0, 120),
            'description'      => $description ?: null,
            'reward_type'      => $rewardType,
            'reward_value'     => number_format($rewardValue, 2, '.', ''),
            'min_paid_months'  => $minMonths,
            'max_per_referrer' => $maxPerRef,
            'valid_from'       => $validFrom ?: null,
            'valid_until'      => $validUntil ?: null,
            'is_active'        => $isActive,
        ];
    }

    /** @return array<string,mixed> */
    private function platformStats(): array
    {
        $db = Database::pdo();
        $stats = [
            'total_referrals' => 0,
            'by_status'       => [],
            'conversion_pct'  => 0.0,
            'total_rewards'   => 0.0,
            'active_codes'    => 0,
        ];
        try {
            $stats['total_referrals'] = (int)$db->query("SELECT COUNT(*) FROM club_referrals")->fetchColumn();
            $rows = $db->query("SELECT status, COUNT(*) c FROM club_referrals GROUP BY status")->fetchAll();
            foreach ($rows as $r) {
                $stats['by_status'][(string)$r['status']] = (int)$r['c'];
            }
            $pendingPlus = ($stats['by_status']['pending'] ?? 0)
                + ($stats['by_status']['qualified'] ?? 0)
                + ($stats['by_status']['paid'] ?? 0);
            $convNum = ($stats['by_status']['qualified'] ?? 0) + ($stats['by_status']['paid'] ?? 0);
            $stats['conversion_pct'] = $pendingPlus > 0
                ? round(($convNum / $pendingPlus) * 100, 1)
                : 0.0;
            $stats['total_rewards'] = (float)$db->query(
                "SELECT COALESCE(SUM(reward_value),0) FROM club_referrals
                  WHERE status IN ('qualified','paid') AND reward_applied = 1"
            )->fetchColumn();
            $stats['active_codes'] = (int)$db->query(
                "SELECT COUNT(*) FROM club_referral_codes WHERE is_active = 1"
            )->fetchColumn();
        } catch (\Throwable) {}
        return $stats;
    }
}
