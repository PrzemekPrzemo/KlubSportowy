<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\AddonCatalogModel;
use App\Models\ClubAddonModel;
use App\Models\SubscriptionModel;

/**
 * Q.2.2 — Klub admin UI do zarządzania addon-ami subskrypcji.
 *
 * Routes:
 *   GET  /club/subscription              — overview (plan + active addons + total)
 *   GET  /club/subscription/addons       — katalog z opcjami zakupu
 *   POST /club/subscription/addons/buy   — aktywuj addon
 *   POST /club/subscription/addons/:id/cancel  — anuluj (active do valid_until)
 *   POST /club/subscription/addons/:id/reactivate
 */
class SubscriptionAddonsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    /**
     * Overview: aktualny plan + aktywne addon-y + total miesięcznego kosztu.
     */
    public function overview(): void
    {
        $clubId = $this->currentClub();
        $subscription = (new SubscriptionModel())->findForClub($clubId);
        $limits       = (new SubscriptionModel())->effectiveLimits($clubId);
        $addons       = (new SubscriptionModel())->activeAddonsForClub($clubId);
        $addonCost    = (new ClubAddonModel())->monthlyCostForClub($clubId);

        // Bieżące zużycie (do procentów)
        $db = \App\Helpers\Database::pdo();
        $stmt = $db->prepare("SELECT COUNT(*) FROM members WHERE club_id = ? AND status = 'aktywny'");
        $stmt->execute([$clubId]);
        $usedMembers = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM club_sports WHERE club_id = ? AND is_active = 1");
        $stmt->execute([$clubId]);
        $usedSports = (int)$stmt->fetchColumn();

        $this->render('subscription/overview', [
            'title'       => 'Subskrypcja klubu',
            'subscription'=> $subscription,
            'limits'      => $limits,
            'addons'      => $addons,
            'addonCost'   => $addonCost,
            'usedMembers' => $usedMembers,
            'usedSports'  => $usedSports,
        ]);
    }

    /**
     * Katalog dostępnych addonów z formularzem zakupu.
     */
    public function catalog(): void
    {
        $clubId   = $this->currentClub();
        $grouped  = (new AddonCatalogModel())->listGroupedByCategory();
        $addons   = (new SubscriptionModel())->activeAddonsForClub($clubId);
        $limits   = (new SubscriptionModel())->effectiveLimits($clubId);

        // Mapuj juz aktywne addony (po addon_id) — UI pokaze "Już aktywny" zamiast Buy
        $activeAddonIds = array_column($addons, 'addon_id');

        $this->render('subscription/addons_catalog', [
            'title'          => 'Dokup zasoby',
            'grouped'        => $grouped,
            'activeAddonIds' => $activeAddonIds,
            'limits'         => $limits,
            'categories'     => AddonCatalogModel::$CATEGORIES,
        ]);
    }

    /**
     * Aktywuj addon dla klubu (Csrf protected).
     * MVP: brak płatności online — admin oznacza, faktura osobno.
     */
    public function buy(): void
    {
        Csrf::verify();
        $clubId    = $this->currentClub();
        $code      = trim($_POST['addon_code'] ?? '');
        $quantity  = max(1, min(20, (int)($_POST['quantity'] ?? 1)));

        if ($code === '') {
            Session::flash('error', 'Nie wybrano addona.');
            $this->redirect('club/subscription/addons');
        }

        try {
            $id = (new ClubAddonModel())->subscribe($clubId, $code, $quantity);
            Session::flash('success',
                "Addon aktywowany. Faktura zostanie wystawiona w cyklu rozliczeniowym."
                . " (ID: {$id})"
            );
        } catch (\InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('Addon subscribe failed: ' . $e->getMessage());
            Session::flash('error', 'Nie udało się aktywować addona. Skontaktuj się z administratorem.');
        }
        $this->redirect('club/subscription');
    }

    public function cancel(string $id): void
    {
        Csrf::verify();
        // Sprawdź ownership: addon musi należeć do TEGO klubu
        $clubId = $this->currentClub();
        $db = \App\Helpers\Database::pdo();
        $stmt = $db->prepare("SELECT id FROM club_addons WHERE id = ? AND club_id = ?");
        $stmt->execute([(int)$id, $clubId]);
        if (!$stmt->fetchColumn()) {
            Session::flash('error', 'Addon nie należy do Twojego klubu.');
            $this->redirect('club/subscription');
        }

        (new ClubAddonModel())->cancel((int)$id);
        Session::flash('success', 'Addon anulowany. Pozostaje aktywny do końca okresu rozliczeniowego.');
        $this->redirect('club/subscription');
    }

    public function reactivate(string $id): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        $db = \App\Helpers\Database::pdo();
        $stmt = $db->prepare("SELECT id FROM club_addons WHERE id = ? AND club_id = ?");
        $stmt->execute([(int)$id, $clubId]);
        if (!$stmt->fetchColumn()) {
            Session::flash('error', 'Addon nie należy do Twojego klubu.');
            $this->redirect('club/subscription');
        }

        (new ClubAddonModel())->reactivate((int)$id);
        Session::flash('success', 'Addon wznowiony.');
        $this->redirect('club/subscription');
    }
}
