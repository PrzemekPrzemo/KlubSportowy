<?php use App\Helpers\View; ?>
<?php
    $fullName = trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? ''));
    $avatarShown = !empty($member['public_profile_show_avatar']) && !empty($member['photo_path']);
    $avatarUrl   = $avatarShown ? url((string)$member['photo_path']) : null;
    $absoluteAvatar = $avatarShown ? (rtrim(BASE_URL, '/') . '/' . ltrim((string)$member['photo_path'], '/')) : null;
?>

<?php ob_start(); ?>
<meta property="og:type" content="profile">
<meta property="og:title" content="<?= View::e($fullName) ?> — <?= View::e($appName ?? 'ClubDesk') ?>">
<meta property="og:description" content="<?= View::e($metaDesc ?? '') ?>">
<?php if ($absoluteAvatar): ?>
<meta property="og:image" content="<?= View::e($absoluteAvatar) ?>">
<?php endif; ?>
<meta property="og:url" content="<?= View::e($profileUrl ?? '') ?>">
<meta name="description" content="<?= View::e($metaDesc ?? '') ?>">
<meta name="robots" content="<?= !empty($isPublic) ? 'index, follow' : 'noindex' ?>">
<link rel="canonical" href="<?= View::e($profileUrl ?? '') ?>">
<?php $extraHead = ob_get_clean(); ?>

<div class="pub-hero mb-4"<?php if (!empty($club['primary_color'])): ?>
    style="background: linear-gradient(135deg, <?= View::e($club['primary_color']) ?> 0%, #212529 100%);"<?php endif; ?>>
    <div class="container">
        <div class="d-flex align-items-center flex-wrap gap-3">
            <?php if ($avatarUrl): ?>
                <img src="<?= View::e($avatarUrl) ?>" alt="<?= View::e($fullName) ?>"
                     style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid #fff;background:#fff;">
            <?php else: ?>
                <div style="width:96px;height:96px;border-radius:50%;background:rgba(255,255,255,.18);
                            display:flex;align-items:center;justify-content:center;font-size:2rem;color:#fff;border:3px solid #fff;">
                    <i class="bi bi-person"></i>
                </div>
            <?php endif; ?>
            <div>
                <h1 class="mb-1"><?= View::e($fullName) ?></h1>
                <?php if (!empty($club) && !empty($member['public_profile_show_club'])): ?>
                    <p class="mb-0 opacity-75">
                        <i class="bi bi-building"></i>
                        <?php if (!empty($club['subdomain'])): ?>
                            <a href="<?= url('c/' . $club['subdomain']) ?>" style="color:#fff;text-decoration:underline;">
                                <?= View::e($club['name']) ?>
                            </a>
                        <?php else: ?>
                            <?= View::e($club['name']) ?>
                        <?php endif; ?>
                        <?php if (!empty($club['city'])): ?>
                            <span class="ms-2"><i class="bi bi-geo-alt"></i> <?= View::e($club['city']) ?></span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($age)): ?>
                    <p class="mb-0 opacity-75"><i class="bi bi-calendar3"></i> Wiek: <?= (int)$age ?></p>
                <?php elseif (!empty($birthYear)): ?>
                    <p class="mb-0 opacity-75"><i class="bi bi-calendar3"></i> Rocznik: <?= (int)$birthYear ?></p>
                <?php endif; ?>
                <?php if (empty($isPublic)): ?>
                    <span class="badge bg-warning text-dark mt-2"><i class="bi bi-lock"></i> Tylko czlonkowie klubu</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <?php if (!empty($bio)): ?>
                <div class="card p-4 mb-4">
                    <h5 class="mb-2"><i class="bi bi-quote"></i> O mnie</h5>
                    <p class="mb-0" style="white-space: pre-wrap;"><?= View::e($bio) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($member['public_profile_show_sports']) && !empty($sports)): ?>
                <div class="card p-4 mb-4">
                    <h5 class="mb-3"><i class="bi bi-trophy"></i> Sporty</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($sports as $s): ?>
                            <span class="sport-badge" style="background: <?= View::e($s['color'] ?? '#6c757d') ?>; font-size: 1rem; padding: .5rem .9rem;">
                                <?php if (!empty($s['icon'])): ?>
                                    <i class="bi <?= View::e($s['icon']) ?>"></i>
                                <?php endif; ?>
                                <?= View::e($s['name']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($member['public_profile_show_rankings']) && !empty($rankings)): ?>
                <div class="card p-4 mb-4">
                    <h5 class="mb-3"><i class="bi bi-graph-up-arrow"></i> Rankingi</h5>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Sport</th>
                                    <th>Sezon</th>
                                    <th class="text-end">Pozycja</th>
                                    <th class="text-end">Punkty</th>
                                    <th class="text-end">Starty</th>
                                    <th class="text-end">Wygrane</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rankings as $r): ?>
                                    <tr>
                                        <td><?= View::e($r['sport_key']) ?></td>
                                        <td><?= View::e($r['season']) ?></td>
                                        <td class="text-end"><?= $r['ranking_position'] ? '#' . (int)$r['ranking_position'] : '—' ?></td>
                                        <td class="text-end"><?= (int)$r['ranking_points'] ?></td>
                                        <td class="text-end"><?= (int)$r['competitions_count'] ?></td>
                                        <td class="text-end"><?= (int)$r['wins'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($member['public_profile_show_tournaments']) && !empty($tournaments)): ?>
                <div class="card p-4 mb-4">
                    <h5 class="mb-3"><i class="bi bi-calendar-event"></i> Turnieje</h5>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($tournaments as $t): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                                <div>
                                    <strong><?= View::e($t['name']) ?></strong>
                                    <div class="small text-muted">
                                        <?= View::e($t['start_date'] ?? '') ?>
                                        <?php if (!empty($t['end_date'])): ?>– <?= View::e($t['end_date']) ?><?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!empty($t['eliminated'])): ?>
                                    <span class="badge bg-secondary">Wyeliminowany</span>
                                <?php elseif (!empty($t['status'])): ?>
                                    <span class="badge bg-primary"><?= View::e($t['status']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($t['seed'])): ?>
                                    <span class="badge bg-info text-dark ms-1">seed #<?= (int)$t['seed'] ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card p-4">
                <h6 class="text-muted small text-uppercase mb-2">Profil zawodnika</h6>
                <p class="small mb-2">
                    Ten profil zostal udostepniony publicznie przez zawodnika.
                </p>
                <p class="small text-muted mb-0">
                    <i class="bi bi-eye"></i> Wyswietlen: <?= (int)($member['public_profile_view_count'] ?? 0) ?>
                </p>
            </div>
            <?php if (!empty($club) && !empty($member['public_profile_show_club']) && !empty($club['subdomain'])): ?>
                <a href="<?= url('c/' . $club['subdomain']) ?>" class="btn btn-outline-primary btn-sm mt-3 w-100">
                    <i class="bi bi-building"></i> Zobacz klub
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
    // Inject meta tags do head'a layoutu poprzez globalna zmienna
    // (public.php layout moze ja czytac); jesli nie - drukujemy fallback ponizej.
    if (isset($GLOBALS['__pageHead'])) {
        $GLOBALS['__pageHead'] .= $extraHead;
    } else {
        $GLOBALS['__pageHead'] = $extraHead;
    }
?>
