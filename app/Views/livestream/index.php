<?php use App\Helpers\View; ?>
<div class="card p-3 mb-3">
    <form method="GET" class="row g-2">
        <div class="col-md-4">
            <select name="status" class="form-select">
                <option value="">— wszystkie —</option>
                <?php foreach (['zaplanowana','na_zywo','zakonczona'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($statusFilter ?? '') === $s ? 'selected' : '' ?>><?= $s === 'na_zywo' ? 'NA ŻYWO' : $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-search"></i></button></div>
        <div class="col-md-3"><a href="<?= url('livestream/create') ?>" class="btn btn-success w-100"><i class="bi bi-plus"></i> Nowa</a></div>
    </form>
</div>
<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Tytuł</th><th>Platforma</th><th>Status</th><th>Zaplanowana</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($pagination['data'])): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Brak transmisji.</td></tr>
        <?php else: foreach ($pagination['data'] as $ls):
            $cls = match($ls['status']) { 'na_zywo'=>'danger', 'zakonczona'=>'secondary', default=>'info' };
        ?>
            <tr>
                <td><a href="<?= url('livestream/' . (int)$ls['id'] . '/watch') ?>"><?= View::e($ls['title']) ?></a>
                    <?php if ($ls['event_name']): ?><small class="text-muted d-block"><?= View::e($ls['event_name']) ?></small><?php endif; ?></td>
                <td><span class="badge bg-dark"><?= View::e($ls['platform']) ?></span></td>
                <td><span class="badge bg-<?= $cls ?>"><?= $ls['status'] === 'na_zywo' ? '● NA ŻYWO' : View::e($ls['status']) ?></span></td>
                <td><small><?= $ls['scheduled_at'] ? format_datetime($ls['scheduled_at']) : '—' ?></small></td>
                <td class="text-end">
                    <form method="POST" action="<?= url('livestream/' . (int)$ls['id'] . '/status') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <?php if ($ls['status'] === 'zaplanowana'): ?>
                            <input type="hidden" name="status" value="na_zywo">
                            <button class="btn btn-sm btn-danger"><i class="bi bi-broadcast"></i> Start</button>
                        <?php elseif ($ls['status'] === 'na_zywo'): ?>
                            <input type="hidden" name="status" value="zakonczona">
                            <button class="btn btn-sm btn-secondary"><i class="bi bi-stop-circle"></i> Zakończ</button>
                        <?php endif; ?>
                    </form>
                    <form method="POST" action="<?= url('livestream/' . (int)$ls['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')" class="d-inline">
                        <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
