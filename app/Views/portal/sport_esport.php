<?php
/**
 * Portal zawodnika — sekcja E-sport.
 *
 * Renderuje:
 *   - kafelki moich profili gier (game / IGN / platforma / ELO / W/P / stream),
 *   - przycisk "Dodaj profil dla gry" (modal — POST do /portal/esport/profiles/save),
 *   - leaderboard klubu top 20 z dropdownem wyboru gry,
 *   - najblizszy turniej w ktorym zglosilem sie (jesli istnieje).
 *
 * @var array<int,array<string,mixed>> $profiles
 * @var array<int,array<string,mixed>> $games
 * @var array<string,string>           $platforms
 * @var string|null                    $selectedGameCode
 * @var array<string,mixed>|null       $selectedGame
 * @var array<int,array<string,mixed>> $leaderboard
 * @var array<string,mixed>|null       $nextTournament
 */
use App\Helpers\View;
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-controller text-primary me-2"></i>E-sport — Mój profil</h4>
    <div class="d-flex gap-2">
        <a href="<?= url('portal/esport/profiles') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-list-ul"></i> Tryb klasyczny
        </a>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#esportProfileModal">
            <i class="bi bi-plus-circle"></i> Dodaj profil dla gry
        </button>
    </div>
</div>

<?php if (!empty($nextTournament)): ?>
<div class="card border-warning mb-3">
    <div class="card-body py-2 d-flex align-items-center gap-3 flex-wrap">
        <i class="bi bi-trophy-fill text-warning fs-3"></i>
        <div class="flex-grow-1">
            <div class="small text-muted">Najblizszy turniej e-sport</div>
            <strong><?= View::e((string)$nextTournament['name']) ?></strong>
            <span class="text-muted ms-2">
                <?= function_exists('format_date') ? format_date($nextTournament['date_start']) : View::e((string)$nextTournament['date_start']) ?>
            </span>
            <span class="badge bg-light text-dark border ms-2"><?= View::e((string)$nextTournament['my_status']) ?></span>
        </div>
        <a href="<?= url('portal/tournaments/' . (int)$nextTournament['id']) ?>" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-eye"></i> Szczegoly
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Moje gry -->
<div class="card mb-3">
    <div class="card-header bg-light"><strong>Moje gry</strong></div>
    <div class="card-body">
        <?php if (empty($profiles)): ?>
            <div class="text-muted text-center py-4">
                <i class="bi bi-controller fs-1"></i>
                <p class="mt-2 mb-0">Nie dodales jeszcze zadnej gry. Kliknij <strong>Dodaj profil dla gry</strong>.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($profiles as $p): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0">
                                        <?= View::e((string)($p['game_display_name'] ?? $p['game_code'])) ?>
                                    </h6>
                                    <span class="badge bg-secondary"><?= View::e($platforms[$p['platform']] ?? (string)$p['platform']) ?></span>
                                </div>
                                <p class="text-muted small mb-2 font-monospace"><?= View::e((string)$p['in_game_name']) ?></p>

                                <div class="row text-center mb-2">
                                    <div class="col-4">
                                        <div class="small text-muted">ELO</div>
                                        <strong class="fs-6"><?= (int)$p['elo_rating'] ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <div class="small text-muted">Ranga</div>
                                        <strong class="small"><?= View::e((string)($p['rank_tier'] ?? '—')) ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <div class="small text-muted">W / P</div>
                                        <strong class="small">
                                            <span class="text-success"><?= (int)$p['wins'] ?></span>
                                            /
                                            <span class="text-danger"><?= (int)$p['losses'] ?></span>
                                        </strong>
                                    </div>
                                </div>

                                <div class="d-flex gap-1 flex-wrap">
                                    <?php if (!empty($p['stream_url'])): ?>
                                        <a href="<?= View::e((string)$p['stream_url']) ?>" target="_blank" rel="noopener"
                                           class="btn btn-sm btn-outline-purple flex-grow-1"
                                           style="color:#6f42c1;border-color:#6f42c1;">
                                            <i class="bi bi-broadcast"></i> Twitch live
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= url('portal/sport/esport?game=' . urlencode((string)$p['game_code'])) ?>"
                                       class="btn btn-sm btn-outline-warning flex-grow-1">
                                        <i class="bi bi-trophy"></i> Leaderboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Leaderboard klubu -->
