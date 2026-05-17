<?php
use App\Helpers\Csrf;
use App\Helpers\GuardianAuth;
use App\Helpers\View;

$guardian = $guardian ?? GuardianAuth::current();
$guardianName = $guardian['first_name'] ?? null;
if (!$guardianName) {
    $guardianName = $guardian['email'] ?? 'Opiekun';
}
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
?><!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= View::e($title ?? 'Portal opiekuna') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Poppins', system-ui, sans-serif; background:#f4f5f9; padding-bottom: 70px; }
        .gp-topbar { background:#2c3e50; color:#fff; padding: .75rem 1rem; position: sticky; top:0; z-index: 100; }
        .gp-topbar a { color:#fff; text-decoration:none; }
        .gp-bottomnav {
            position: fixed; bottom:0; left:0; right:0;
            background:#fff; border-top:1px solid #e2e3e7; display:flex;
            justify-content: space-around; padding: .4rem 0; z-index: 100;
            padding-bottom: calc(.4rem + env(safe-area-inset-bottom));
        }
        .gp-bottomnav a {
            color:#666; text-decoration:none; text-align:center;
            font-size:.7rem; flex:1; padding:.2rem 0;
        }
        .gp-bottomnav a.active { color:#2c3e50; }
        .gp-bottomnav i { font-size:1.2rem; display:block; }
        .gp-card { background:#fff; border-radius:10px; padding:1rem; margin-bottom:1rem; box-shadow:0 1px 3px rgba(0,0,0,.06); }
        .gp-child-tile { display:flex; align-items:center; gap:.75rem; }
        .gp-avatar { width:48px; height:48px; border-radius:50%; background:#dee2e6; display:flex; align-items:center; justify-content:center; color:#6c757d; font-weight:600; }
        @media (min-width: 768px) {
            .gp-container { max-width: 720px; margin: 1.5rem auto; }
        }
    </style>
</head>
<body>
<header class="gp-topbar">
    <div class="d-flex justify-content-between align-items-center" style="max-width:720px; margin:0 auto;">
        <a href="<?= View::e(url('portal/guardian')) ?>" class="d-flex align-items-center gap-2">
            <i class="bi bi-shield-check fs-4"></i>
            <strong>Portal opiekuna</strong>
        </a>
        <?php if (GuardianAuth::check()): ?>
            <form action="<?= View::e(url('guardian/logout')) ?>" method="post" class="m-0">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-sm btn-outline-light border-0">
                    <i class="bi bi-box-arrow-right"></i> Wyloguj
                </button>
            </form>
        <?php endif; ?>
    </div>
</header>

<main class="gp-container px-3 pt-3">
    <?php foreach (['flashError'=>'danger','flashSuccess'=>'success','flashInfo'=>'info','flashWarning'=>'warning'] as $k => $cls): ?>
        <?php if (!empty($$k)): ?>
            <div class="alert alert-<?= $cls ?> py-2"><?= View::e($$k) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>
    <?= $content ?? '' ?>
</main>

<?php if (GuardianAuth::check()): ?>
<nav class="gp-bottomnav" aria-label="Nawigacja opiekuna">
    <a href="<?= View::e(url('portal/guardian')) ?>" class="<?= ($currentPath === '/portal/guardian') ? 'active' : '' ?>">
        <i class="bi bi-house"></i> Start
    </a>
    <a href="<?= View::e(url('portal/guardian/children')) ?>" class="<?= (str_contains($currentPath, '/children') || str_contains($currentPath, '/child/')) ? 'active' : '' ?>">
        <i class="bi bi-people"></i> Dzieci
    </a>
    <a href="<?= View::e(url('portal/guardian/profile')) ?>" class="<?= str_contains($currentPath, '/profile') ? 'active' : '' ?>">
        <i class="bi bi-person"></i> Profil
    </a>
    <a href="<?= View::e(url('help/parent')) ?>" target="_blank" rel="noopener">
        <i class="bi bi-question-circle"></i> Pomoc
    </a>
</nav>
<?php endif; ?>
</body>
</html>
