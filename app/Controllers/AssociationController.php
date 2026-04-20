<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\AssociationMeetingModel;
use App\Models\AssociationVoteModel;
use App\Models\MemberModel;

class AssociationController extends BaseController
{
    private AssociationMeetingModel $meetings;
    private AssociationVoteModel    $votes;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->meetings = new AssociationMeetingModel();
        $this->votes    = new AssociationVoteModel();
    }

    public function meetings(): void
    {
        $type = $_GET['type'] ?? null;
        $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
        $page = max(1, (int)($_GET['page'] ?? 1));

        $result  = $this->meetings->listForClub($type, $year, $page);
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $this->render('association/meetings/index', [
            'title'        => 'Posiedzenia — Stowarzyszenie',
            'meetings'     => $result['data'] ?? $result,
            'pagination'   => $result['pagination'] ?? null,
            'meetingTypes' => AssociationMeetingModel::$MEETING_TYPES,
            'filterType'   => $type,
            'filterYear'   => $year,
            'members'      => $members,
        ]);
    }

    public function createMeeting(): void
    {
        Csrf::verify();
        $type = array_key_exists($_POST['meeting_type'] ?? '', AssociationMeetingModel::$MEETING_TYPES)
            ? $_POST['meeting_type'] : 'zarząd';
        $date = trim($_POST['meeting_date'] ?? '') ?: date('Y-m-d');

        $id = $this->meetings->createMeeting([
            'meeting_type'  => $type,
            'meeting_date'  => $date,
            'location'      => trim($_POST['location'] ?? '') ?: null,
            'quorum_reached'=> !empty($_POST['quorum_reached']) ? 1 : 0,
            'agenda'        => trim($_POST['agenda'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Posiedzenie zostało dodane.');
        $this->redirect('association/meetings/' . $id);
    }

    public function showMeeting(string $id): void
    {
        $meetingId = (int)$id;
        $meeting   = $this->meetings->findWithVotes($meetingId);
        if (!$meeting) { Session::flash('error', 'Nie znaleziono posiedzenia.'); $this->redirect('association/meetings'); }

        $votes = $this->votes->listForMeeting($meetingId);
        $nextNum = $this->votes->nextResolutionNumber($meetingId, (int)date('Y', strtotime($meeting['meeting_date'])));

        $this->render('association/meetings/show', [
            'title'     => 'Posiedzenie — ' . AssociationMeetingModel::$MEETING_TYPES[$meeting['meeting_type']],
            'meeting'   => $meeting,
            'votes'     => $votes,
            'nextNum'   => $nextNum,
            'voteResults' => AssociationVoteModel::$RESULTS,
        ]);
    }

    public function addVote(string $meetingId): void
    {
        Csrf::verify();
        $mid = (int)$meetingId;
        $meeting = $this->meetings->findWithVotes($mid);
        if (!$meeting) { $this->redirect('association/meetings'); }

        $title = trim($_POST['title'] ?? '');
        if (!$title) { Session::flash('error', 'Podaj tytuł uchwały.'); $this->redirect('association/meetings/' . $mid); }

        $year = (int)date('Y', strtotime($meeting['meeting_date']));
        $num  = trim($_POST['resolution_number'] ?? '') ?: $this->votes->nextResolutionNumber($mid, $year);

        $result = array_key_exists($_POST['result'] ?? '', AssociationVoteModel::$RESULTS)
            ? $_POST['result'] : 'przyjęta';

        $this->votes->addVote([
            'meeting_id'        => $mid,
            'resolution_number' => $num,
            'title'             => $title,
            'content'           => trim($_POST['content'] ?? '') ?: null,
            'vote_yes'          => max(0, (int)($_POST['vote_yes'] ?? 0)),
            'vote_no'           => max(0, (int)($_POST['vote_no'] ?? 0)),
            'vote_abstain'      => max(0, (int)($_POST['vote_abstain'] ?? 0)),
            'result'            => $result,
        ]);
        Session::flash('success', 'Uchwała dodana.');
        $this->redirect('association/meetings/' . $mid);
    }

    public function board(): void
    {
        $db      = \App\Helpers\Database::pdo();
        $stmt    = $db->prepare(
            "SELECT bm.*, m.first_name, m.last_name, m.member_number
             FROM association_board_members bm
             JOIN members m ON m.id = bm.member_id
             WHERE bm.club_id = ?
             ORDER BY bm.active DESC, bm.role"
        );
        $stmt->execute([\App\Helpers\ClubContext::getClubId()]);
        $boardMembers = $stmt->fetchAll();

        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $this->render('association/board/index', [
            'title'       => 'Skład Zarządu — Stowarzyszenie',
            'boardMembers'=> $boardMembers,
            'members'     => $members,
            'boardRoles'  => [
                'prezes'             => 'Prezes',
                'wiceprezes'         => 'Wiceprezes',
                'sekretarz'          => 'Sekretarz',
                'skarbnik'           => 'Skarbnik',
                'członek_zarządu'    => 'Członek Zarządu',
                'komisja_rewizyjna'  => 'Komisja Rewizyjna',
                'przewodniczący_kr'  => 'Przewodniczący KR',
            ],
        ]);
    }

    public function updateBoard(): void
    {
        Csrf::verify();
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $memberId = (int)($_POST['member_id'] ?? 0);
            $role     = $_POST['role'] ?? '';
            if (!$memberId || !$role) { Session::flash('error', 'Uzupełnij dane.'); $this->redirect('association/board'); }

            $db = \App\Helpers\Database::pdo();
            $db->prepare(
                "INSERT INTO association_board_members (club_id, member_id, role, term_start, term_end, active)
                 VALUES (?, ?, ?, ?, ?, 1)"
            )->execute([
                \App\Helpers\ClubContext::getClubId(),
                $memberId,
                $role,
                trim($_POST['term_start'] ?? '') ?: date('Y-m-d'),
                trim($_POST['term_end'] ?? '') ?: null,
            ]);
            Session::flash('success', 'Dodano do zarządu.');
        } elseif ($action === 'deactivate') {
            $bmId = (int)($_POST['board_member_id'] ?? 0);
            \App\Helpers\Database::pdo()->prepare(
                "UPDATE association_board_members SET active = 0 WHERE id = ? AND club_id = ?"
            )->execute([$bmId, \App\Helpers\ClubContext::getClubId()]);
            Session::flash('success', 'Zakończono kadencję.');
        }

        $this->redirect('association/board');
    }
}
