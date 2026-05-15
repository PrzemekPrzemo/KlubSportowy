<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-plus-circle text-primary me-2"></i>
        Nowa kampania
    </h3>
    <a href="<?= url('admin/campaigns') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('warning')): ?>
    <div class="alert alert-warning"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="card p-4">
    <form method="POST" action="<?= url('admin/campaigns/send') ?>">
        <?= csrf_field() ?>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Nazwa kampanii *</label>
                <input type="text" name="name" class="form-control" required maxlength="120"
                       placeholder="np. Przypomnienie składki — luty 2026">
            </div>
            <div class="col-md-3">
                <label class="form-label">Kanał *</label>
                <select name="channel" class="form-select" required>
                    <option value="email">E-mail</option>
                    <option value="sms">SMS</option>
                    <option value="both">E-mail + SMS</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Zaplanuj (opcjonalne)</label>
                <input type="datetime-local" name="schedule_at" class="form-control">
                <small class="text-muted">Puste → wyślij teraz</small>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Temat (dla email)</label>
            <input type="text" name="subject" class="form-control" maxlength="200"
                   placeholder="Przypomnienie o opłacie składki">
        </div>

        <div class="mb-3">
            <label class="form-label">Treść wiadomości *</label>
            <textarea name="body" rows="8" class="form-control" required
                      placeholder="Cześć {{first_name}},&#10;&#10;Przypominamy o opłacie składki członkowskiej za bieżący miesiąc.&#10;Pozdrawiamy,&#10;Zarząd klubu"></textarea>
            <small class="text-muted">
                Placeholdery: <code>{{first_name}}</code>, <code>{{last_name}}</code>, <code>{{member_number}}</code>
            </small>
        </div>

        <h5>Filtr odbiorców</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label">Sekcja sportowa</label>
                <select name="sport_id" class="form-select">
                    <option value="">Wszystkie</option>
                    <?php foreach (($clubSports ?? []) as $cs): ?>
                        <option value="<?= (int)$cs['club_sport_id'] ?>"><?= View::e($cs['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Wszyscy</option>
                    <option value="aktywny" selected>Aktywni</option>
                    <option value="zawieszony">Zawieszeni</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Składki</label>
                <select name="fees" class="form-select">
                    <option value="">Bez filtra</option>
                    <option value="overdue">Tylko z zaległościami</option>
                    <option value="paid">Tylko opłaceni (90 dni)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Płeć</label>
                <select name="gender" class="form-select">
                    <option value="">Obie</option>
                    <option value="M">M</option>
                    <option value="K">K</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Wiek od</label>
                <input type="number" name="age_min" min="0" max="120" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Wiek do</label>
                <input type="number" name="age_max" min="0" max="120" class="form-control">
            </div>
        </div>

        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            Po kliknięciu "Wyślij" odbiorcy zostaną zakolejkowani. Wysłka faktyczna idzie przez worker
            (cli/send_campaigns.php — uruchamiany przez cron co 5 min).
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('admin/campaigns') ?>" class="btn btn-outline-secondary">Anuluj</a>
            <button class="btn btn-primary" onclick="return confirm('Utworzyć kampanię i zakolejkować odbiorców?')">
                <i class="bi bi-send"></i> Wyślij kampanię
            </button>
        </div>
    </form>
</div>
