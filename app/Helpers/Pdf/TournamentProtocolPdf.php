<?php

declare(strict_types=1);

namespace App\Helpers\Pdf;

/**
 * Protokół turnieju w PDF — lista uczestników + wyników meczy/miejsc.
 *
 * Dane wejściowe:
 *   - tournament:    array (id, name, sport_key, date_start, status, ...)
 *   - participants:  array of rows (member_id, first_name, last_name, member_number, place?, time_ms?, score?)
 *   - matches:       array of rows (round, match_number, player1_name, player2_name, score1, score2, winner_id, ...)
 *   - sport:         array (key, name) — opcjonalnie
 *   - club_header:   HTML (z PdfHelper::getClubHeader)
 *   - system_footer: HTML (z PdfHelper::getSystemFooter)
 */
class TournamentProtocolPdf
{
    public static function generate(array $data): string
    {
        $e = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

        $tournament   = $data['tournament']    ?? [];
        $participants = $data['participants']  ?? [];
        $matches      = $data['matches']       ?? [];
        $sport        = $data['sport']         ?? [];
        $clubHeader   = (string)($data['club_header']   ?? '');
        $systemFooter = (string)($data['system_footer'] ?? '');

        $name       = $e($tournament['name']       ?? 'Turniej');
        $sportName  = $e($sport['name']            ?? ($tournament['sport_key'] ?? ''));
        $dateStart  = $e($tournament['date_start'] ?? '');
        $status     = $e($tournament['status']     ?? '');
        $tournId    = (int)($tournament['id']      ?? 0);

        // Sortuj uczestników wg place (jeśli ustawione), potem alfabetycznie.
        usort($participants, static function ($a, $b) {
            $pa = isset($a['place']) && $a['place'] !== null ? (int)$a['place'] : 9999;
            $pb = isset($b['place']) && $b['place'] !== null ? (int)$b['place'] : 9999;
            if ($pa !== $pb) return $pa <=> $pb;
            return strcmp((string)($a['last_name'] ?? ''), (string)($b['last_name'] ?? ''));
        });

        // Sekcja uczestników.
        $rowsHtml = '';
        foreach ($participants as $i => $p) {
            $place    = isset($p['place']) && $p['place'] !== null ? (int)$p['place'] : '';
            $fullName = $e(trim(($p['last_name'] ?? '') . ' ' . ($p['first_name'] ?? '')));
            $memNo    = $e($p['member_number'] ?? '');
            $score    = isset($p['score']) && $p['score'] !== null ? $e((string)$p['score']) : '';
            $timeMs   = isset($p['time_ms']) && $p['time_ms'] !== null
                        ? $e(self::fmtTime((int)$p['time_ms'])) : '';
            $idx      = $i + 1;

            $rowsHtml .= "<tr>
                <td style=\"text-align:center\">{$idx}</td>
                <td style=\"text-align:center\"><strong>{$place}</strong></td>
                <td>{$fullName}</td>
                <td style=\"text-align:center\">{$memNo}</td>
                <td style=\"text-align:right\">{$score}</td>
                <td style=\"text-align:right\">{$timeMs}</td>
            </tr>";
        }
        if ($rowsHtml === '') {
            $rowsHtml = '<tr><td colspan="6" style="text-align:center;color:#999">Brak uczestników</td></tr>';
        }

        // Sekcja meczy.
        $matchesHtml = '';
        if (!empty($matches)) {
            // grupowanie po rundzie
            $byRound = [];
            foreach ($matches as $m) {
                $byRound[(int)$m['round']][] = $m;
            }
            ksort($byRound);

            foreach ($byRound as $round => $matchesInRound) {
                $matchesHtml .= '<h4 style="margin-top:14px;margin-bottom:6px;color:#444">Runda ' . (int)$round . '</h4>';
                $matchesHtml .= '<table class="grid"><thead><tr>
                    <th style="width:40px">#</th>
                    <th>Zawodnik 1</th>
                    <th style="width:60px;text-align:center">Wynik</th>
                    <th>Zawodnik 2</th>
                    <th style="width:200px">Zwycięzca</th>
                </tr></thead><tbody>';
                foreach ($matchesInRound as $m) {
                    $no    = (int)($m['match_number'] ?? 0);
                    $p1    = $e($m['player1_name'] ?? '—');
                    $p2    = $e($m['player2_name'] ?? '—');
                    $s1    = $e((string)($m['score1'] ?? ''));
                    $s2    = $e((string)($m['score2'] ?? ''));
                    $winId = $m['winner_id'] ?? null;
                    $winner = '—';
                    if ($winId !== null) {
                        if ((int)$winId === (int)($m['player1_id'] ?? 0)) $winner = $p1;
                        elseif ((int)$winId === (int)($m['player2_id'] ?? 0)) $winner = $p2;
                    }
                    $matchesHtml .= "<tr>
                        <td style=\"text-align:center\">{$no}</td>
                        <td>{$p1}</td>
                        <td style=\"text-align:center\">{$s1} : {$s2}</td>
                        <td>{$p2}</td>
                        <td><strong>{$winner}</strong></td>
                    </tr>";
                }
                $matchesHtml .= '</tbody></table>';
            }
        }

        $genTime = $e(date('d.m.Y H:i'));

        return <<<HTML
<!DOCTYPE html>
<html lang="pl"><head><meta charset="UTF-8"><style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #222; }
  h1 { font-size: 20px; margin: 8px 0 4px 0; }
  h2 { font-size: 14px; margin: 18px 0 6px 0; color: #333; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
  h4 { font-size: 12px; margin: 14px 0 4px 0; color: #555; }
  .meta { font-size: 11px; color: #555; margin-bottom: 12px; }
  .meta strong { color: #222; }
  table.grid { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  table.grid th, table.grid td {
    border: 1px solid #ccc;
    padding: 4px 6px;
    vertical-align: middle;
  }
  table.grid th {
    background: #f5f5f5;
    text-align: left;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .badge {
    display: inline-block; padding: 2px 8px; border-radius: 4px;
    background: #e7f1ff; color: #084298; font-size: 10px;
  }
  .signature-row { margin-top: 50px; width: 100%; }
  .signature-row td {
    text-align: center; padding-top: 20px;
    border-top: 1px solid #555; font-size: 10px; color: #555;
    width: 33%;
  }
  .gen { font-size: 9px; color: #999; margin-top: 18px; text-align: right; }
</style></head><body>
{$clubHeader}

<h1>Protokół turnieju</h1>
<div class="meta">
  <strong>{$name}</strong>
  &nbsp;·&nbsp; Sport: {$sportName}
  &nbsp;·&nbsp; Data: {$dateStart}
  &nbsp;·&nbsp; Status: <span class="badge">{$status}</span>
  &nbsp;·&nbsp; ID: #{$tournId}
</div>

<h2>Klasyfikacja uczestników</h2>
<table class="grid">
  <thead>
    <tr>
      <th style="width:30px;text-align:center">#</th>
      <th style="width:60px;text-align:center">Miejsce</th>
      <th>Zawodnik</th>
      <th style="width:90px;text-align:center">Nr czł.</th>
      <th style="width:80px;text-align:right">Score</th>
      <th style="width:120px;text-align:right">Czas</th>
    </tr>
  </thead>
  <tbody>{$rowsHtml}</tbody>
</table>

HTML
        . ($matchesHtml !== '' ? '<h2>Mecze turnieju</h2>' . $matchesHtml : '')
        . <<<HTML

<table class="signature-row">
  <tr>
    <td>Sędzia główny</td>
    <td>Prezes / Trener</td>
    <td>Data i podpis</td>
  </tr>
</table>

<div class="gen">Protokół wygenerowany: {$genTime}</div>

{$systemFooter}
</body></html>
HTML;
    }

    private static function fmtTime(int $ms): string
    {
        if ($ms <= 0) return '';
        $mm = intdiv($ms, 60000);
        $rest = $ms % 60000;
        $ss = intdiv($rest, 1000);
        $SS = $rest % 1000;
        return sprintf('%02d:%02d.%03d', $mm, $ss, $SS);
    }
}
