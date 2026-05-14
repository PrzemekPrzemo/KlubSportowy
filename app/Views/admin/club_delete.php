<?php use App\Helpers\View; ?>
<div class="alert alert-danger d-flex align-items-start gap-2 mb-3" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-3"></i>
    <div>
        <h5 class="alert-heading mb-1">Nieodwracalne usuniecie klubu</h5>
        <p class="mb-0">
            Klub <strong><?= View::e($club['name'] ?? '') ?></strong> (id=<?= (int)($club['id'] ?? 0) ?>)
            wraz ze WSZYSTKIMI powiazanymi danymi zostanie permanentnie usuniety z bazy.
            <br>Operacja jest <strong>nieodwracalna</strong> — uzyj opcji backupu ponizej, jesli nie masz pewnosci.
        </p>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-trash"></i> Co zostanie usuniete
            </div>
            <div class="card-body p-0">
                <?php if (empty($stats)): ?>
                    <div class="p-3 text-muted text-center">Brak danych powiazanych z klubem.</div>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Co</th>
                                <th class="text-end">Liczba rekordow</th>
                                <th class="text-muted small">tabela</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats as $table => $s): ?>
                                <tr>
                                    <td><?= View::e($s['label']) ?></td>
                                    <td class="text-end"><strong><?= (int)$s['count'] ?></strong></td>
                                    <td class="text-muted small"><code><?= View::e($table) ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="card-footer text-muted small">
                Plus: wszystkie pliki uploadow (logo, favicon, branding) zwiazane z tym klubem.
                <br>FK <code>ON DELETE CASCADE</code> zadba o spojnosc bazy.
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-shield-check"></i> Potwierdzenie</div>
            <div class="card-body">
                <form method="POST" action="<?= url('admin/clubs/' . (int)($club['id'] ?? 0) . '/delete') ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="confirm_name" class="form-label">
                            Aby potwierdzic, wpisz nazwe klubu <strong>dokladnie</strong>:
                        </label>
                        <div class="form-text mb-2">
                            <code class="user-select-all"><?= View::e($club['name'] ?? '') ?></code>
                        </div>
                        <input type="text"
                               name="confirm_name"
                               id="confirm_name"
                               class="form-control"
                               required
                               autocomplete="off"
                               placeholder="Wpisz dokladnie nazwe klubu">
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox"
                               name="backup_first"
                               id="backup_first"
                               value="1"
                               class="form-check-input"
                               checked>
                        <label for="backup_first" class="form-check-label">
                            Wykonaj backup przed usunieciem <span class="text-success">(zalecane)</span>
                            <div class="form-text">
                                Backup zapisany w <code>storage/backups/</code> przez <code>cli/backup_club.php</code>.
                                Backup zawiera tylko dane TEGO klubu (pre-tenant scoped mysqldump).
                            </div>
                        </label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger" id="delete-btn" disabled>
                            <i class="bi bi-trash-fill"></i> Usun klub bezpowrotnie
                        </button>
                        <a href="<?= url('admin/clubs') ?>" class="btn btn-secondary">
                            Anuluj
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Aktywuj przycisk Usun tylko gdy wpisana nazwa zgadza sie z oczekiwana
(function() {
    var input = document.getElementById('confirm_name');
    var btn = document.getElementById('delete-btn');
    var expected = <?= json_encode($club['name'] ?? '') ?>;
    if (!input || !btn) return;
    input.addEventListener('input', function() {
        btn.disabled = (input.value !== expected);
    });
})();
</script>
