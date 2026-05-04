<?php

namespace App\Sports\Chess\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Chess\Models\ChessRatingModel;

class RatingsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $ratings = (new ChessRatingModel())->latestRatings();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        // Group ratings by member for display
        $byMember = [];
        foreach ($ratings as $row) {
            $mid = $row['member_id'];
            if (!isset($byMember[$mid])) {
                $byMember[$mid] = [
                    'member_id'     => $mid,
                    'first_name'    => $row['first_name'],
                    'last_name'     => $row['last_name'],
                    'member_number' => $row['member_number'],
                    'ratings'       => [],
                ];
            }
            $byMember[$mid]['ratings'][$row['rating_type']] = $row;
        }

        $this->render('chess/ratings/index', [
            'title'     => 'Rankingi ELO — Szachy',
            'byMember'  => $byMember,
            'members'   => $members,
            'ratingTypes' => ChessRatingModel::$TYPES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId   = (int)($_POST['member_id'] ?? 0);
        $ratingType = $_POST['rating_type'] ?? '';
        $rating     = (int)($_POST['rating'] ?? 0);
        $ratingDate = trim($_POST['rating_date'] ?? '') ?: date('Y-m-d');

        if ($memberId <= 0 || !array_key_exists($ratingType, ChessRatingModel::$TYPES) || $rating <= 0) {
            Session::flash('error', 'Nieprawidłowe dane.');
            $this->redirect('chess/ratings');
        }

        (new ChessRatingModel())->insert([
            'member_id'   => $memberId,
            'rating_type' => $ratingType,
            'rating'      => $rating,
            'rating_date' => $ratingDate,
            'notes'       => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Ocena dodana.');
        $this->redirect('chess/ratings');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new ChessRatingModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('chess/ratings');
    }
}
