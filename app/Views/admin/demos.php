<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Tokeny demo</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#demoModal">
            <i class="bi bi-plus-circle"></i> Nowe demo
        </button>
        <form method="POST" action="<?= url('admin/demos/cleanup') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button class="btn btn-outline-warning"><i class="bi bi-trash"></i> Wyczyść wygasłe</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
            <tr>
                <th>Klub</th>
                <th>Konfiguracja</th>
                <th style="min-width:240px;">Link demo</th>
                <th style="white-space:nowrap;">Wygasa</th>
                <th>Utworzony przez</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($tokens)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Brak aktywnych tokenów demo.</td></tr>
        <?php else: ?>
            <?php foreach ($tokens as $t): ?>
                <?php
                    $demoUrl = url('demo/' . $t['token']);
                    $cfgJson = null;
                    try {
                        $cfgJson = (new \App\Models\ClubSettingsModel())->get((int)$t['club_id'], 'demo_config');
                    } catch (\Throwable) {}
                    $cfg = $cfgJson ? json_decode($cfgJson, true) : null;
                ?>
                <tr>
                    <td>
                        <strong><?= View::e($t['club_name']) ?></strong>
                        <div class="text-muted small"><?= format_datetime($t['created_at']) ?></div>
                    </td>
                    <td style="max-width:320px;">
                        <?php if ($cfg): ?>
                            <?php
                                $sportsArr  = $cfg['sports']  ?? [];
                                $modulesArr = $cfg['modules'] ?? [];
                                $sportsShown  = array_slice($sportsArr, 0, 5);
                                $sportsRest   = max(0, count($sportsArr) - 5);
                                $modulesShown = array_slice($modulesArr, 0, 4);
                                $modulesRest  = max(0, count($modulesArr) - 4);
                            ?>
                            <div class="d-flex flex-wrap gap-1 small">
                                <?php foreach ($sportsShown as $s): ?>
                                    <span class="badge bg-primary"><?= View::e($s) ?></span>
                                <?php endforeach; ?>
                                <?php if ($sportsRest > 0): ?>
                                    <span class="badge bg-primary-subtle text-primary-emphasis"
                                          title="<?= View::e(implode(', ', array_slice($sportsArr, 5))) ?>">
                                        +<?= $sportsRest ?> sport.
                                    </span>
                                <?php endif; ?>
                                <?php foreach ($modulesShown as $m): ?>
                                    <span class="badge bg-secondary"><?= View::e($m) ?></span>
                                <?php endforeach; ?>
                                <?php if ($modulesRest > 0): ?>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis"
                                          title="<?= View::e(implode(', ', array_slice($modulesArr, 4))) ?>">
                                        +<?= $modulesRest ?> mod.
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($cfg['volume'])): ?>
                                    <span class="badge bg-info text-dark"><?= View::e($cfg['volume']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="min-width:240px; max-width:280px;">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" value="<?= View::e($demoUrl) ?>"
                                   readonly id="link-<?= (int)$t['id'] ?>">
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="var el=document.getElementById('link-<?= (int)$t['id'] ?>');navigator.clipboard.writeText(el.value).then(()=>{this.textContent='✓';setTimeout(()=>this.textContent='Kopiuj',1500)})">Kopiuj</button>
                            <a href="<?= View::e($demoUrl) ?>" class="btn btn-outline-primary" target="_blank" title="Otwórz">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </div>
                    </td>
                    <td class="small" style="white-space:nowrap;"><?= format_datetime($t['expires_at']) ?></td>
                    <td class="small"><?= View::e($t['creator_name'] ?? '—') ?></td>
                    <td>
                        <form method="POST" action="<?= url('admin/demos/' . (int)$t['id'] . '/delete') ?>"
                              onsubmit="return confirm('Usunąć ten token demo?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger" title="Usuń"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- ── Modal: Nowe demo ───────────────────────────────────────────────── -->
<div class="modal fade" id="demoModal" tabindex="-1" aria-labelledby="demoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('admin/demos/create') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="demoModalLabel">
                        <i class="bi bi-rocket-takeoff me-1"></i> Utwórz konto demo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Nazwa klubu demo <span class="text-muted fw-normal">(opcjonalna)</span></label>
                        <input type="text" name="club_name" class="form-control" placeholder="np. FC Barcelona Demo">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Sekcje sportowe</label>
                        <div class="row g-2">
                            <?php
                            $sportLabels = [
                                'football'      => ['Piłka nożna',   'bi-dribbble'],
                                'basketball'    => ['Koszykówka',    'bi-emoji-sunglasses'],
                                'volleyball'    => ['Siatkówka',     'bi-circle'],
                                'shooting'      => ['Strzelectwo',   'bi-bullseye'],
                                'athletics'     => ['Lekkoatletyka', 'bi-lightning'],
                                'rollerskating' => ['Łyżwiarstwo',   'bi-snow'],
                            ];
                            foreach (($sports ?? []) as $sport):
                                $key   = $sport['key'];
                                $label = $sportLabels[$key][0] ?? $sport['name'];
                                $icon  = $sportLabels[$key][1] ?? 'bi-trophy';
                            ?>
                            <div class="col-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="sports[]"
                                           value="<?= View::e($key) ?>" id="sport-<?= View::e($key) ?>" checked>
                                    <label class="form-check-label" for="sport-<?= View::e($key) ?>">
                                        <i class="bi <?= $icon ?> me-1"></i><?= View::e($label) ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Aktywne moduły</label>
                        <div class="row g-2">
                            <?php foreach ([
                                'gallery'    => ['Galeria',    'bi-images'],
                                'messages'   => ['Wiadomości', 'bi-chat-dots'],
                                'bookings'   => ['Rezerwacje', 'bi-calendar-check'],
                                'analytics'  => ['Analityka',  'bi-bar-chart'],
                                'shop'       => ['Sklep',      'bi-bag'],
                                'livestream' => ['Transmisje', 'bi-camera-video'],
                            ] as $key => [$label, $icon]): ?>
                            <div class="col-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="modules[]"
                                           value="<?= $key ?>" id="module-<?= $key ?>" checked>
                                    <label class="form-check-label" for="module-<?= $key ?>">
                                        <i class="bi <?= $icon ?> me-1"></i><?= $label ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Wolumen danych demo</label>
                            <div class="d-flex gap-3 flex-wrap">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="volume" value="basic" id="vol-basic">
                                    <label class="form-check-label" for="vol-basic">
                                        Podstawowy<br><small class="text-muted">5 członków</small>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="volume" value="standard" id="vol-standard" checked>
                                    <label class="form-check-label" for="vol-standard">
                                        Standardowy<br><small class="text-muted">10 członków</small>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="volume" value="full" id="vol-full">
                                    <label class="form-check-label" for="vol-full">
                                        Pełny<br><small class="text-muted">25 członków + galeria + badania</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label for="demo-duration" class="form-label fw-semibold">Czas trwania</label>
                            <select name="duration" id="demo-duration" class="form-select">
                                <option value="7">7 dni</option>
                                <option value="14" selected>14 dni</option>
                                <option value="30">30 dni</option>
                                <option value="60">60 dni (długi trial)</option>
                            </select>
                            <div class="form-text small mt-2">
                                <i class="bi bi-info-circle"></i>
                                Po utworzeniu demo otrzymasz link (<code>demo/:token</code>), który można udostępnić — pozwala zalogować się bez hasła.
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-rocket-takeoff me-1"></i> Utwórz demo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
