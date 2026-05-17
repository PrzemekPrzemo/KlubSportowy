<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">Przywroc dane z backupu</h4>
        <div class="text-muted small">Wgraj plik ZIP wygenerowany przez ClubDesk. Najpierw zostanie zwalidowany — dopiero potem mozna wykonac import.</div>
    </div>
    <a href="<?= url('club/backup') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Wroc do listy
    </a>
</div>

<?php $preview = $preview ?? null; ?>

<?php if ($preview === null): ?>
    <form method="POST" action="<?= url('club/backup/restore/preview') ?>" enctype="multipart/form-data" class="card p-4">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Plik backupu (.zip, max 500 MB)</label>
            <input type="file" name="backup_zip" accept=".zip,application/zip" class="form-control" required>
        </div>
        <div class="alert alert-warning small">
            <strong>Uwaga:</strong> import to operacja krytyczna. Tryb "overwrite" usuwa wszystkie obecne
            dane klubu (czlonkow, platnosci, treningi) PRZED wstawieniem nowych. Najpierw zrob backup biezacy!
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-upload"></i> Wgraj i zwaliduj
        </button>
    </form>
<?php else: ?>
    <div class="card p-4 mb-3">
        <h5>Walidacja backupu</h5>
        <?php $m = $preview['manifest'] ?? []; ?>
        <?php if ($m): ?>
            <dl class="row small">
                <dt class="col-sm-3">Format</dt>            <dd class="col-sm-9"><code><?= View::e((string)($m['format_version'] ?? '?')) ?></code></dd>
                <dt class="col-sm-3">Schema</dt>            <dd class="col-sm-9"><code><?= View::e((string)($m['schema_version'] ?? '?')) ?></code></dd>
                <dt class="col-sm-3">Klub zrodlowy</dt>     <dd class="col-sm-9"><?= View::e((string)($m['club_name'] ?? '')) ?> (id=<?= (int)($m['club_id'] ?? 0) ?>)</dd>
                <dt class="col-sm-3">Wygenerowano</dt>      <dd class="col-sm-9"><?= View::e((string)($m['exported_at'] ?? '')) ?></dd>
                <dt class="col-sm-3">Tryb</dt>              <dd class="col-sm-9"><?= View::e((string)($m['mode'] ?? '')) ?></dd>
                <dt class="col-sm-3">Wiersze</dt>           <dd class="col-sm-9"><?= (int)($m['totals']['rows']  ?? 0) ?></dd>
                <dt class="col-sm-3">Pliki</dt>             <dd class="col-sm-9"><?= (int)($m['totals']['files'] ?? 0) ?></dd>
            </dl>
            <?php if (!empty($m['table_counts'])): ?>
                <details>
                    <summary class="small text-muted">Tabele (<?= count($m['table_counts']) ?>)</summary>
                    <ul class="small mb-0">
                    <?php foreach ($m['table_counts'] as $t => $c): ?>
                        <li><code><?= View::e((string)$t) ?></code>: <?= (int)$c ?></li>
                    <?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($preview['errors'])): ?>
            <div class="alert alert-danger mt-3 mb-0">
                <strong>Bledy:</strong>
                <ul class="mb-0"><?php foreach ($preview['errors'] as $e): ?><li><?= View::e($e) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>
        <?php if (!empty($preview['warnings'])): ?>
            <div class="alert alert-warning mt-3 mb-0">
                <strong>Ostrzezenia:</strong>
                <ul class="mb-0"><?php foreach ($preview['warnings'] as $w): ?><li><?= View::e($w) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($preview['valid'])): ?>
        <form method="POST" action="<?= url('club/backup/restore/execute') ?>" class="card p-4">
            <?= csrf_field() ?>
            <input type="hidden" name="upload_token" value="<?= View::e((string)($uploadToken ?? '')) ?>">
            <h5>Strategia importu</h5>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="overwrite" name="overwrite" value="1">
                <label class="form-check-label" for="overwrite">
                    <strong>Overwrite</strong> — usun wszystkie obecne dane klubu PRZED importem.
                    <span class="text-danger">Operacja nieodwracalna!</span>
                </label>
            </div>
            <p class="small text-muted mt-2">
                Bez "overwrite": import dodaje wiersze (merge). UNIQUE constraints moga spowodowac
                pominiecie czesci wpisow — sprawdz log po imporcie.
            </p>
            <button type="submit" class="btn btn-danger mt-2"
                    onclick="return confirm('Na pewno wykonac import? Ta operacja moze nadpisac obecne dane.');">
                <i class="bi bi-check-circle"></i> Wykonaj import
            </button>
        </form>
    <?php else: ?>
        <a href="<?= url('club/backup/restore') ?>" class="btn btn-secondary">Sprobuj inny plik</a>
    <?php endif; ?>
<?php endif; ?>
