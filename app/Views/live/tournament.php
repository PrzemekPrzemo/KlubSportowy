<?php
use App\Helpers\View;

/**
 * Publiczna strona LIVE turnieju — BEZ logowania.
 *
 * Layout: 'none' (czyste body) — zeby zminimalizowac zaleznosci i wagae HTML.
 * Branding klubu: $branding (primary_color, navbar_bg, accent_color, motto).
 * Mobile-first; bracket scrollowalny w prawo na malych ekranach.
 *
 * Zero PII: imie + inicjal nazwiska (chyba ze admin opt-in pelnych nazwisk).
 * Wszystkie pola czlonkow sa juz przemyte przez controller (formatName w SSE).
 */

$primaryColor = htmlspecialchars((string)($branding['primary_color'] ?? '#0d6efd'), ENT_QUOTES);
$navbarBg     = htmlspecialchars((string)($branding['navbar_bg']     ?? '#212529'), ENT_QUOTES);
$accentColor  = htmlspecialchars((string)($branding['accent_color']  ?? '#198754'), ENT_QUOTES);
$logoPath     = $branding['logo_path'] ?? null;
$motto        = $branding['motto'] ?? null;

$showFullNames = (bool)($showFullNames ?? false);

/**
 * Helper: imie + inicjal nazwiska (lub pelne nazwisko gdy opt-in).
 * Brak imienia/nazwiska -> "BYE" (NULL player = bye w drabince).
 */
$formatName = static function (?string $first, ?string $last) use ($showFullNames): string {
    if (!$first && !$last) return 'BYE';
    $first = trim((string)$first);
    $last  = trim((string)$last);
    if ($showFullNames) return View::e(trim($first . ' ' . $last));
    $initial = $last !== '' ? mb_substr($last, 0, 1, 'UTF-8') . '.' : '';
    return View::e(trim($first . ' ' . $initial));
};

