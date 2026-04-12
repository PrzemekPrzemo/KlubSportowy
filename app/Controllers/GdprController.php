<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\MemberConsentModel;
use App\Models\MemberModel;

class GdprController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $type = $_GET['type'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = (new MemberConsentModel())->listForClub($type ?: null, $page, 30);
        $this->render('gdpr/index', [
            'title'      => 'RODO — Zgody',
            'pagination' => $pagination,
            'typeFilter' => $type,
            'types'      => MemberConsentModel::TYPES(),
        ]);
    }

    public function memberConsents(string $memberId): void
    {
        $member = (new MemberModel())->findById((int)$memberId);
        if (!$member) { Session::flash('error', 'Nie znaleziono.'); $this->redirect('gdpr'); }
        $consents = (new MemberConsentModel())->forMember((int)$memberId);
        $this->render('gdpr/member_consents', [
            'title'    => 'Zgody: ' . $member['first_name'] . ' ' . $member['last_name'],
            'member'   => $member,
            'consents' => $consents,
            'types'    => MemberConsentModel::TYPES(),
        ]);
    }

    public function grantConsent(string $memberId): void
    {
        Csrf::verify();
        $type = $_POST['consent_type'] ?? '';
        if (!array_key_exists($type, MemberConsentModel::TYPES())) {
            Session::flash('error', 'Nieprawidłowy typ zgody.');
            $this->redirect('gdpr/member/' . $memberId);
        }
        (new MemberConsentModel())->grant($this->currentClub(), (int)$memberId, $type);
        Session::flash('success', 'Zgoda udzielona.');
        $this->redirect('gdpr/member/' . $memberId);
    }

    public function revokeConsent(string $memberId): void
    {
        Csrf::verify();
        $type = $_POST['consent_type'] ?? '';
        (new MemberConsentModel())->revoke($this->currentClub(), (int)$memberId, $type);
        Session::flash('success', 'Zgoda cofnięta.');
        $this->redirect('gdpr/member/' . $memberId);
    }

    /** Eksport danych zawodnika (Art. 20 RODO) — JSON download. */
    public function exportData(string $memberId): void
    {
        $member = (new MemberModel())->withSports((int)$memberId);
        if (!$member) { Session::flash('error', 'Nie znaleziono.'); $this->redirect('gdpr'); }

        $db = Database::pdo();
        $data = ['member' => $member];
        $data['consents']   = (new MemberConsentModel())->forMember((int)$memberId);
        $data['payments']   = $db->prepare("SELECT * FROM payments WHERE member_id = ? ORDER BY payment_date DESC")->execute([(int)$memberId]) ? $db->query("SELECT * FROM payments WHERE member_id = " . (int)$memberId)->fetchAll() : [];
        $data['medical']    = $db->query("SELECT * FROM member_medical_exams WHERE member_id = " . (int)$memberId)->fetchAll();
        $data['licenses']   = $db->query("SELECT * FROM member_licenses WHERE member_id = " . (int)$memberId)->fetchAll();
        $data['trainings']  = $db->query("SELECT ta.*, t.name FROM training_attendees ta JOIN trainings t ON t.id = ta.training_id WHERE ta.member_id = " . (int)$memberId)->fetchAll();
        $data['events']     = $db->query("SELECT ee.*, e.name FROM event_entries ee JOIN events e ON e.id = ee.event_id WHERE ee.member_id = " . (int)$memberId)->fetchAll();
        $data['exported_at'] = date('Y-m-d H:i:s');

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="gdpr_export_' . (int)$memberId . '_' . date('Ymd') . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Anonimizacja danych osobowych (prawo do bycia zapomnianym). */
    public function anonymize(string $memberId): void
    {
        Csrf::verify();
        $db = Database::pdo();
        $anonData = [
            'first_name'      => '***',
            'last_name'       => '***',
            'pesel'           => null,
            'email'           => null,
            'phone'           => null,
            'address_street'  => null,
            'address_city'    => null,
            'address_postal'  => null,
            'birth_date'      => null,
            'photo_path'      => null,
            'portal_password' => null,
            'notes'           => null,
            'anonymized_at'   => date('Y-m-d H:i:s'),
            'status'          => 'wykreslony',
        ];
        $set = implode(' = ?, ', array_map(fn($c) => "`{$c}`", array_keys($anonData))) . ' = ?';
        $stmt = $db->prepare("UPDATE members SET {$set} WHERE id = ? AND club_id = ?");
        $stmt->execute([...array_values($anonData), (int)$memberId, $this->currentClub()]);

        // Usuń zgody
        $db->prepare("DELETE FROM member_consents WHERE member_id = ? AND club_id = ?")
           ->execute([(int)$memberId, $this->currentClub()]);

        (new \App\Models\ActivityLogModel())->log('gdpr_anonymize', 'member', (int)$memberId);
        Session::flash('success', 'Dane zawodnika zostały zanonimizowane.');
        $this->redirect('members');
    }
}
