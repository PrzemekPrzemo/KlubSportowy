<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\ReferralCodeService;
use App\Helpers\Session;
use App\Models\ReferralCodeModel;
use App\Models\ReferralModel;
use App\Models\ReferralRewardsConfigModel;

/**
 * Affiliate program — UI dla zarzadu klubu.
 *
 * Routes:
 *   GET  /club/referrals             — dashboard (kod + statystyki + lista)
 *   POST /club/referrals/regenerate  — wygeneruj nowy kod (max 1/dzien)
 *   GET  /club/referrals/share       — modal share (email + social)
 *
 * Dostep: zarzad / admin (oraz super admin).
 */
class ClubReferralsController extends BaseController
{
    private const REGENERATE_FLAG_KEY = 'referral_regen_last_';

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad', 'admin']);
    }

    /** GET /club/referrals */
    public function index(): void
    {
        $clubId = $this->currentClub();

        // Lazy creation kodu.
        $code = ReferralCodeService::ensureForClub($clubId);

        $codeRow    = (new ReferralCodeModel())->findForClub($clubId);
        $referrals  = (new ReferralModel())->listForReferrer($clubId);
        $stats      = (new ReferralModel())->statsForReferrer($clubId);
        $totalEarn  = (new ReferralModel())->totalRewardForReferrer($clubId);
        $rewardCfg  = (new ReferralRewardsConfigModel())->getActiveReward();

        $shareLink = $this->buildShareLink($code);

        $this->render('club_referrals/index', [
            'title'      => 'Polecenia / rabaty',
            'code'       => $code,
            'codeRow'    => $codeRow,
            'referrals'  => $referrals,
            'stats'      => $stats,
            'totalEarn'  => $totalEarn,
            'rewardCfg'  => $rewardCfg,
            'shareLink'  => $shareLink,
        ]);
    }

    /** POST /club/referrals/regenerate */
    public function regenerate(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();

        // Rate limit: max 1/dzien per klub (przez session flag fallback,
        // realne wdrozenie powinno isc do tabeli activity_log).
        $key = self::REGENERATE_FLAG_KEY . $clubId;
        $last = (int)(Session::get($key) ?? 0);
        if ($last > 0 && (time() - $last) < 86400) {
            Session::flash('error', 'Nowy kod mozna wygenerowac maks. raz na dobe.');
            $this->redirect('club/referrals');
        }

        $newCode = ReferralCodeService::generateForClub($clubId);
        Session::set($key, time());

        Session::flash('success', 'Wygenerowano nowy kod: ' . $newCode);
        $this->redirect('club/referrals');
    }

    /** GET /club/referrals/share — modal-friendly fragment. */
    public function share(): void
    {
        $clubId = $this->currentClub();
        $code = ReferralCodeService::ensureForClub($clubId);
        $shareLink = $this->buildShareLink($code);

        $club = $this->getCurrentClubData($clubId);
        $emailSubject = 'Zaproszenie do ClubDesk od ' . ($club['name'] ?? 'klubu');
        $emailBody = "Czesc!\n\n"
            . "Polecam Ci platforme ClubDesk do zarzadzania klubem sportowym. "
            . "Skorzystaj z mojego kodu polecajacego i obaj mamy korzysci.\n\n"
            . "Link do rejestracji: " . $shareLink . "\n"
            . "Kod polecajacy: " . $code . "\n\n"
            . "Pozdrawiam,\n"
            . ($club['name'] ?? '');

        $this->render('club_referrals/share', [
            'title'        => 'Udostepnij zaproszenie',
            'code'         => $code,
            'shareLink'    => $shareLink,
            'emailSubject' => $emailSubject,
            'emailBody'    => $emailBody,
        ]);
    }

    private function buildShareLink(string $code): string
    {
        // url() helper zwraca pelny URL aplikacji.
        return url('trial?ref=' . urlencode($code));
    }

    private function getCurrentClubData(int $clubId): ?array
    {
        try {
            return (new \App\Models\ClubModel())->findById($clubId);
        } catch (\Throwable) {
            return null;
        }
    }
}
