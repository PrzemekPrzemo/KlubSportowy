<?php
use App\Helpers\View;

$r = $report; // edit mode = array, create = null
$isEdit = $r !== null;
$scheduleLabels = [
    'weekly_mon'  => 'Tygodniowy (poniedzialek 08:00)',
    'weekly_fri'  => 'Tygodniowy (piatek 08:00)',
    'monthly_1st' => 'Miesieczny (1. dzien miesiaca 08:00)',
    'quarterly'   => 'Kwartalny (1. dzien kwartalu Jan/Apr/Jul/Oct 08:00)',
];
$templateLabels = [
    'full_dashboard' => 'Pelny dashboard (KPI + frekwencja + eventy + top trenerow)',
    'club_summary'   => 'Podsumowanie klubu (top 3 KPI + eventy)',
    'financial'      => 'Finansowy (wplywy + zaleglosci + split metod)',
    'attendance'     => 'Frekwencja (per sekcja + alerty)',
];
$config = $r['config'] ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-envelope-paper text-primary me-2"></i><?= $isEdit ? 'Edycja raportu' : 'Nowy zaplanowany raport' ?></h3>
    <a href="<?= url('club/scheduled-reports') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Powrot</a>
</div>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= View::e($flashError) ?></div>
<?php endif; ?>

<form method="post" action="<?= url('club/scheduled-reports/store') ?>" class="card">
    <div class="card-body">
        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Nazwa raportu <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required maxlength="200"
                       value="<?= View::e($r['name'] ?? '') ?>"
                       placeholder="np. Raport tygodniowy zarzadu">
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <div class="form-check form-switch pt-2">
                    <input type="checkbox" class="form-check-input" name="active" id="active" value="1"
                           <?= (!$isEdit || (int)($r['active'] ?? 1) === 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="active">Aktywny (cron wysyla)</label>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Harmonogram <span class="text-danger">*</span></label>
                <select name="cron_schedule" class="form-select" required>
                    <?php foreach ($schedules as $s): ?>
                        <option value="<?= View::e($s) ?>" <?= ($r['cron_schedule'] ?? '') === $s ? 'selected' : '' ?>>
                            <?= View::e($scheduleLabels[$s] ?? $s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Szablon raportu <span class="text-danger">*</span></label>
                <select name="template" class="form-select" required>
                    <?php foreach ($templates as $t): ?>
                        <option value="<?= View::e($t) ?>" <?= ($r['template'] ?? 'full_dashboard') === $t ? 'selected' : '' ?>>
                            <?= View::e($templateLabels[$t] ?? $t) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Adresaci email <span class="text-danger">*</span></label>
                <textarea name="recipients" class="form-control" rows="3"
                          placeholder="Jeden email na linie, lub rozdzielone przecinkami"><?= View::e($r['recipients_text'] ?? '') ?></textarea>
                <small class="form-text text-muted">PDF zostanie wyslany jako zalacznik (max 5 MB).</small>
            </div>

            <div class="col-12">
                <label class="form-label">Sekcje opcjonalne</label>
                <div class="row g-2">
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="include_kpi" id="ck_kpi"
                                   <?= !empty($config['include_kpi']) || !$isEdit ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ck_kpi">Karty KPI</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="include_events" id="ck_ev"
                                   <?= !empty($config['include_events']) || !$isEdit ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ck_ev">Nadchodzace eventy</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="include_trainers" id="ck_tr"
                                   <?= !empty($config['include_trainers']) || !$isEdit ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ck_tr">Top trenerzy</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="include_overdue" id="ck_ov"
                                   <?= !empty($config['include_overdue']) || !$isEdit ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ck_ov">Zaleglosci (zanonimizowane)</label>
                        </div>
                    </div>
                </div>
                <small class="text-muted">Te opcje sa zapamietane w config_json (rozszerzalne).</small>
            </div>
        </div>
    </div>
    <div class="card-footer text-end">
        <a href="<?= url('club/scheduled-reports') ?>" class="btn btn-outline-secondary">Anuluj</a>
        <button class="btn btn-primary"><i class="bi bi-save"></i> Zapisz</button>
    </div>
</form>
