<?php
use App\Helpers\View;
?>
<h2 class="mb-3"><i class="bi bi-people"></i> Moi zawodnicy</h2>

<?php if (!empty($sections)): ?>
<form method="get" class="mb-3 d-flex flex-wrap gap-2 align-items-center">
    <label class="me-2"><strong>Sekcja:</strong></label>
    <select name="club_sport_id" class="form-select form-select-sm" style="max-width:240px;" onchange="this.form.submit()">
        <option value="0">Wszystkie moje sekcje</option>
        <?php foreach ($sections as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= (int)$filterSection === (int)$s['id'] ? 'selected' : '' ?>>
                <?= View::e($s['sport_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <noscript><button type="submit" class="btn btn-sm btn-secondary">Filtruj</button></noscript>
</form>
<?php endif; ?>

<?php if (empty($members)): ?>
    <div class="alert alert-info">
        <?php if (empty($sections)): ?>
            Nie jestes jeszcze przypisany jako instruktor zadnego treningu w tym klubie.
            Sekcje sa wnioskowane z prowadzonych treningow.
        <?php else: ?>
            Brak aktywnych zawodnikow w wybranej sekcji.
        <?php endif; ?>
    </div>
<?php else: ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Imie i nazwisko</th>
                    <th>Ostatnia obecnosc</th>
                    <th>Frekwencja</th>
                    <th>Badania</th>
                    <th>Kontakt</th>
                    <th class="text-end">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $m): ?>
                    <?php
                    $pct = $m['attendance_pct'] !== null ? (float)$m['attendance_pct'] : null;
                    $valid = $m['medical_valid_until'] ?? null;
                    $expired = $valid && strtotime($valid) < time();
                    $expiringSoon = $valid && !$expired && strtotime($valid) < strtotime('+30 days');
                    ?>
                    <tr>
                        <td><small class="text-muted"><?= View::e((string)$m['member_number']) ?></small></td>
                        <td>
                            <a href="<?= url('members/' . (int)$m['id']) ?>">
                                <?= View::e($m['last_name'] . ' ' . $m['first_name']) ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($m['last_attendance']): ?>
                                <small><?= View::e(substr((string)$m['last_attendance'], 0, 10)) ?></small>
                            <?php else: ?>
                                <small class="text-muted">brak</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($pct !== null): ?>
                                <span class="badge bg-<?= $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') ?>">
                                    <?= number_format($pct, 1) ?>%
                                </span>
                            <?php else: ?>
                                <small class="text-muted">—</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$valid): ?>
                                <span class="badge bg-secondary">brak</span>
                            <?php elseif ($expired): ?>
                                <span class="badge bg-danger" title="<?= View::e($valid) ?>">wygasly</span>
                            <?php elseif ($expiringSoon): ?>
                                <span class="badge bg-warning text-dark" title="<?= View::e($valid) ?>">do <?= View::e($valid) ?></span>
                            <?php else: ?>
                                <span class="badge bg-success" title="<?= View::e($valid) ?>">aktualne</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($m['email'])): ?>
                                <small><a href="mailto:<?= View::e($m['email']) ?>"><?= View::e($m['email']) ?></a></small><br>
                            <?php endif; ?>
                            <?php if (!empty($m['phone'])): ?>
                                <small><i class="bi bi-telephone"></i> <?= View::e($m['phone']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('messages/new?to_member=' . (int)$m['id']) ?>" class="btn btn-sm btn-outline-primary" title="Wyslij wiadomosc">
                                <i class="bi bi-chat-dots"></i>
                            </a>
                            <a href="<?= url('members/' . (int)$m['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Profil">
                                <i class="bi bi-person"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
