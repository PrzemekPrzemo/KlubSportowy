<?php

declare(strict_types=1);

namespace App\Helpers\Pdf;

use App\Helpers\Translator;

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
    /**
     * @param string|null $locale 'pl'|'en'; null = current request locale.
     */
    public static function generate(array $data, ?string $locale = null): string
    {
        if ($locale !== null) {
            return Translator::withLocale($locale, fn() => self::doGenerate($data));
        }
        return self::doGenerate($data);
    }

    private static function doGenerate(array $data): string
    {
        $e = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

        $tournament   = $data['tournament']    ?? [];
        $participants = $data['participants']  ?? [];
        $matches      = $data['matches']       ?? [];
        $sport        = $data['sport']         ?? [];
        $clubHeader   = (string)($data['club_header']   ?? '');
        $systemFooter = (string)($data['system_footer'] ?? '');

        $name       = $e($tournament['name']       ?? __('pdf.tournament_protocol.default_name'));
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
            $rowsHtml = '<tr><td colspan="6" style="text-align:center;color:#999">' . $e(__('pdf.tournament_protocol.no_participants')) . '</td></tr>';
        }

        // i18n labels (used in matches block too)
        $roundLabel    = $e(__('pdf.tournament_protocol.round'));
        $colMatchNo    = $e(__('pdf.tournament_protocol.col_match_no'));
        $colPlayer1    = $e(__('pdf.tournament_protocol.col_player1'));
        $colPlayer2    = $e(__('pdf.tournament_protocol.col_player2'));
        $colResult     = $e(__('pdf.tournament_protocol.col_result'));
        $colWinner     = $e(__('pdf.tournament_protocol.col_winner'));

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
                $matchesHtml .= '<h4 style="margin-top:14px;margin-bottom:6px;color:#444">' . $roundLabel . ' ' . (int)$round . '</h4>';
                $matchesHtml .= '<table class="grid"><thead><tr>
                    <th style="width:40px">' . $colMatchNo . '</th>
                    <th>' . $colPlayer1 . '</th>
                    <th style="width:60px;text-align:center">' . $colResult . '</th>
                    <th>' . $colPlayer2 . '</th>
                    <th style="width:200px">' . $colWinner . '</th>
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

        // i18n labels (header / classification)
        $title         = $e(__('pdf.tournament_protocol.title'));
        $sportLbl      = $e(__('pdf.tournament_protocol.sport'));
        $dateLbl       = $e(__('pdf.tournament_protocol.date'));
        $statusLbl     = $e(__('pdf.tournament_protocol.status'));
        $idLbl         = $e(__('pdf.tournament_protocol.id'));
        $classificationLbl = $e(__('pdf.tournament_protocol.classification'));
        $colIndex      = $e(__('pdf.tournament_protocol.col_index'));
        $colPlace      = $e(__('pdf.tournament_protocol.col_place'));
        $colPlayer     = $e(__('pdf.tournament_protocol.col_player'));
        $colMemberNo   = $e(__('pdf.tournament_protocol.col_member_no'));
        $colScore      = $e(__('pdf.tournament_protocol.col_score'));
        $colTime       = $e(__('pdf.tournament_protocol.col_time'));
        $matchesLbl    = $e(__('pdf.tournament_protocol.matches'));
        $sigJudge      = $e(__('pdf.tournament_protocol.sig_judge'));
        $sigPresident  = $e(__('pdf.tournament_protocol.sig_president'));
        $sigDate       = $e(__('pdf.tournament_protocol.sig_date'));
        $generatedLbl  = $e(__('pdf.tournament_protocol.generated_at'));
        $htmlLang      = $e(Translator::getLocale());

        return <<<HTML
<!DOCTYPE html>
<html lang="{$htmlLang}"><head><meta charset="UTF-8"><style>
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

<h1>{$title}</h1>
<div class="meta">
  <strong>{$name}</strong>
  &nbsp;·&nbsp; {$sportLbl}: {$sportName}
  &nbsp;·&nbsp; {$dateLbl}: {$dateStart}
  &nbsp;·&nbsp; {$statusLbl}: <span class="badge">{$status}</span>
  &nbsp;·&nbsp; {$idLbl}: #{$tournId}
</div>

<h2>{$classificationLbl}</h2>
<table class="grid">
  <thead>
    <tr>
      <th style="width:30px;text-align:center">{$colIndex}</th>
      <th style="width:60px;text-align:center">{$colPlace}</th>
      <th>{$colPlayer}</th>
      <th style="width:90px;text-align:center">{$colMemberNo}</th>
      <th style="width:80px;text-align:right">{$colScore}</th>
      <th style="width:120px;text-align:right">{$colTime}</th>
    </tr>
  </thead>
  <tbody>{$rowsHtml}</tbody>
</table>

HTML
        . ($matchesHtml !== '' ? '<h2>' . $matchesLbl . '</h2>' . $matchesHtml : '')
        . <<<HTML

<table class="signature-row">
  <tr>
    <td>{$sigJudge}</td>
    <td>{$sigPresident}</td>
    <td>{$sigDate}</td>
  </tr>
</table>

<div class="gen">{$generatedLbl}: {$genTime}</div>

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
