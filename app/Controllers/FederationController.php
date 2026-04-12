<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\FederationClient;
use App\Helpers\Session;
use App\Models\ClubSettingsModel;
use App\Models\FederationModel;
use App\Models\MemberLicenseModel;

class FederationController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    /** Dashboard integracji — lista federacji z ich statusem. */
    public function index(): void
    {
        $integrations = FederationClient::supportedIntegrations();
        $federations  = (new FederationModel())->listActive();
        $clubId       = $this->currentClub();
        $cs           = new ClubSettingsModel();

        // Dodaj status konfiguracji per-klub
        $configured = [];
        foreach ($integrations as $code => $info) {
            $prefix = 'federation_' . strtolower($code) . '_';
            $configured[$code] = [
                'has_login'   => !empty($cs->get($clubId, $prefix . 'login', '')),
                'has_api_key' => !empty($cs->get($clubId, $prefix . 'api_key', '')),
            ];
        }

        $this->render('federation/index', [
            'title'        => 'Integracje z federacjami',
            'integrations' => $integrations,
            'federations'  => $federations,
            'configured'   => $configured,
        ]);
    }

    /** Konfiguracja credentiali federacji per-klub. */
    public function configure(): void
    {
        $clubId = $this->currentClub();
        $cs     = new ClubSettingsModel();

        $configs = [];
        foreach (FederationClient::supportedIntegrations() as $code => $info) {
            $prefix = 'federation_' . strtolower($code) . '_';
            $configs[$code] = [
                'login'   => $cs->get($clubId, $prefix . 'login', ''),
                'api_key' => $cs->get($clubId, $prefix . 'api_key', ''),
                'club_id' => $cs->get($clubId, $prefix . 'club_id', ''),
            ];
        }

        $this->render('federation/configure', [
            'title'   => 'Konfiguracja integracji federacji',
            'configs' => $configs,
        ]);
    }

    public function saveConfigure(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        $cs     = new ClubSettingsModel();

        foreach (FederationClient::supportedIntegrations() as $code => $info) {
            $lcode  = strtolower($code);
            $prefix = 'federation_' . $lcode . '_';
            foreach (['login', 'api_key', 'club_id'] as $field) {
                $value = trim($_POST[$lcode . '_' . $field] ?? '');
                if ($value !== '') {
                    $cs->set($clubId, $prefix . $field, $value, 'text', "Federacja {$code}: {$field}");
                }
            }
            // Hasło (osobno — nie nadpisuj pustym)
            $pass = trim($_POST[$lcode . '_pass'] ?? '');
            if ($pass !== '') {
                $cs->set($clubId, $prefix . 'pass', $pass, 'text', "Federacja {$code}: hasło");
            }
        }

        Session::flash('success', 'Konfiguracja federacji zapisana.');
        $this->redirect('federation');
    }

    /** Ręczna weryfikacja licencji. */
    public function verifyLicense(string $licenseId): void
    {
        $license = (new MemberLicenseModel())->findById((int)$licenseId);
        if (!$license) {
            Session::flash('error', 'Nie znaleziono licencji.');
            $this->redirect('federation');
        }

        // Szukaj federacji po sport_id
        $db   = \App\Helpers\Database::pdo();
        $stmt = $db->prepare(
            "SELECT f.code FROM federations f
             JOIN sports s ON s.federation_id = f.id
             WHERE s.id = ?"
        );
        $stmt->execute([$license['sport_id']]);
        $fedCode = $stmt->fetchColumn();

        if (!$fedCode) {
            Session::flash('warning', 'Brak powiązanej federacji dla tego sportu.');
            $this->redirect('federation');
        }

        $result = FederationClient::verifyLicense($fedCode, $license['license_number'], $this->currentClub());

        $this->render('federation/verify_result', [
            'title'   => 'Wynik weryfikacji licencji',
            'license' => $license,
            'result'  => $result,
            'fedCode' => $fedCode,
        ]);
    }
}
