<?php
use App\Helpers\View;

$scheduleLabels = [
    'weekly_mon'  => 'Tygodniowy (pon. 08:00)',
    'weekly_fri'  => 'Tygodniowy (pt. 08:00)',
    'monthly_1st' => 'Miesieczny (1. dzien 08:00)',
    'quarterly'   => 'Kwartalny (1. dzien kwartalu)',
];
$templateLabels = [
    'full_dashboard' => 'Pelny dashboard',
    'club_summary'   => 'Podsumowanie klubu',
    'financial'      => 'Finansowy',
    'attendance'     => 'Frekwencja',
];
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h3 class="mb-0"><i class="bi bi-envelope-paper text-primary me-2"></i>Raporty zaplanowane</h3>
        <small class="text-muted">Cykliczne PDF dashboardy wysylane do zarzadu (weekly / monthly / quarterly).</small>
    </div>
    <a href="<?= url('club/scheduled-reports/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nowy raport
    </a>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= View::e($flashError) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($reports)): ?>
            <div class="p-4 text-center text-muted">
                Brak zaplanowanych raportow.
                <a href="<?= url('club/scheduled-reports/create') ?>">Utworz pierwszy</a>.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nazwa</th>
                            <th>Harmonogram</th>
                            <th>Szablon</th>
                            <th>Adresaci</th>
                            <th>Ostatnio</th>
                            <th>Nastepna wysylka</th>
                            <th>Status</th>
                            <th class="text-end">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reports as $r): ?>
                        <tr>
                            <td><strong><?= View::e($r['name']) ?></strong></td>
                            <td><span class="badge bg-secondary"><?= View::e($scheduleLabels[$r['cron_schedule']] ?? $r['cron_schedule']) ?></span></td>
                            <td><span class="badge bg-info"><?= View::e($templateLabels[$r['template']] ?? $r['template']) ?></span></td>
                            <td><span class="small text-muted"><?= count($r['recipients']) ?> adresat(ow)</span></td>
                            <td class="small"><?= $r['last_sent_at'] ? View::e($r['last_sent_at']) : '<span class="text-muted">—</span>' ?></td>
                            <td class="small"><?= $r['next_send_at'] ? View::e($r['next_send_at']) : '<span class="text-muted">—</span>' ?></td>
                            <td>
                                <?php if ((int)$r['active'] === 1): ?>
                                    <span class="badge bg-success">Aktywny</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Wylaczony</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('club/scheduled-reports/' . (int)$r['id'] . '/preview') ?>"
                                   class="btn btn-sm btn-outline-primary" title="Pobierz teraz (bez wysylki)" target="_blank">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url('club/scheduled-reports/' . (int)$r['id'] . '/runs') ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Historia">
                                    <i class="bi bi-clock-history"></i>
                                </a>
                                <a href="<?= url('club/scheduled-reports/' . (int)$r['id'] . '/edit') ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Edytuj">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="<?= url('club/scheduled-reports/' . (int)$r['id'] . '/delete') ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Usunac raport &quot;<?= View::e($r['name']) ?>&quot;?');">
                                    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                                    <button class="btn btn-sm btn-outline-danger" title="Usun"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
