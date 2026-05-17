<?php

namespace App\Sports\Boxing\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Boxing\Models\BoxingRecordModel;
use App\Sports\Boxing\Models\BoxingResultModel;
use App\Sports\Boxing\Models\BoxingWeightHistoryModel;

/**
 * Admin/coach widok kartoteki bokserskiej:
 *   - GET  /boxing/record/:memberId               - kartoteka W-L-D + licencja + waga
 *   - POST /boxing/record/:memberId/update        - edycja statystyk
 *   - GET  /boxing/record/:memberId/weight        - historia wazenia
 *   - POST /boxing/record/:memberId/weight/add    - dodaj pomiar wagi
 */
class SportBoxingController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function memberRecord(string $memberId): void
    {
        $mid    = (int)$memberId;
        $record = new BoxingRecordModel();

        if (!$record->memberBelongsToClub($mid)) {
            Session::flash('error', 'Zawodnik nie nalezy do biezacego klubu.');
            $this->redirect('boxing/results');
        }

        $member  = (new MemberModel())->findById($mid);
        $row     = $record->forMember($mid);
        $history = (new BoxingWeightHistoryModel())->listForMember($mid);
        $results = (new BoxingResultModel())->listForClub($mid);

        $this->render('boxing/record/index', [
            'title'         => 'Kartoteka bokserska',
            'member'        => $member,
            'record'        => $row,
            'history'       => $history,
            'results'       => $results,
            'licenseLevels' => BoxingRecordModel::$LICENSE_LEVELS,
            'stances'       => BoxingRecordModel::$STANCES,
            'weightClasses' => BoxingResultModel::$WEIGHT_CLASSES,
        ]);
    }

    public function updateRecord(string $memberId): void
    {
        Csrf::verify();
        $mid    = (int)$memberId;
        $record = new BoxingRecordModel();

        if (!$record->memberBelongsToClub($mid)) {
            Session::flash('error', 'Zawodnik nie nalezy do biezacego klubu.');
            $this->redirect('boxing/results');
        }

        $licenseLevel = $_POST['license_level'] ?? 'junior';
        if (!array_key_exists($licenseLevel, BoxingRecordModel::$LICENSE_LEVELS)) {
            $licenseLevel = 'junior';
        }
        $stance = $_POST['stance'] ?? 'orthodox';
        if (!array_key_exists($stance, BoxingRecordModel::$STANCES)) {
            $stance = 'orthodox';
        }

        $weightKg = $_POST['current_weight_kg'] ?? '';
        $weightKg = ($weightKg !== '' && is_numeric($weightKg)) ? (float)$weightKg : null;

        $record->upsert($mid, [
            'wins'                 => max(0, (int)($_POST['wins']     ?? 0)),
            'losses'               => max(0, (int)($_POST['losses']   ?? 0)),
            'draws'                => max(0, (int)($_POST['draws']    ?? 0)),
            'ko_wins'              => max(0, (int)($_POST['ko_wins']  ?? 0)),
            'tko_wins'             => max(0, (int)($_POST['tko_wins'] ?? 0)),
            'license_level'        => $licenseLevel,
            'license_number'       => trim((string)($_POST['license_number'] ?? '')) ?: null,
            'license_expires'      => trim((string)($_POST['license_expires'] ?? '')) ?: null,
            'current_weight_class' => trim((string)($_POST['current_weight_class'] ?? '')) ?: null,
            'current_weight_kg'    => $weightKg,
            'reach_cm'             => !empty($_POST['reach_cm']) ? (int)$_POST['reach_cm'] : null,
            'stance'               => $stance,
        ]);

        Session::flash('success', 'Kartoteka zapisana.');
        $this->redirect('boxing/record/' . $mid);
    }

    public function weightHistory(string $memberId): void
    {
        $mid     = (int)$memberId;
        $history = new BoxingWeightHistoryModel();
        $record  = new BoxingRecordModel();

        if (!$record->memberBelongsToClub($mid)) {
            Session::flash('error', 'Zawodnik nie nalezy do biezacego klubu.');
            $this->redirect('boxing/results');
        }

        $member = (new MemberModel())->findById($mid);
        $rows   = $history->listForMember($mid);

        $this->render('boxing/record/weight', [
            'title'         => 'Historia wazenia',
            'member'        => $member,
            'history'       => $rows,
            'weightClasses' => BoxingResultModel::$WEIGHT_CLASSES,
        ]);
    }

    public function addWeightEntry(string $memberId): void
    {
        Csrf::verify();
        $mid    = (int)$memberId;
        $record = new BoxingRecordModel();

        if (!$record->memberBelongsToClub($mid)) {
            Session::flash('error', 'Zawodnik nie nalezy do biezacego klubu.');
            $this->redirect('boxing/results');
        }

        $weight = $_POST['weight_kg'] ?? '';
        if ($weight === '' || !is_numeric($weight)) {
            Session::flash('error', 'Podaj wage (kg).');
            $this->redirect('boxing/record/' . $mid . '/weight');
        }

        $weightClass = trim((string)($_POST['weight_class'] ?? '')) ?: null;
        if ($weightClass !== null && !array_key_exists($weightClass, BoxingResultModel::$WEIGHT_CLASSES)) {
            $weightClass = null;
        }

        $measured = trim((string)($_POST['measured_at'] ?? '')) ?: date('Y-m-d');

        (new BoxingWeightHistoryModel())->add(
            $mid,
            (float)$weight,
            $weightClass,
            $measured,
            trim((string)($_POST['notes'] ?? '')) ?: null
        );

        Session::flash('success', 'Pomiar dodany.');
        $this->redirect('boxing/record/' . $mid . '/weight');
    }
}
