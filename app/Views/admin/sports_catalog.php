<?php use App\Helpers\View; ?>
<p class="text-muted">Katalog modułów sportowych zainstalowanych w platformie. Statystyki odzwierciedlają aktywne sekcje sportowe w klubach.</p>

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Moduł / Klucz</th>
                <th>Federacja</th>
                <th>Funkcje</th>
                <th>Aktywne kluby</th>
                <th>Zawodnicy</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($sports as $key => $entry):
            $m = $entry['manifest'];
        ?>
            <tr class="<?= $entry['deprecated'] ? 'table-warning' : '' ?>">
                <td>
                    <strong><?= View::e($m['name']) ?></strong>
                    <div class="small text-muted">
                        <code><?= View::e($key) ?></code>
                        <?php if ($entry['deprecated']): ?>
                            <span class="badge bg-warning text-dark ms-1">
                                <i class="bi bi-exclamation-triangle me-1"></i>Deprecated
                            </span>
                        <?php endif; ?>
                    </div>
                </td>
                <td class="small">
                    <?php if ($entry['deprecated']): ?>
                        <span class="text-muted">
                            <?= View::e($m['federation']) ?><br>
                            <a href="https://shotero.pl" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-warning mt-1">
                                <i class="bi bi-box-arrow-up-right me-1"></i>→ shotero.pl
                            </a>
                        </span>
                    <?php else: ?>
                        <?= View::e($m['federation'] ?? '—') ?>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach (($m['features'] ?? []) as $f): ?>
                            <span class="badge bg-light text-dark border" style="font-size:.7rem"><?= View::e($f) ?></span>
                        <?php endforeach; ?>
                    </div>
                </td>
                <td>
                    <?php if ($entry['club_count'] > 0): ?>
                        <span class="badge bg-success"><?= $entry['club_count'] ?></span>
                    <?php else: ?>
                        <span class="badge bg-secondary">0</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?= $entry['member_count'] > 0
                        ? '<span class="fw-semibold">' . $entry['member_count'] . '</span>'
                        : '<span class="text-muted">0</span>' ?>
                </td>
                <td>
                    <?php if ($entry['deprecated']): ?>
                        <span class="small text-muted fst-italic">Obsługiwany przez shotero.pl</span>
                    <?php else: ?>
                        <form method="POST" action="<?= url('admin/sports/' . urlencode($key) . '/toggle') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-toggle-on"></i> Toggle
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (isset($sports['shooting'])): ?>
<div class="alert alert-warning mt-3">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Strzelectwo (shooting)</strong> — Ten moduł jest oznaczony jako <em>deprecated</em>.
    Obsługa klubów strzeleckich PZSS jest realizowana przez platformę
    <a href="https://shotero.pl" target="_blank" rel="noopener noreferrer" class="alert-link">shotero.pl</a>.
    Moduł pozostaje aktywny dla kompatybilności wstecznej. Nowe kluby strzeleckie powinny
    korzystać z shotero.pl dla zarządzania PZSS.
</div>
<?php endif; ?>
