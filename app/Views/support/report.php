<?php
use App\Helpers\View;
$typeLabels = [
    'bug'      => 'Blad',
    'feature'  => 'Propozycja zmiany',
    'question' => 'Pytanie',
    'other'    => 'Inne',
];
?>
<div class="container py-4" style="max-width: 720px;">
    <h1 class="h3 mb-3"><i class="bi bi-bug"></i> Zglos blad lub propozycje</h1>
    <p class="text-muted">Twoje zgloszenie zostanie zapisane oraz przekazane zespolowi ClubDesk.</p>

    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger"><?= View::e($flashError) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url('support/report') ?>" enctype="multipart/form-data" class="card p-4 shadow-sm">
        <?= csrf_field() ?>
        <input type="hidden" name="return" value="<?= View::e($returnUrl ?? '') ?>">
        <input type="hidden" name="url_context" value="<?= View::e($returnUrl ?? ($_SERVER['HTTP_REFERER'] ?? '')) ?>">

        <div class="mb-3">
            <label class="form-label fw-bold">Typ zgloszenia *</label>
            <div class="d-flex flex-wrap gap-3">
                <?php foreach (($allowedTypes ?? []) as $t):
                    $checked = ($t === 'bug') ? 'checked' : ''; ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="type" id="type_<?= View::e($t) ?>"
                               value="<?= View::e($t) ?>" <?= $checked ?> required>
                        <label class="form-check-label" for="type_<?= View::e($t) ?>">
                            <?= View::e($typeLabels[$t] ?? $t) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold" for="sr_title">Tytul *</label>
            <input type="text" id="sr_title" name="title" class="form-control"
                   minlength="5" maxlength="200" required
                   placeholder="Krotki opis problemu / propozycji">
            <div class="form-text">5-200 znakow</div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold" for="sr_description">Opis *</label>
            <textarea id="sr_description" name="description" class="form-control" rows="6"
                      minlength="10" maxlength="5000" required
                      placeholder="Opisz dokladnie co sie stalo lub co chcialbys zmienic. Podaj kroki do reprodukcji bledu jesli to mozliwe."></textarea>
            <div class="form-text">10-5000 znakow</div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold" for="sr_screenshot">Zrzut ekranu (opcjonalnie)</label>
            <input type="file" id="sr_screenshot" name="screenshot" class="form-control"
                   accept="image/png,image/jpeg">
            <div class="form-text">PNG lub JPG, max 5 MB</div>
            <div class="mt-2" id="sr_preview_wrap" style="display:none;">
                <img id="sr_preview" alt="podglad" style="max-width: 100%; max-height: 240px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-send"></i> Wyslij zgloszenie
            </button>
            <a href="<?= View::e($returnUrl !== '' ? $returnUrl : url('dashboard')) ?>" class="btn btn-outline-secondary">
                Anuluj
            </a>
        </div>
    </form>
</div>

<script>
(function(){
    var input = document.getElementById('sr_screenshot');
    var wrap  = document.getElementById('sr_preview_wrap');
    var img   = document.getElementById('sr_preview');
    if (!input) return;
    input.addEventListener('change', function(){
        var f = input.files && input.files[0];
        if (!f) { wrap.style.display = 'none'; return; }
        if (f.size > 5 * 1024 * 1024) {
            alert('Plik przekracza 5 MB');
            input.value = '';
            wrap.style.display = 'none';
            return;
        }
        var r = new FileReader();
        r.onload = function(e){ img.src = e.target.result; wrap.style.display = 'block'; };
        r.readAsDataURL(f);
    });
})();
</script>
