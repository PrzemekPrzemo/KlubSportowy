<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Feature;
use App\Helpers\Session;
use App\Models\ClubFeatureOverrideModel;
use App\Models\ClubModel;
use App\Models\FeatureFlagCatalogModel;

/**
 * Master Admin: zarządzanie katalogiem feature flags + per-klub override.
 *
 * Routes (zarejestrowane w public/index.php):
 *   GET  /admin/platform/feature-flags
 *   GET  /admin/platform/feature-flags/clubs/:clubId
 *   POST /admin/platform/feature-flags/override
 *   POST /admin/platform/feature-flags/clear
 */
class AdminFeatureFlagsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    /** Lista wszystkich flag w katalogu (Master Admin tool). */
    public function index(): void
    {
        $catalog = new FeatureFlagCatalogModel();
        $flags   = $catalog->listAll();

        // Hydrate default_in_plan z JSON do tablicy dla wygody w widoku
        foreach ($flags as &$f) {
            $decoded = json_decode((string)($f['default_in_plan'] ?? ''), true);
            $f['default_in_plan_parsed'] = is_array($decoded) ? $decoded : [];
        }
        unset($f);

        $this->render('admin/feature_flags/index', [
            'title'      => 'Feature flags — katalog',
            'flags'      => $flags,
            'planCodes'  => $catalog->listPlanCodes(),
        ]);
    }

    /** Lista override-ów dla konkretnego klubu + wszystkie flagi z effective state. */
    public function clubOverrides(string $clubId): void
    {
        $cid  = (int)$clubId;
        $club = (new ClubModel())->findById($cid);
        if (!$club) {
            Session::flash('error', 'Nie znaleziono klubu.');
            $this->redirect('admin/clubs');
        }

        $flagsState = Feature::list($cid); // [{code, enabled, source, ...}]
        $overrides  = (new ClubFeatureOverrideModel())->listForClub($cid);

        // Wyciągnij plan klubu do nagłówka
        $planCode = '';
        try {
            $stmt = Database::pdo()->prepare(
                "SELECT sp.code, sp.name
                   FROM club_subscriptions cs
                   JOIN subscription_plans sp ON sp.id = cs.plan_id
                  WHERE cs.club_id = ? LIMIT 1"
            );
            $stmt->execute([$cid]);
            $planRow  = $stmt->fetch();
            $planCode = $planRow['code'] ?? '';
            $planName = $planRow['name'] ?? '';
        } catch (\Throwable) {
            $planName = '';
        }

        $this->render('admin/feature_flags/club', [
            'title'       => 'Feature flags klubu: ' . $club['name'],
            'club'        => $club,
            'flagsState'  => $flagsState,
            'overrides'   => array_column($overrides, null, 'feature_code'),
            'planCode'    => $planCode,
            'planName'    => $planName,
        ]);
    }

    /** Ustawia override (włącz/wyłącz) feature flag dla konkretnego klubu. */
    public function saveOverride(): void
    {
        Csrf::verify();

        $clubId      = (int)($_POST['club_id'] ?? 0);
        $featureCode = trim((string)($_POST['feature_code'] ?? ''));
        $enabled     = !empty($_POST['enabled']);
        $reason      = trim((string)($_POST['reason'] ?? '')) ?: null;
        $expiresRaw  = trim((string)($_POST['expires_at'] ?? ''));
        $expiresAt   = $expiresRaw !== '' ? $expiresRaw : null;

        if ($clubId <= 0 || $featureCode === '') {
            Session::flash('error', 'Brak wymaganych pól (club_id, feature_code).');
            $this->redirect('admin/platform/feature-flags');
        }

        // Walidacja: flaga musi istnieć w katalogu
        $cat = (new FeatureFlagCatalogModel())->findByCode($featureCode);
        if (!$cat) {
            Session::flash('error', 'Nieznana feature flag: ' . $featureCode);
            $this->redirect('admin/platform/feature-flags/clubs/' . $clubId);
        }

        // Normalizacja expires_at: 'Y-m-d' z form date → 'Y-m-d 23:59:59'
        if ($expiresAt !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiresAt)) {
            $expiresAt .= ' 23:59:59';
        }

        (new ClubFeatureOverrideModel())->set(
            $clubId,
            $featureCode,
            $enabled,
            $reason,
            $expiresAt,
            Auth::id()
        );

        Session::flash('success', 'Override zapisany: ' . $featureCode . ' = ' . ($enabled ? 'ON' : 'OFF'));
        $this->redirect('admin/platform/feature-flags/clubs/' . $clubId);
    }

    /** Usuwa override (klub wraca do domyślnej wartości z planu). */
    public function clearOverride(): void
    {
        Csrf::verify();

        $clubId      = (int)($_POST['club_id'] ?? 0);
        $featureCode = trim((string)($_POST['feature_code'] ?? ''));

        if ($clubId <= 0 || $featureCode === '') {
            Session::flash('error', 'Brak wymaganych pól.');
            $this->redirect('admin/platform/feature-flags');
        }

        (new ClubFeatureOverrideModel())->clear($clubId, $featureCode);
        Session::flash('success', 'Override usunięty: ' . $featureCode);
        $this->redirect('admin/platform/feature-flags/clubs/' . $clubId);
    }
}
