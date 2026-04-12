<?php use App\Helpers\View; ?>

<div class="pub-hero mb-4" <?php if (!empty($club['primary_color'])): ?>
    style="background: linear-gradient(135deg, <?= View::e($club['primary_color']) ?> 0%, #212529 100%);"
<?php endif; ?>>
    <div class="container">
        <div class="d-flex align-items-center">
            <?php if (!empty($club['logo_path'])): ?>
                <img src="<?= url($club['logo_path']) ?>" alt="logo"
                     style="max-width:80px; max-height:80px; margin-right:1.5rem; border-radius:.5rem; background:#fff; padding:4px;">
            <?php endif; ?>
            <div>
                <h1 class="mb-1"><?= View::e($club['name']) ?></h1>
                <?php if (!empty($club['city'])): ?>
                    <p class="mb-0 opacity-75"><i class="bi bi-geo-alt"></i> <?= View::e($club['city']) ?></p>
                <?php endif; ?>
                <?php if (!empty($club['motto'])): ?>
                    <p class="mb-0 fst-italic opacity-75"><?= View::e($club['motto']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4">
        <!-- Kolumna glowna -->
        <div class="col-lg-8">
            <!-- Sekcje sportowe -->
            <h4 class="mb-3"><i class="bi bi-trophy"></i> Sekcje sportowe</h4>
            <?php if (empty($sports)): ?>
                <p class="text-muted">Brak aktywnych sekcji sportowych.</p>
            <?php else: ?>
                <div class="row g-3 mb-4">
                    <?php foreach ($sports as $sport): ?>
                        <div class="col-sm-6 col-md-4">
                            <div class="card text-center p-3">
                                <div class="mb-2">
                                    <span class="sport-badge" style="background: <?= View::e($sport['color'] ?? '#6c757d') ?>; font-size: 1rem; padding: .4rem .8rem;">
                                        <?php if (!empty($sport['icon'])): ?>
                                            <i class="bi <?= View::e($sport['icon']) ?>"></i>
                                        <?php endif; ?>
                                        <?= View::e($sport['name']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Nadchodzace wydarzenia -->
            <h4 class="mb-3"><i class="bi bi-calendar-event"></i> Nadchodzace wydarzenia</h4>
            <?php if (empty($events)): ?>
                <p class="text-muted">Brak nadchodzacych wydarzen.</p>
            <?php else: ?>
                <div class="list-group mb-4">
                    <?php foreach ($events as $ev): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?= View::e($ev['name']) ?></h6>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> <?= format_datetime($ev['event_date']) ?>
                                        <?php if (!empty($ev['location'])): ?>
                                            &middot; <i class="bi bi-geo-alt"></i> <?= View::e($ev['location']) ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-info"><?= View::e($ev['type']) ?></span>
                                    <?php if (!empty($ev['sport_name'])): ?>
                                        <br>
                                        <span class="sport-badge mt-1" style="background: <?= View::e($ev['sport_color'] ?? '#6c757d') ?>">
                                            <?= View::e($ev['sport_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <a href="<?= url('pub/' . urlencode($club['subdomain']) . '/results') ?>"
               class="btn btn-outline-secondary">
                <i class="bi bi-bar-chart"></i> Zobacz wyniki
            </a>
        </div>

        <!-- Sidebar: kontakt -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <i class="bi bi-info-circle"></i> Informacje kontaktowe
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <?php if (!empty($club['email'])): ?>
                            <li class="mb-2">
                                <i class="bi bi-envelope text-primary"></i>
                                <a href="mailto:<?= View::e($club['email']) ?>"><?= View::e($club['email']) ?></a>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($club['phone'])): ?>
                            <li class="mb-2">
                                <i class="bi bi-telephone text-primary"></i>
                                <?= View::e($club['phone']) ?>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($club['address'])): ?>
                            <li class="mb-2">
                                <i class="bi bi-geo text-primary"></i>
                                <?= nl2br(View::e($club['address'])) ?>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($club['website'])): ?>
                            <li class="mb-2">
                                <i class="bi bi-globe text-primary"></i>
                                <a href="<?= View::e($club['website']) ?>" target="_blank" rel="noopener">
                                    <?= View::e($club['website']) ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($club['founded_year'])): ?>
                            <li class="mb-2">
                                <i class="bi bi-calendar3 text-primary"></i>
                                Rok zalozenia: <?= View::e($club['founded_year']) ?>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($club['nip'])): ?>
                            <li class="mb-0">
                                <i class="bi bi-building text-primary"></i>
                                NIP: <?= View::e($club['nip']) ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
