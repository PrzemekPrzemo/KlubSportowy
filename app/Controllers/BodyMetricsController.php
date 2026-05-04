<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\BodyMetricsModel;
use App\Models\MemberModel;

class BodyMetricsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSensitiveAccess();
    }

    public function member(string $memberId): void
    {
        $mid    = (int)$memberId;
        $member = (new MemberModel())->findById($mid);
        if (!$member) {
            Session::flash('error', 'Nie znaleziono zawodnika.');
            $this->redirect('members');
        }

        \App\Models\SensitiveAccessLogModel::log('body_metrics', 'view', $mid);

        $model = new BodyMetricsModel();
        $this->render('members/body_metrics', [
            'title'   => 'Pomiary ciała — ' . $member['last_name'] . ' ' . $member['first_name'],
            'member'  => $member,
            'metrics' => $model->listForMember($mid, 100),
            'latest'  => $model->latestForMember($mid),
        ]);
    }

    public function store(string $memberId): void
    {
        Csrf::verify();
        $mid = (int)$memberId;

        $weight = !empty($_POST['weight_kg'])    ? (float)$_POST['weight_kg']    : null;
        $height = !empty($_POST['height_cm'])    ? (int)$_POST['height_cm']      : null;
        $bodyFat= !empty($_POST['body_fat_pct']) ? (float)$_POST['body_fat_pct'] : null;
        $hr     = !empty($_POST['resting_hr'])   ? (int)$_POST['resting_hr']     : null;
        $wing   = !empty($_POST['wingspan_cm'])  ? (int)$_POST['wingspan_cm']    : null;

        if ($weight === null && $height === null && $bodyFat === null && $hr === null && $wing === null) {
            Session::flash('error', 'Podaj co najmniej jeden pomiar.');
            $this->redirect('members/' . $mid . '/metrics');
        }

        (new BodyMetricsModel())->insert([
            'member_id'    => $mid,
            'measured_at'  => trim($_POST['measured_at'] ?? '') ?: date('Y-m-d'),
            'weight_kg'    => $weight,
            'height_cm'    => $height,
            'body_fat_pct' => $bodyFat,
            'resting_hr'   => $hr,
            'wingspan_cm'  => $wing,
            'measured_by'  => trim($_POST['measured_by'] ?? '') ?: null,
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Pomiar zapisany.');
        $this->redirect('members/' . $mid . '/metrics');
    }

    public function delete(string $memberId, string $id): void
    {
        Csrf::verify();
        (new BodyMetricsModel())->delete((int)$id);
        Session::flash('success', 'Usunięto pomiar.');
        $this->redirect('members/' . (int)$memberId . '/metrics');
    }
}
