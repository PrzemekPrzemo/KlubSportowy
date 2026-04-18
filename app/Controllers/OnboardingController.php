<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\CsvImporter;
use App\Helpers\Session;
use App\Models\ClubCustomizationModel;
use App\Models\ClubModel;
use App\Models\ClubSportModel;
use App\Models\MemberModel;
use App\Models\SportModel;

class OnboardingController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->view->setLayout('onboarding');
    }

    // ── Step 1: Club data ────────────────────────────────────

    public function step1(): void
    {
        $clubId = $this->currentClub();
        $club   = (new ClubModel())->findById($clubId);

        $this->render('onboarding/step1', [
            'title'       => 'Onboarding — Dane klubu',
            'currentStep' => 1,
            'club'        => $club,
        ]);
    }

    public function saveStep1(): void
    {
        Csrf::verify();

        $clubId = $this->currentClub();
        $data   = [
            'name'  => trim($_POST['name'] ?? ''),
            'city'  => trim($_POST['city'] ?? ''),
            'nip'   => trim($_POST['nip'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
        ];

        (new ClubModel())->update($clubId, $data);

        Session::flash('success', 'Dane klubu zapisane.');
        $this->redirect('onboarding/step2');
    }

    // ── Step 2: Sports selection ─────────────────────────────

    public function step2(): void
    {
        $clubId       = $this->currentClub();
        $allSports    = (new SportModel())->listActive();
        $clubSports   = (new SportModel())->listForClub($clubId);
        $clubSportIds = array_column($clubSports, 'id');

        $this->render('onboarding/step2', [
            'title'        => 'Onboarding — Sekcje sportowe',
            'currentStep'  => 2,
            'allSports'    => $allSports,
            'clubSportIds' => $clubSportIds,
        ]);
    }

    public function saveStep2(): void
    {
        Csrf::verify();

        $clubId   = $this->currentClub();
        $selected = $_POST['sports'] ?? [];

        $csModel = new ClubSportModel();
        foreach ($selected as $sportId) {
            $csModel->addSportToClub($clubId, (int)$sportId);
        }

        Session::flash('success', 'Sekcje sportowe zapisane.');
        $this->redirect('onboarding/step3');
    }

    // ── Step 3: Branding ─────────────────────────────────────

    public function step3(): void
    {
        $clubId    = $this->currentClub();
        $branding  = (new ClubCustomizationModel())->findForClub($clubId);

        $this->render('onboarding/step3', [
            'title'       => 'Onboarding — Branding',
            'currentStep' => 3,
            'branding'    => $branding ?? ClubCustomizationModel::defaults(),
        ]);
    }

    public function saveStep3(): void
    {
        Csrf::verify();

        $clubId = $this->currentClub();
        $data   = [
            'primary_color' => trim($_POST['primary_color'] ?? '#0d6efd'),
            'accent_color'  => trim($_POST['accent_color'] ?? '#198754'),
            'motto'         => trim($_POST['motto'] ?? ''),
        ];

        // Handle logo upload
        if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = ROOT_PATH . '/public/uploads/logos';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $ext      = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowed  = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
            if (in_array($ext, $allowed, true)) {
                $filename = 'club_' . $clubId . '_' . time() . '.' . $ext;
                $dest     = $uploadDir . '/' . $filename;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                    $data['logo_path'] = 'uploads/logos/' . $filename;
                }
            }
        }

        (new ClubCustomizationModel())->upsert($clubId, $data);

        Session::flash('success', 'Branding zapisany.');
        $this->redirect('onboarding/step4');
    }

    // ── Step 4: Import members ───────────────────────────────

    public function step4(): void
    {
        $this->render('onboarding/step4', [
            'title'       => 'Onboarding — Zawodnicy',
            'currentStep' => 4,
        ]);
    }

    public function saveStep4(): void
    {
        Csrf::verify();

        $clubId = $this->currentClub();
        $mode   = $_POST['mode'] ?? 'manual';

        if ($mode === 'csv' && !empty($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            // CSV import
            $uploadDir = ROOT_PATH . '/storage/uploads/import';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $ext        = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
            $storedName = 'onboard_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $storedPath = $uploadDir . '/' . $storedName;

            if (move_uploaded_file($_FILES['csv_file']['tmp_name'], $storedPath)) {
                $delimiter = CsvImporter::detectDelimiter($storedPath);
                $parsed    = CsvImporter::parse($storedPath, $delimiter);

                if (!empty($parsed['headers']) && !empty($parsed['rows'])) {
                    $mapping = CsvImporter::mapColumns($parsed['headers']);
                    // Convert header-keyed mapping to index-keyed mapping
                    $indexMapping = [];
                    foreach ($parsed['headers'] as $idx => $header) {
                        if (isset($mapping[$header]) && $mapping[$header] !== null) {
                            $indexMapping[$idx] = $mapping[$header];
                        }
                    }

                    $result = CsvImporter::import($clubId, $parsed['rows'], $indexMapping, (int)Auth::id());
                    Session::flash('success', "Zaimportowano {$result['imported']} zawodników (pominięto: {$result['skipped']}).");
                } else {
                    Session::flash('error', 'Plik CSV jest pusty lub nieprawidłowy.');
                }

                @unlink($storedPath);
            } else {
                Session::flash('error', 'Nie udało się przesłać pliku.');
            }
        } else {
            // Manual single member
            $memberModel = new MemberModel();
            $memberNum   = $memberModel->nextMemberNumber($clubId);

            $data = [
                'club_id'       => $clubId,
                'first_name'    => trim($_POST['first_name'] ?? ''),
                'last_name'     => trim($_POST['last_name'] ?? ''),
                'email'         => trim($_POST['email'] ?? '') ?: null,
                'phone'         => trim($_POST['phone'] ?? '') ?: null,
                'join_date'     => trim($_POST['join_date'] ?? '') ?: date('Y-m-d'),
                'status'        => 'aktywny',
                'member_number' => $memberNum,
                'created_by'    => (int)Auth::id(),
            ];

            if ($data['first_name'] !== '' && $data['last_name'] !== '') {
                $memberModel->insert($data);
                Session::flash('success', 'Zawodnik dodany.');
            } else {
                Session::flash('error', 'Imię i nazwisko są wymagane.');
                $this->redirect('onboarding/step4');
            }
        }

        $this->redirect('onboarding/step5');
    }

    // ── Step 5: Summary ──────────────────────────────────────

    public function step5(): void
    {
        $clubId   = $this->currentClub();
        $club     = (new ClubModel())->findById($clubId);
        $stats    = (new ClubModel())->stats($clubId);
        $branding = (new ClubCustomizationModel())->findForClub($clubId) ?? ClubCustomizationModel::defaults();

        $this->render('onboarding/step5', [
            'title'       => 'Onboarding — Podsumowanie',
            'currentStep' => 5,
            'club'        => $club,
            'stats'       => $stats,
            'branding'    => $branding,
        ]);
    }

    public function complete(): void
    {
        Csrf::verify();
        Session::flash('success', 'Onboarding zakończony! Witamy w systemie.');
        $this->redirect('dashboard');
    }

    /** Pomiń onboarding — dokończ później. */
    public function skip(): void
    {
        Session::set('skip_onboarding', true);
        Session::flash('info', 'Onboarding pominięty. Możesz go dokończyć w dowolnym momencie.');
        $this->redirect('dashboard');
    }
}
