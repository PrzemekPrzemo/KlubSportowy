<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-envelope-paper text-primary me-2"></i>
        Wyślij wiadomość do <?= count($recipients) ?> zawodników
    </h3>
    <a href="<?= url('members') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Anuluj i wróć
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<?php if ((int)$totalIds !== count($recipients)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        Z <?= (int)$totalIds ?> zaznaczonych zawodników, tylko
        <?= count($recipients) ?> ma adres email.
        Pozostali zostaną pominięci. Uzupełnij e-maile w profilach jeśli chcesz dotrzeć do wszystkich.
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-8">
        <form method="POST" action="<?= url('members/bulk-message/send') ?>" class="card p-4">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Temat wiadomości *</label>
                <input type="text" name="subject" class="form-control" required maxlength="200"
                       placeholder="np. Przypomnienie o składce za luty 2026">
            </div>

            <div class="mb-3">
                <label class="form-label">Treść wiadomości *</label>
                <textarea name="body" class="form-control" rows="10" required
                          placeholder="Drogi {{first_name}},&#10;&#10;Przypominamy o opłaceniu składki członkowskiej...&#10;&#10;Pozdrawiamy,&#10;Zarząd klubu"></textarea>
                <small class="text-muted">
                    Wskazówki:
                    <code>{{first_name}}</code> → imię odbiorcy ·
                    <code>{{last_name}}</code> → nazwisko ·
                    <code>{{member_number}}</code> → numer członkowski.<br>
                    Wiadomość zostanie wysłana z brandingiem klubu (HTML email z logo).
                </small>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= url('members') ?>" class="btn btn-outline-secondary">Anuluj</a>
                <button type="submit" class="btn btn-primary"
                        onclick="return confirm('Wysłać <?= count($recipients) ?> wiadomości?')">
                    <i class="bi bi-send"></i> Wyślij wiadomości
                </button>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card p-3">
            <h6 class="mb-2"><i class="bi bi-people"></i> Odbiorcy (<?= count($recipients) ?>)</h6>
            <div style="max-height: 400px; overflow-y: auto; font-size: .85rem;">
                <?php foreach ($recipients as $r): ?>
                    <div class="border-bottom py-1">
                        <strong><?= View::e($r['name']) ?></strong>
                        <small class="text-muted d-block"><?= View::e($r['email']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
