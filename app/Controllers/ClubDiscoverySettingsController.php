<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Geocoder;
use App\Helpers\Session;
use App\Models\ActivityLogModel;
use App\Models\ClubModel;

/**
 * Panel admina dla "Publicznej prezentacji" klubu (Club Discovery opt-in).
 *
 * Wymagana rola: zarzad (decyzja biznesowa o opt-in publicznej widocznosci).
 * Aktualizuje kolumny dodane w migracji 095 (public_discovery_enabled, public_slug,
 * latitude/longitude, description_short, contact_phone, website_url).
 */
class ClubDiscoverySettingsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad']);
    }

    /**
     * GET /club/settings/discovery — formularz konfiguracji publicznego profilu.
     */
    public function show(): void
    {
        $clubId    = $this->currentClub();
        $clubModel = new ClubModel();
        $club      = $clubModel->findById($clubId);
        $sports    = $clubModel->sportsForClub($clubId);

        $this->render('club/discovery', [
            'title'  => 'Publiczna prezentacja (katalog ClubDesk)',
            'club'   => $club,
            'sports' => $sports,
        ]);
    }

    /**
     * POST /club/settings/discovery — zapis ustawien publicznej prezentacji.
     */
    public function save(): void
    {
        Csrf::verify();
        $clubId    = $this->currentClub();
        $clubModel = new ClubModel();
        $club      = $clubModel->findById($clubId);
        if ($club === null) {
            Session::flash('error', 'Klub nie istnieje.');
            $this->redirect('club/settings/discovery');
        }

        $enabled   = isset($_POST['public_discovery_enabled']) ? 1 : 0;
        $descShort = trim((string)($_POST['description_short'] ?? ''));
        if ($descShort !== '') {
            $descShort = mb_substr($descShort, 0, 500);
        }
        $contactPhone = trim((string)($_POST['contact_phone'] ?? ''));
        if ($contactPhone !== '') {
            $contactPhone = mb_substr($contactPhone, 0, 50);
        }
        $websiteUrl = trim((string)($_POST['website_url'] ?? ''));
        if ($websiteUrl !== '') {
            $websiteUrl = mb_substr($websiteUrl, 0, 255);
            if (!preg_match('#^https?://#i', $websiteUrl)) {
                $websiteUrl = 'https://' . $websiteUrl;
            }
        }

        // Geokoordynaty — manual override lub auto via Nominatim, jezeli "do_geocode" zaznaczone.
        $latitude  = $this->parseFloatOrNull($_POST['latitude']  ?? null);
        $longitude = $this->parseFloatOrNull($_POST['longitude'] ?? null);

        $doGeocode = isset($_POST['do_geocode']) && ($latitude === null || $longitude === null);
        if ($doGeocode) {
            $address = trim((string)($club['address'] ?? ''));
            $city    = trim((string)($club['city'] ?? ''));
            $full    = trim($address . ' ' . $city);
            if ($full !== '') {
                try {
                    $g     = new Geocoder();
                    $coord = $g->geocode($full);
                    if ($coord !== null) {
                        $latitude  = $coord['lat'];
                        $longitude = $coord['lng'];
                    }
                } catch (\Throwable) {
                    // Best-effort: brak geocodingu nie blokuje zapisu.
                }
            }
        }

        // Generuj slug jezeli enabling po raz pierwszy i brak slug
        $publicSlug = (string)($club['public_slug'] ?? '');
        if ($enabled === 1 && $publicSlug === '') {
            try {
                $publicSlug = $clubModel->generatePublicSlug($clubId);
            } catch (\Throwable) {
                Session::flash('error', 'Nie udalo sie wygenerowac unikatowego slug. Sprobuj ponownie.');
                $this->redirect('club/settings/discovery');
            }
        }

        $data = [
            'public_discovery_enabled' => $enabled,
            'public_slug'              => $publicSlug !== '' ? $publicSlug : null,
            'description_short'        => $descShort !== '' ? $descShort : null,
            'contact_phone'            => $contactPhone !== '' ? $contactPhone : null,
            'website_url'              => $websiteUrl !== '' ? $websiteUrl : null,
            'latitude'                 => $latitude,
            'longitude'                => $longitude,
        ];

        $clubModel->update($clubId, $data);

        // Odswiez cache sports_offered_json (zawsze, takze przy disable, zeby bylo aktualne na re-enable).
        try {
            $clubModel->refreshSportsOfferedJson($clubId);
        } catch (\Throwable) {
            // Best-effort.
        }

        try {
            (new ActivityLogModel())->log(
                'club_discovery_settings',
                'club',
                $clubId,
                'enabled=' . $enabled . ' slug=' . ($publicSlug ?: '—')
            );
        } catch (\Throwable) {}

        Session::flash('success', $enabled === 1
            ? 'Klub jest teraz widoczny w publicznym katalogu ClubDesk.'
            : 'Ustawienia zapisane (klub niewidoczny publicznie).'
        );
        $this->redirect('club/settings/discovery');
    }

    private function parseFloatOrNull(mixed $raw): ?float
    {
        if ($raw === null) return null;
        $s = trim((string)$raw);
        if ($s === '') return null;
        $s = str_replace(',', '.', $s);
        if (!is_numeric($s)) return null;
        return round((float)$s, 6);
    }
}
