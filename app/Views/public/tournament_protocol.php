<?php
use App\Helpers\View;

/**
 * Publiczna strona pobierania PDF protokolu turnieju — BEZ logowania.
 *
 * Variables:
 *   $tournament, $club, $branding, $protocol, $podium, $participantCount,
 *   $downloadUrl, $pageUrl, $qrUrl
 */

$primaryColor = htmlspecialchars((string)($branding['primary_color'] ?? '#0d6efd'), ENT_QUOTES);
$navbarBg     = htmlspecialchars((string)($branding['navbar_bg']     ?? '#212529'), ENT_QUOTES);
$accentColor  = htmlspecialchars((string)($branding['accent_color']  ?? '#198754'), ENT_QUOTES);
$logoPath     = $branding['logo_path'] ?? null;
$motto        = $branding['motto'] ?? null;

$tournamentName = (string)($tournament['name'] ?? '');
$dateStart      = (string)($tournament['date_start'] ?? '');
$clubName       = (string)($club['name'] ?? '');

$genAt          = (string)($protocol['generated_at'] ?? '');
$version        = (int)($protocol['version'] ?? 1);

$medals = [1 => '#FFD700', 2 => '#C0C0C0', 3 => '#CD7F32'];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="index,follow">
<meta name="description" content="Protokol turnieju <?= View::e($tournamentName) ?> — <?= View::e($clubName) ?>. Pobierz pelny PDF.">
<title>Protokol: <?= View::e($tournamentName) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    :root {
        --cd-primary: <?= $primaryColor ?>;
        --cd-navbar:  <?= $navbarBg ?>;
        --cd-accent:  <?= $accentColor ?>;
    }
    body { background:#f4f6f9; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; color:#212529; }
    .header { background: var(--cd-navbar); color:#fff; padding:1.25rem 1rem; }
    .header .logo { max-height:60px; background:#fff; padding:6px; border-radius:6px; }
    .header h1 { font-size:1.4rem; margin:0; }
    .header .motto { color:#cfd6df; font-size:.85rem; }
    .btn-cd { background: var(--cd-primary); color:#fff; border:none; }
    .btn-cd:hover { background: var(--cd-accent); color:#fff; }
    .podium-card {
        background:#fff; border-radius:10px; padding:1rem; text-align:center;
        box-shadow:0 1px 3px rgba(0,0,0,.06);
    }
    .podium-medal {
        width:54px; height:54px; border-radius:50%; margin:0 auto .5rem;
        display:flex; align-items:center; justify-content:center; color:#fff;
        font-weight:700; font-size:1.4rem;
    }
    .meta-row { font-size:.9rem; color:#555; }
    .meta-row strong { color:#222; }
    .footer-cd { margin-top:3rem; padding:1rem; text-align:center; color:#888; font-size:.8rem; }
    .footer-cd a { color:#666; text-decoration:none; }
</style>
</head>
<body>

<header class="header">
    <div class="container d-flex align-items-center gap-3 flex-wrap">
        <?php if (!empty($logoPath)): ?>
            <img src="<?= View::e($logoPath) ?>" alt="Logo klubu" class="logo">
        <?php endif; ?>
        <div class="flex-grow-1">
            <h1><?= View::e($clubName) ?></h1>
            <?php if ($motto): ?>
                <div class="motto"><?= View::e($motto) ?></div>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="container py-4">

    <div class="bg-white rounded-3 p-4 shadow-sm mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h2 class="mb-1"><i class="bi bi-trophy-fill me-2" style="color:var(--cd-primary)"></i><?= View::e($tournamentName) ?></h2>
                <div class="meta-row">
                    <?php if ($dateStart): ?>
                        <span class="me-3"><i class="bi bi-calendar3 me-1"></i><strong><?= View::e($dateStart) ?></strong></span>
                    <?php endif; ?>
                    <?php if ($participantCount > 0): ?>
                        <span class="me-3"><i class="bi bi-people me-1"></i><strong><?= (int)$participantCount ?></strong> uczestnik(ow)</span>
                    <?php endif; ?>
                    <span class="badge" style="background:var(--cd-accent)"><i class="bi bi-check-circle"></i> Zakonczony</span>
                </div>
            </div>
            <div class="text-end">
                <a href="<?= View::e($downloadUrl) ?>" class="btn btn-cd btn-lg">
                    <i class="bi bi-file-earmark-pdf"></i> Pobierz pelen protokol (PDF)
                </a>
            </div>
        </div>

        <?php if (!empty($podium)): ?>
        <div class="row g-3 mt-3">
            <?php foreach ($podium as $p): $pl = (int)($p['place'] ?? 0); ?>
                <div class="col-md-4">
                    <div class="podium-card">
                        <div class="podium-medal" style="background: <?= $medals[$pl] ?? '#999' ?>"><?= $pl ?: '?' ?></div>
                        <div class="fw-bold"><?= View::e(trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''))) ?></div>
                        <div class="text-muted small">
                            <?= $pl === 1 ? 'Zwyciezca' : ($pl === 2 ? 'II miejsce' : ($pl === 3 ? 'III miejsce' : '')) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="bg-white rounded-3 p-4 shadow-sm h-100">
                <h5 class="mb-3"><i class="bi bi-info-circle me-2"></i>O protokole</h5>
                <p class="mb-2 text-muted">
                    Pelny protokol turnieju zawiera klasyfikacje uczestnikow, wyniki wszystkich
                    rozegranych meczy, oraz informacje organizacyjne. Plik jest oficjalnym
                    dokumentem klubu i moze byc uzywany do raportowania federacyjnego.
                </p>
                <ul class="text-muted small mb-0">
                    <li>Wersja dokumentu: <strong>v<?= (int)$version ?></strong></li>
                    <?php if ($genAt): ?>
                        <li>Wygenerowano: <strong><?= View::e($genAt) ?></strong></li>
                    <?php endif; ?>
                    <?php if (!empty($protocol['pdf_size_bytes'])): ?>
                        <li>Rozmiar PDF: <?= number_format(((int)$protocol['pdf_size_bytes']) / 1024, 1) ?> KB</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bg-white rounded-3 p-4 shadow-sm text-center h-100">
                <h6 class="text-muted mb-3"><i class="bi bi-qr-code me-1"></i>Udostepnij</h6>
                <img src="<?= View::e($qrUrl) ?>" alt="QR" width="160" height="160" style="background:#fff;padding:6px;border-radius:6px;">
                <div class="mt-2 small text-muted">Skanuj kodem, aby udostepnic</div>
                <div class="d-grid mt-3">
                    <button class="btn btn-outline-secondary btn-sm"
                            onclick="navigator.clipboard.writeText('<?= View::e($pageUrl) ?>'); this.textContent='Skopiowano!';">
                        <i class="bi bi-clipboard"></i> Kopiuj link
                    </button>
                </div>
            </div>
        </div>
    </div>

</main>

<footer class="footer-cd">
    Powered by <a href="https://clubdesk.pl" target="_blank" rel="noopener"><strong>ClubDesk</strong></a>
    — system zarzadzania klubem sportowym.
</footer>

</body>
</html>
