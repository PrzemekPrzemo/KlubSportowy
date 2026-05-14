<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\ClubOnboardingConfigModel;
use App\Models\EmailEventCatalogModel;
use App\Models\FeeRateModel;
use App\Models\SportModel;

/**
 * Konfiguracja workflow onboardingu czlonka per klub.
 * Dostepne tylko dla zarzadu i administratora klubu.
 */
class ClubOnboardingConfigController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['admin', 'zarzad']);
    }

    public function show(): void
    {
        $clubId = $this->currentClub();
        $model  = new ClubOnboardingConfigModel();
        $config = $model->forClub($clubId);

        $clubSports = (new SportModel())->listForClub($clubId);

        // FeeRates klubu (do wyboru auto_assign_fee_rate_id) — defensywnie
        $rates = [];
        try {
            $rates = (new FeeRateModel())->listForClub(null, true);
        } catch (\Throwable) {
            $rates = [];
        }

        $events = (new EmailEventCatalogModel())->listActive();

        $this->render('club/onboarding_config', [
            'title'      => 'Konfiguracja onboardingu czlonka',
            'config'     => $config,
            'clubSports' => $clubSports,
            'feeRates'   => $rates,
            'events'     => $events,
        ]);
    }

    public function save(): void
    {
        Csrf::verify();

        $payload = [
            'require_pesel'                    => $_POST['require_pesel']                    ?? 0,
            'require_address'                  => $_POST['require_address']                  ?? 0,
            'require_emergency_contact'        => $_POST['require_emergency_contact']        ?? 0,
            'require_medical_consent'          => $_POST['require_medical_consent']          ?? 0,
            'require_photo'                    => $_POST['require_photo']                    ?? 0,
            'require_parent_data_for_minors'   => $_POST['require_parent_data_for_minors']   ?? 0,
            'auto_send_welcome_email'          => $_POST['auto_send_welcome_email']          ?? 0,
            'auto_assign_sport_id'             => $_POST['auto_assign_sport_id']             ?? null,
            'auto_assign_fee_rate_id'          => $_POST['auto_assign_fee_rate_id']          ?? null,
            'welcome_email_template'           => $_POST['welcome_email_template']           ?? null,
            'min_age_years'                    => $_POST['min_age_years']                    ?? null,
            'max_age_years'                    => $_POST['max_age_years']                    ?? null,
            'require_parent_consent_under_age' => $_POST['require_parent_consent_under_age'] ?? 18,
            'custom_consents'                  => $this->parseConsents(),
            'custom_fields'                    => $this->parseCustomFields(),
        ];

        try {
            (new ClubOnboardingConfigModel())->upsert($payload);
            Session::flash('success', 'Konfiguracja onboardingu zapisana.');
        } catch (\InvalidArgumentException $e) {
            Session::flash('error', 'Nieprawidlowe dane JSON: ' . $e->getMessage());
            http_response_code(400);
        } catch (\Throwable $e) {
            error_log('OnboardingConfig save failed: ' . $e->getMessage());
            Session::flash('error', 'Blad zapisu konfiguracji.');
        }

        $this->redirect('club/onboarding-config');
    }

    /**
     * Parsuje custom consents — albo z dynamic listy ($_POST['consents'] array of arrays),
     * albo z surowego JSON ($_POST['consents_json']).
     */
    private function parseConsents(): array
    {
        if (!empty($_POST['consents_json'])) {
            $decoded = json_decode((string)$_POST['consents_json'], true);
            if (!is_array($decoded)) {
                throw new \InvalidArgumentException('consents_json must be a JSON array');
            }
            return $this->normalizeConsents($decoded);
        }

        $rows = $_POST['consents'] ?? [];
        if (!is_array($rows)) return [];

        return $this->normalizeConsents($rows);
    }

    private function normalizeConsents(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            $key = trim((string)($row['key'] ?? ''));
            $label = trim((string)($row['label'] ?? ''));
            if ($key === '' || $label === '') continue;
            $out[] = [
                'key'      => substr($key, 0, 60),
                'label'    => substr($label, 0, 200),
                'body'     => trim((string)($row['body'] ?? '')),
                'required' => !empty($row['required']) ? 1 : 0,
                'version'  => substr(trim((string)($row['version'] ?? '1.0')), 0, 20),
            ];
        }
        return $out;
    }

    private function parseCustomFields(): array
    {
        if (!empty($_POST['custom_fields_json'])) {
            $decoded = json_decode((string)$_POST['custom_fields_json'], true);
            if (!is_array($decoded)) {
                throw new \InvalidArgumentException('custom_fields_json must be a JSON array');
            }
            return $this->normalizeFields($decoded);
        }
        $rows = $_POST['custom_fields'] ?? [];
        if (!is_array($rows)) return [];
        return $this->normalizeFields($rows);
    }

    private function normalizeFields(array $rows): array
    {
        $allowedTypes = ['text', 'select', 'number', 'date', 'textarea', 'checkbox'];
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            $key   = trim((string)($row['key'] ?? ''));
            $label = trim((string)($row['label'] ?? ''));
            $type  = (string)($row['type'] ?? 'text');
            if ($key === '' || $label === '') continue;
            if (!in_array($type, $allowedTypes, true)) $type = 'text';

            $options = [];
            if ($type === 'select') {
                $raw = $row['options'] ?? [];
                if (is_string($raw)) {
                    $raw = array_filter(array_map('trim', explode(',', $raw)));
                }
                if (is_array($raw)) {
                    foreach ($raw as $opt) {
                        $opt = trim((string)$opt);
                        if ($opt !== '') $options[] = substr($opt, 0, 100);
                    }
                }
            }

            $out[] = [
                'key'      => substr(preg_replace('/[^a-z0-9_]/i', '_', $key), 0, 60),
                'label'    => substr($label, 0, 200),
                'type'     => $type,
                'required' => !empty($row['required']) ? 1 : 0,
                'options'  => $options,
            ];
        }
        return $out;
    }
}