$statusMap = [
    'draft'    => ['label' => 'Szkic',      'color' => '#6c757d'],
    'active'   => ['label' => 'LIVE',       'color' => '#dc3545'],
    'finished' => ['label' => 'Zakonczony', 'color' => '#198754'],
];
$status = $statusMap[$tournament['status']] ?? ['label' => 'LIVE', 'color' => '#dc3545'];
$isLive = $tournament['status'] === 'active';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="robots" content="index,follow">
<meta name="description" content="Live wyniki turnieju <?= View::e($tournament['name']) ?> — <?= View::e($club['name'] ?? '') ?>. Powered by ClubDesk.">
<title>LIVE: <?= View::e($tournament['name']) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    :root {
        --cd-primary: <?= $primaryColor ?>;
        --cd-navbar:  <?= $navbarBg ?>;
        --cd-accent:  <?= $accentColor ?>;
    }
    body { background: #f1f3f5; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 15px; color:#212529; }
    .live-header { background: var(--cd-navbar); color: #fff; padding: 1rem 0; }
    .live-header .club-logo { height: 48px; max-width: 120px; object-fit: contain; background:#fff; border-radius:6px; padding:4px; }
    .live-badge {
        display:inline-flex; align-items:center; gap:.4rem;
        background: <?= $status['color'] ?>; color:#fff; padding:.2rem .7rem;
        border-radius: 999px; font-weight:700; font-size:.85rem; text-transform: uppercase;
    }
    <?php if ($isLive): ?>
    .live-pulse { width:8px; height:8px; border-radius:50%; background:#fff; animation: pulse 1.2s infinite; }
    @keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:.3;} }
    <?php endif; ?>
    h1.tour-title { font-size: 1.4rem; font-weight:700; margin: 0; line-height:1.2; }
    .tour-meta { font-size:.85rem; opacity:.85; }

    .section-tabs { background: #fff; border-bottom: 1px solid #dee2e6; position: sticky; top:0; z-index:50; }
    .section-tabs a {
        display: inline-block; padding: .85rem 1rem; color: #495057; text-decoration: none;
        border-bottom: 3px solid transparent; font-weight:500; font-size: .9rem;
    }
    .section-tabs a.active { color: var(--cd-primary); border-bottom-color: var(--cd-primary); }

    .match-card {
        background:#fff; border-radius:8px; box-shadow: 0 1px 2px rgba(0,0,0,.05);
        padding: .75rem .9rem; margin-bottom: .6rem;
    }
    .match-card .row-player {
        display:flex; justify-content:space-between; align-items:center; padding:.25rem 0;
    }
    .match-card .row-player.winner { font-weight:700; color: var(--cd-accent); }
    .match-card .row-player.loser  { color:#868e96; text-decoration: line-through; }
    .match-card .score-pill {
        background:#f8f9fa; border:1px solid #dee2e6; border-radius:4px;
        padding:.05rem .45rem; font-family: monospace; font-weight:700; min-width:32px; text-align:center;
    }
    .match-card .meta { font-size:.7rem; color:#adb5bd; text-transform:uppercase; letter-spacing:.04em; }
    .match-card.winner-decided { border-left: 3px solid var(--cd-accent); }

    /* Bracket: kolumny per runda, scroll w poziomie */
    .bracket-wrap { overflow-x: auto; padding-bottom: 1rem; }
    .bracket-rounds { display:flex; gap: 1rem; min-width: max-content; }
    .bracket-round { min-width: 240px; flex: 0 0 auto; }
    .bracket-round h6 { font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:#6c757d; margin-bottom:.6rem; }

    /* Standings */
    table.standings { background:#fff; border-radius:8px; overflow:hidden; }
    table.standings th { background:#f8f9fa; font-size:.8rem; text-transform:uppercase; color:#6c757d; }

    footer.cd-footer { background: var(--cd-navbar); color:#adb5bd; padding:1.5rem 0; font-size:.85rem; text-align:center; }
    footer.cd-footer a { color:#fff; font-weight:500; text-decoration:none; }
    footer.cd-footer a:hover { text-decoration:underline; }

    .qr-thumb { width: 90px; height: 90px; background:#fff; padding:6px; border-radius:6px; display:inline-block; }

    @media (max-width: 600px) {
        .live-header { padding: .8rem 0; }
        h1.tour-title { font-size: 1.15rem; }
        .bracket-round { min-width: 200px; }
    }
</style>
</head>
<body>

<header class="live-header">
    <div class="container">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <?php if ($logoPath): ?>
                <img class="club-logo" src="<?= View::e($logoPath) ?>" alt="<?= View::e($club['name'] ?? '') ?>">
            <?php endif; ?>
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="live-badge">
                        <?php if ($isLive): ?><span class="live-pulse"></span><?php endif; ?>
                        <?= View::e($status['label']) ?>
                    </span>
                    <small class="tour-meta">
                        <?= View::e($club['name'] ?? '') ?>
                        <?php if (!empty($club['city'])): ?>
                            &middot; <?= View::e($club['city']) ?>
                        <?php endif; ?>
                    </small>
                </div>
                <h1 class="tour-title"><?= View::e($tournament['name']) ?></h1>
                <div class="tour-meta">
                    <i class="bi bi-calendar3"></i> <?= View::e($tournament['date_start']) ?>
                    <?php if (!empty($tournament['sport_key'])): ?>
                        &middot; <i class="bi bi-trophy"></i> <?= View::e($tournament['sport_key']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-end">
                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#qrModal">
                    <i class="bi bi-qr-code"></i> QR
                </button>
                <button class="btn btn-sm btn-light" id="copyLinkBtn">
                    <i class="bi bi-link-45deg"></i> Link
                </button>
            </div>
        </div>
    </div>
</header>

<nav class="section-tabs">
    <div class="container">
        <a href="#bracket" class="active" data-section="bracket"><i class="bi bi-diagram-3"></i> Drabinka</a>
        <a href="#feed"    data-section="feed"><i class="bi bi-list-ul"></i> Wyniki</a>
        <a href="#standings" data-section="standings"><i class="bi bi-bar-chart"></i> Klasyfikacja</a>
        <a href="#upcoming" data-section="upcoming"><i class="bi bi-clock"></i> Nadchodzace</a>
    </div>
</nav>

<main class="container py-3">

    <?php if (!empty($motto)): ?>
        <p class="text-muted fst-italic text-center mb-3"><?= View::e($motto) ?></p>
    <?php endif; ?>

    <!-- Bracket -->
    <section id="bracket" class="mb-4">
        <h5 class="mb-2"><i class="bi bi-diagram-3"></i> Drabinka turniejowa</h5>
        <?php if (empty($byRound)): ?>
            <div class="alert alert-light border">Drabinka nie jest jeszcze wygenerowana.</div>
        <?php else: ?>
            <div class="bracket-wrap">
                <div class="bracket-rounds">
                    <?php foreach ($byRound as $round => $rmatches): ?>
                        <div class="bracket-round">
                            <h6>Runda <?= (int)$round ?></h6>
                            <?php foreach ($rmatches as $m):
                                $hasResult = $m['winner_id'] !== null;
                                $w         = $hasResult ? (int)$m['winner_id'] : 0;
                                $p1Win     = $hasResult && $w === (int)($m['player1_id'] ?? 0);
                                $p2Win     = $hasResult && $w === (int)($m['player2_id'] ?? 0);
                                $isBye     = empty($m['player1_id']) || empty($m['player2_id']);
                            ?>
                                <div class="match-card <?= $hasResult ? 'winner-decided' : '' ?>" data-match-id="<?= (int)$m['id'] ?>">
                                    <div class="meta">Mecz #<?= (int)$m['match_number'] ?> <?= $isBye && !$hasResult ? '&middot; BYE' : '' ?></div>
                                    <div class="row-player <?= $p1Win ? 'winner' : ($hasResult && !$p1Win && !empty($m['player1_id']) ? 'loser' : '') ?>">
                                        <span class="player1"><?= $formatName($m['p1_first'] ?? null, $m['p1_last'] ?? null) ?></span>
                                        <?php if ($m['score1'] !== null && $m['score1'] !== ''): ?>
                                            <span class="score-pill score1"><?= View::e($m['score1']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="row-player <?= $p2Win ? 'winner' : ($hasResult && !$p2Win && !empty($m['player2_id']) ? 'loser' : '') ?>">
                                        <span class="player2"><?= $formatName($m['p2_first'] ?? null, $m['p2_last'] ?? null) ?></span>
                                        <?php if ($m['score2'] !== null && $m['score2'] !== ''): ?>
                                            <span class="score-pill score2"><?= View::e($m['score2']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- Recent results feed -->
    <section id="feed" class="mb-4">
        <h5 class="mb-2"><i class="bi bi-list-ul"></i> Ostatnie wyniki</h5>
        <div id="recentFeed">
        <?php if (empty($recent)): ?>
            <div class="alert alert-light border">Brak rozegranych meczy.</div>
        <?php else: foreach ($recent as $m):
            $w = (int)($m['winner_id'] ?? 0);
            $p1Win = $w === (int)($m['player1_id'] ?? 0);
        ?>
            <div class="match-card winner-decided">
                <div class="meta">R<?= (int)$m['round'] ?> &middot; #<?= (int)$m['match_number'] ?></div>
                <div class="row-player <?= $p1Win ? 'winner' : 'loser' ?>">
                    <span><?= $formatName($m['p1_first'] ?? null, $m['p1_last'] ?? null) ?></span>
                    <span class="score-pill"><?= View::e($m['score1'] ?? '') ?></span>
                </div>
                <div class="row-player <?= !$p1Win ? 'winner' : 'loser' ?>">
                    <span><?= $formatName($m['p2_first'] ?? null, $m['p2_last'] ?? null) ?></span>
                    <span class="score-pill"><?= View::e($m['score2'] ?? '') ?></span>
                </div>
            </div>
        <?php endforeach; endif; ?>
        </div>
    </section>

    <!-- Standings -->
    <section id="standings" class="mb-4">
        <h5 class="mb-2"><i class="bi bi-bar-chart"></i> Klasyfikacja</h5>
        <?php if (empty($standings)): ?>
            <div class="alert alert-light border">Brak danych.</div>
        <?php else: ?>
            <div class="table-responsive">
            <table class="table table-sm standings mb-0">
                <thead><tr><th>#</th><th>Zawodnik</th><th class="text-end">W</th><th class="text-end">P</th><th class="text-end">M</th></tr></thead>
                <tbody>
                <?php foreach ($standings as $i => $s): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= $formatName($s['first_name'] ?? null, $s['last_name'] ?? null) ?></td>
                        <td class="text-end"><?= (int)($s['wins'] ?? 0) ?></td>
                        <td class="text-end"><?= (int)($s['losses'] ?? 0) ?></td>
                        <td class="text-end"><?= (int)($s['played'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- Upcoming -->
    <section id="upcoming" class="mb-4">
        <h5 class="mb-2"><i class="bi bi-clock"></i> Nadchodzace mecze</h5>
        <?php if (empty($upcoming)): ?>
            <div class="alert alert-light border">Brak zaplanowanych meczy.</div>
        <?php else: foreach ($upcoming as $m): ?>
            <div class="match-card">
                <div class="meta">
                    R<?= (int)$m['round'] ?> &middot; #<?= (int)$m['match_number'] ?>
                    <?php if (!empty($m['scheduled_at'])): ?>
                        &middot; <i class="bi bi-calendar3"></i> <?= View::e($m['scheduled_at']) ?>
                    <?php endif; ?>
                </div>
                <div class="row-player">
                    <span><?= $formatName($m['p1_first'] ?? null, $m['p1_last'] ?? null) ?></span>
                    <span class="text-muted small">vs</span>
                    <span><?= $formatName($m['p2_first'] ?? null, $m['p2_last'] ?? null) ?></span>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </section>

</main>

<!-- QR modal -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-qr-code"></i> Udostepnij link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="<?= View::e($qrUrl) ?>" alt="QR code" width="256" height="256" loading="lazy" style="max-width:100%; height:auto;">
                <p class="mt-3 mb-1"><strong>URL strony:</strong></p>
                <code class="d-block text-break"><?= View::e($pageUrl) ?></code>
                <button class="btn btn-primary mt-3" onclick="navigator.clipboard.writeText('<?= View::e($pageUrl) ?>'); this.textContent='Skopiowano!';">
                    <i class="bi bi-clipboard"></i> Kopiuj link
                </button>
            </div>
        </div>
    </div>
</div>

<footer class="cd-footer mt-5">
    <div class="container">
        Live scoring powered by <a href="https://clubdesk.pl" target="_blank" rel="noopener">ClubDesk</a>
        &middot; <a href="https://clubdesk.pl/cennik" target="_blank" rel="noopener">Twoj klub takze moze!</a>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
    // Section tab navigation (smooth scroll + active highlight).
    const tabs = document.querySelectorAll('.section-tabs a');
    tabs.forEach(t => t.addEventListener('click', e => {
        tabs.forEach(x => x.classList.remove('active'));
        t.classList.add('active');
    }));

    // Copy link button.
    const copyBtn = document.getElementById('copyLinkBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            navigator.clipboard.writeText('<?= View::e($pageUrl) ?>').then(() => {
                const orig = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="bi bi-check"></i> Skopiowano!';
                setTimeout(() => copyBtn.innerHTML = orig, 2000);
            });
        });
    }

    // SSE: connect + receive match-update events. Fallback: long-poll co 15s gdy brak EventSource.
    const streamUrl = <?= json_encode($streamUrl, JSON_UNESCAPED_SLASHES) ?>;
    let sinceId = <?= (int)$sinceId ?>;

    function applyUpdate(matches) {
        // Naiwna strategia: jakikolwiek update -> przeladuj strone (po 2s zeby
        // dac uzytkownikowi animacje). Mozemy zoptymalizowac do mutacji DOM
        // gdy ilosc meczy bedzie duza (na razie wystarczy).
        if (Array.isArray(matches) && matches.length > 0) {
            // Subtelny banner.
            const banner = document.createElement('div');
            banner.style.cssText = 'position:fixed;top:0;left:0;right:0;background:#198754;color:#fff;text-align:center;padding:.5rem;z-index:9999;font-weight:600;';
            banner.textContent = '⚡ Nowy wynik! Aktualizuje...';
            document.body.appendChild(banner);
            setTimeout(() => location.reload(), 1500);
        }
    }

    if (typeof EventSource !== 'undefined') {
        const es = new EventSource(streamUrl + '?since=' + sinceId);
        es.addEventListener('match-update', e => {
            try {
                const data = JSON.parse(e.data);
                applyUpdate(data);
            } catch (err) { /* ignore */ }
        });
        es.addEventListener('error', () => {
            // EventSource auto-reconnect — nic nie robimy.
        });
    } else {
        // Fallback: prosty poll co 15s.
        setInterval(() => {
            fetch(streamUrl.replace('/stream', '/stream') + '?since=' + sinceId)
                .catch(() => {});
        }, 15000);
    }
})();
</script>
</body>
</html>
