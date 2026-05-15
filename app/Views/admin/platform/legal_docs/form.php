<?php
use App\Helpers\Csrf;
use App\Helpers\View;
/** @var string $docType */
/** @var string $label */
/** @var array|null $current */

$prevVer  = $current['version'] ?? '1.0';
// Suggest next minor version.
$suggest = $prevVer;
if (preg_match('/^(\d+)\.(\d+)(?:\.(\d+))?$/', $prevVer, $m)) {
    $suggest = $m[1] . '.' . ((int)$m[2] + 1);
}
?>
<a href="<?= url('admin/platform/legal-docs/' . $docType) ?>" class="text-muted small mb-2 d-inline-block">
    <i class="bi bi-arrow-left"></i> Powrót do listy wersji
</a>
<h1 class="h4 mb-3">Nowa wersja: <?= View::e($label) ?></h1>

<?php if ($current): ?>
    <div class="alert alert-info small">
        <i class="bi bi-info-circle me-1"></i>
        Bieżąca wersja: <strong><?= View::e($current['version']) ?></strong>
        (od <?= View::e(date('d.m.Y', strtotime((string)$current['effective_from']))) ?>).
        Po opublikowaniu nowej wersji poprzednia zostanie oznaczona jako archiwalna,
        a wszystkim użytkownikom zostanie wymuszona ponowna akceptacja przy następnym logowaniu
        (dotyczy typów <code>tos</code> i <code>privacy</code>).
    </div>
<?php endif; ?>

<form method="post" action="<?= url('admin/platform/legal-docs/' . $docType . '/publish') ?>">
    <?= Csrf::field() ?>

    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Numer wersji <span class="text-danger">*</span></label>
            <input type="text" name="version" class="form-control" required pattern="\d+\.\d+(\.\d+)?"
                   value="<?= View::e($suggest) ?>">
            <small class="text-muted">np. 1.0, 1.1, 2.0</small>
        </div>
        <div class="col-md-3">
            <label class="form-label">Obowiązuje od <span class="text-danger">*</span></label>
            <input type="date" name="effective_from" class="form-control" required
                   value="<?= View::e(date('Y-m-d')) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Tytuł dokumentu <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" required maxlength="200"
                   value="<?= View::e($current['title'] ?? $label) ?>">
        </div>
    </div>

    <div class="mt-3">
        <label class="form-label">Treść (Markdown) <span class="text-danger">*</span></label>
        <textarea name="body_md" class="form-control" rows="24" required
                  style="font-family: ui-monospace, 'SF Mono', Consolas, monospace; font-size: .85rem;"><?= View::e($current['body_md'] ?? '') ?></textarea>
        <small class="text-muted">
            Wspierane: nagłówki <code>#</code>..<code>######</code>, listy, tabele, <code>**bold**</code>, <code>*italic*</code>, <code>[link](url)</code>, <code>&gt; cytat</code>, <code>---</code> (poziomy separator).
        </small>
    </div>

    <div class="mt-4 d-flex justify-content-between">
        <a href="<?= url('admin/platform/legal-docs/' . $docType) ?>" class="btn btn-link text-muted">
            Anuluj
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-cloud-upload me-1"></i> Opublikuj nową wersję
        </button>
    </div>
</form>
