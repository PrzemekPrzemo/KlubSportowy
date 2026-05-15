<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Bracket\BracketGenerator;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\PdfHelper;
use App\Helpers\Session;
use App\Helpers\View;
use App\Models\TournamentBracketModel;
use App\Models\TournamentModel;
use App\Models\TournamentParticipantModel;

/**
 * Tournament bracket UI:
 *   GET  /tournaments/:id/bracket               wizualizacja
 *   GET  /tournaments/:id/bracket/generate      formularz konfiguracji
 *   POST /tournaments/:id/bracket/generate      generuj mecze
 *   GET  /tournaments/:id/bracket/seeds         UI manual seeding
 *   POST /tournaments/:id/bracket/seeds         zapis seedow
 *   GET  /tournaments/:id/bracket/pdf           eksport PDF
 *
 * Bezpieczenstwo:
 *   - requireLogin + requireClubContext
 *   - requireRole(['zarzad','trener','admin','sedzia']) na operacjach pisarskich i generacji
 *   - kazda akcja waliduje tournament.club_id == ClubContext::current()
 *   - CSRF na POST
 */
class TournamentBracketController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    /** GET /tournaments/:id/bracket */
    public function show(string $id): void
    {
        $tournamentId = (int)$id;
        $tournament = $this->loadOwnedTournament($tournamentId);

        $bracketModel = new TournamentBracketModel();
        $cfg = $bracketModel->configFor($tournamentId);

        $matches = (new TournamentModel())->brackets($tournamentId);

        // Group by (bracket_side, round)
        $grouped = ['upper' => [], 'lower' => [], 'final' => [], 'third_place' => []];
        foreach ($matches as $m) {
            $side = $m['bracket_side'] ?? 'upper';
            $grouped[$side][(int)$m['round']][] = $m;
        }

        // Order rounds by key
        foreach ($grouped as $side => $rounds) {
            ksort($grouped[$side]);
        }

        $bracketType = $cfg['bracket_type'] ?? ($tournament['format'] ?? 'single_elimination');

        $this->render('tournaments/bracket', [
            'title'       => 'Drabinka — ' . ($tournament['name'] ?? ''),
            'tournament'  => $tournament,
            'bracketCfg'  => $cfg,
            'bracketType' => $bracketType,
            'grouped'     => $grouped,
            'matches'     => $matches,
        ]);
    }

    /** GET /tournaments/:id/bracket/generate */
    public function generateForm(string $id): void
    {
        $this->requireRole(['zarzad','trener','admin','sedzia']);

        $tournamentId = (int)$id;
        $tournament = $this->loadOwnedTournament($tournamentId);

        $bracketModel = new TournamentBracketModel();
        $cfg = $bracketModel->configFor($tournamentId);

        // Has existing matches?
        $existing = (new TournamentModel())->brackets($tournamentId);

        $participants = (new TournamentParticipantModel())->listForTournament($tournamentId);

        $this->render('tournaments/bracket_form', [
            'title'        => 'Generuj drabinke',
            'tournament'   => $tournament,
            'bracketCfg'   => $cfg,
            'participants' => $participants,
            'hasMatches'   => !empty($existing),
            'byes'         => BracketGenerator::byes(count($participants)),
            'bracketSize'  => BracketGenerator::bracketSize(max(1, count($participants))),
        ]);
    }

    /** POST /tournaments/:id/bracket/generate */
    public function generate(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['zarzad','trener','admin','sedzia']);

        $tournamentId = (int)$id;
        $this->loadOwnedTournament($tournamentId);

        $cfgData = [
            'bracket_type'      => $_POST['bracket_type'] ?? 'single_elimination',
            'seed_method'       => $_POST['seed_method'] ?? 'random',
            'third_place_match' => !empty($_POST['third_place_match']) ? 1 : 0,
        ];
        $overwrite = !empty($_POST['overwrite']);

        $bracketModel = new TournamentBracketModel();

        try {
            $bracketModel->upsertConfig($tournamentId, $cfgData);
            $created = $bracketModel->generateMatches($tournamentId, $overwrite);
            Session::flash('success', "Drabinka wygenerowana. Utworzono {$created} mecz(y).");
        } catch (\Throwable $e) {
            Session::flash('error', 'Blad generowania: ' . $e->getMessage());
            $this->redirect('tournaments/' . $tournamentId . '/bracket/generate');
        }

        $this->redirect('tournaments/' . $tournamentId . '/bracket');
    }

    /** GET /tournaments/:id/bracket/seeds */
    public function seedsForm(string $id): void
    {
        $this->requireRole(['zarzad','trener','admin','sedzia']);

        $tournamentId = (int)$id;
        $tournament = $this->loadOwnedTournament($tournamentId);

        $bracketModel = new TournamentBracketModel();
        $cfg = $bracketModel->configFor($tournamentId);
        $seeds = $bracketModel->seedsFor($tournamentId);

        $participants = (new TournamentParticipantModel())->listForTournament($tournamentId);

        // Build participants list with current seed (if any)
        $seedsByPartId = [];
        foreach ($seeds as $s) {
            $seedsByPartId[(int)$s['participant_id']] = (int)$s['seed_number'];
        }

        $this->render('tournaments/bracket_seeds', [
            'title'         => 'Seedy drabinki',
            'tournament'    => $tournament,
            'bracketCfg'    => $cfg,
            'participants'  => $participants,
            'seedsByPartId' => $seedsByPartId,
            'isLocked'      => !empty($cfg['is_locked']),
        ]);
    }

    /** POST /tournaments/:id/bracket/seeds */
    public function saveSeeds(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['zarzad','trener','admin','sedzia']);

        $tournamentId = (int)$id;
        $this->loadOwnedTournament($tournamentId);

        $bracketModel = new TournamentBracketModel();
        $cfg = $bracketModel->configFor($tournamentId);
        if (!empty($cfg['is_locked'])) {
            Session::flash('error', 'Drabinka jest zablokowana — nie mozna edytowac seedow.');
            $this->redirect('tournaments/' . $tournamentId . '/bracket/seeds');
        }

        // Input: seeds[participant_id] = seed_number
        $input = $_POST['seeds'] ?? [];
        if (!is_array($input)) $input = [];

        $assignments = [];
        foreach ($input as $pid => $num) {
            $pid = (int)$pid;
            $num = (int)$num;
            if ($pid > 0 && $num > 0) {
                $assignments[] = ['participant_id' => $pid, 'seed_number' => $num];
            }
        }

        try {
            $bracketModel->saveSeeds($tournamentId, $assignments);
            Session::flash('success', 'Seedy zapisane.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Blad zapisu: ' . $e->getMessage());
        }

        $this->redirect('tournaments/' . $tournamentId . '/bracket/seeds');
    }

    /** GET /tournaments/:id/bracket/pdf */
    public function exportPdf(string $id): void
    {
        $tournamentId = (int)$id;
        $tournament = $this->loadOwnedTournament($tournamentId);

        $bracketModel = new TournamentBracketModel();
        $cfg = $bracketModel->configFor($tournamentId);
        $matches = (new TournamentModel())->brackets($tournamentId);

        $bracketType = $cfg['bracket_type'] ?? ($tournament['format'] ?? 'single_elimination');

        $html = $this->renderPdfHtml($tournament, $matches, $bracketType);
        $filename = 'drabinka_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$tournament['name']) . '.pdf';

        $orientation = (count($matches) >= 8) ? 'L' : 'P';
        PdfHelper::renderToPdf($html, $filename, $orientation);
    }

    /* ──────────────── helpers ──────────────── */

    /**
     * @return array<string,mixed>
     */
    private function loadOwnedTournament(int $tournamentId): array
    {
        $tournament = (new TournamentModel())->findById($tournamentId);
        $clubId = ClubContext::current();
        if (!$tournament || (int)($tournament['club_id'] ?? 0) !== (int)$clubId) {
            http_response_code(404);
            Session::flash('error', 'Turniej nie istnieje.');
            $this->redirect('tournaments');
        }
        return $tournament;
    }

    private function renderPdfHtml(array $tournament, array $matches, string $bracketType): string
    {
        $e = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

        $rows = [];
        $byRound = [];
        foreach ($matches as $m) {
            $byRound[(int)$m['round']][] = $m;
        }
        ksort($byRound);

        $rounds = '';
        foreach ($byRound as $r => $list) {
            $rounds .= '<div class="round"><h4>Runda ' . (int)$r . '</h4>';
            foreach ($list as $m) {
                $p1 = $m['player1_name'] ?? ($m['player1_id'] ? 'Gracz #'.$m['player1_id'] : 'BYE');
                $p2 = $m['player2_name'] ?? ($m['player2_id'] ? 'Gracz #'.$m['player2_id'] : 'BYE');
                $w  = $m['winner_id'] ? (int)$m['winner_id'] : 0;
                $p1cls = ($w && (int)$m['player1_id'] === $w) ? 'winner' : '';
                $p2cls = ($w && (int)$m['player2_id'] === $w) ? 'winner' : '';
                $s1 = $e($m['score1'] ?? '');
                $s2 = $e($m['score2'] ?? '');

                $rounds .= '<div class="match">'
                    . '<div class="player '.$p1cls.'"><span>'.$e($p1).'</span><span class="score">'.$s1.'</span></div>'
                    . '<div class="player '.$p2cls.'"><span>'.$e($p2).'</span><span class="score">'.$s2.'</span></div>'
                    . '</div>';
            }
            $rounds .= '</div>';
        }

        $title = $e($tournament['name'] ?? 'Turniej');
        $typeLabel = $e([
            'single_elimination' => 'Single Elimination',
            'double_elimination' => 'Double Elimination',
            'round_robin'        => 'Round Robin',
            'swiss'              => 'Swiss',
        ][$bracketType] ?? $bracketType);

        return <<<HTML
<!DOCTYPE html><html lang="pl"><head><meta charset="UTF-8"><style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; }
  h1 { font-size: 16pt; margin: 0 0 4px; }
  h4 { font-size: 11pt; margin: 8px 0 6px; color: #444; }
  .header { border-bottom: 2px solid #333; padding-bottom: 6px; margin-bottom: 10px; }
  .rounds { display: table; width: 100%; }
  .round { display: table-cell; vertical-align: top; padding: 0 8px; min-width: 160px; }
  .match { border: 1px solid #999; margin-bottom: 12px; padding: 4px; }
  .player { display: flex; justify-content: space-between; padding: 3px; border-bottom: 1px solid #eee; }
  .player:last-child { border-bottom: none; }
  .player.winner { font-weight: bold; background: #e6f7e6; }
  .score { color: #666; }
  .meta { font-size: 9pt; color: #666; }
</style></head><body>
<div class="header">
  <h1>{$title}</h1>
  <div class="meta">Format: {$typeLabel}</div>
</div>
<div class="rounds">{$rounds}</div>
</body></html>
HTML;
    }
}