<div class="card mb-3">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
        <strong><i class="bi bi-trophy-fill text-warning me-1"></i>Leaderboard klubu — top 20</strong>
        <form method="GET" action="<?= url('portal/sport/esport') ?>" class="d-flex gap-2">
            <select name="game" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">— wybierz gre —</option>
                <?php foreach ($games as $g): ?>
                    <option value="<?= View::e((string)$g['game_code']) ?>"
                            <?= ($selectedGameCode === $g['game_code']) ? 'selected' : '' ?>>
                        <?= View::e((string)$g['display_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="card-body p-0">
        <?php if ($selectedGame === null): ?>
            <p class="text-muted text-center py-4 mb-0">
                <i class="bi bi-info-circle me-1"></i>Wybierz gre z listy aby zobaczyc leaderboard.
            </p>
        <?php elseif (empty($leaderboard)): ?>
            <p class="text-muted text-center py-4 mb-0">
                Brak zawodnikow z profilem w grze <strong><?= View::e((string)$selectedGame['display_name']) ?></strong>.
            </p>
        <?php else: ?>
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Zawodnik</th>
                        <th>IGN</th>
                        <th class="text-end">ELO</th>
                        <th class="text-end">W / P</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($leaderboard as $i => $r):
                    $rank = $i + 1;
                    $rankCls = $rank === 1 ? 'fw-bold text-warning'
                        : ($rank === 2 ? 'fw-bold text-secondary'
                        : ($rank === 3 ? 'fw-bold text-danger' : ''));
                ?>
                    <tr>
                        <td class="<?= $rankCls ?>">#<?= (int)$rank ?></td>
                        <td>
                            <?= View::e(trim((string)($r['first_name'] ?? '') . ' ' . (string)($r['last_name'] ?? ''))) ?>
                            <?php if (!empty($r['member_number'])): ?>
                                <small class="text-muted">(<?= View::e((string)$r['member_number']) ?>)</small>
                            <?php endif; ?>
                        </td>
                        <td><code class="small"><?= View::e((string)$r['in_game_name']) ?></code></td>
                        <td class="text-end"><strong><?= (int)$r['elo_rating'] ?></strong></td>
                        <td class="text-end small">
                            <span class="text-success"><?= (int)$r['wins'] ?></span>
                            /
                            <span class="text-danger"><?= (int)$r['losses'] ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: dodaj/edytuj profil -->
<div class="modal fade" id="esportProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('portal/esport/profiles/save') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-controller me-1"></i>Dodaj/edytuj profil w grze</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Gra</label>
                            <select name="game_code" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($games as $g): ?>
                                    <option value="<?= View::e((string)$g['game_code']) ?>">
                                        <?= View::e((string)$g['display_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Jesli edytujesz juz dodana gre — wybierz ja ponownie aby zaktualizowac dane (upsert).</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nick w grze (IGN)</label>
                            <input type="text" name="in_game_name" class="form-control" required maxlength="200">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Platforma</label>
                            <select name="platform" class="form-select">
                                <?php foreach ($platforms as $k => $label): ?>
                                    <option value="<?= View::e((string)$k) ?>"><?= View::e((string)$label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Ranga (opcjonalnie)</label>
                            <input type="text" name="rank_tier" class="form-control" maxlength="50"
                                   placeholder="np. Diamond II">
                        </div>
                        <div class="col-12">
                            <label class="form-label">URL streamingu (Twitch/YouTube)</label>
                            <input type="url" name="stream_url" class="form-control"
                                   placeholder="https://twitch.tv/twoj_kanal">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button class="btn btn-success"><i class="bi bi-check-circle"></i> Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
