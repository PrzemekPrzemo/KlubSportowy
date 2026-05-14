<?php use App\Helpers\View; ?>
<div class="card p-3 mb-3">
    <h5 class="mb-3"><i class="bi bi-broadcast"></i> Live updates — zarządzanie kanałami</h5>
    <p class="text-muted small">
        Kanały Server-Sent Events (SSE) dla real-time wyników. Subskrybuj kanał w przeglądarce:
        <code>/live/stream/&lt;channel&gt;</code>. Klient użyje <code>EventSource</code>.
    </p>
    <form method="POST" action="<?= url('live/admin/create') ?>" class="row g-2">
        <?= csrf_field() ?>
        <div class="col-md-3">
            <input name="channel" class="form-control" placeholder="np. match:42" required pattern="[a-zA-Z0-9:_\-]{3,60}">
        </div>
        <div class="col-md-4">
            <input name="title" class="form-control" placeholder="Tytuł meczu/eventu" required>
        </div>
        <div class="col-md-2">
            <input name="sport_key" class="form-control" placeholder="sport_key (opc.)">
        </div>
        <div class="col-md-2 d-flex align-items-center">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="is_public" id="is_public" value="1">
                <label for="is_public" class="form-check-label">Publiczny</label>
            </div>
        </div>
        <div class="col-md-1">
            <button class="btn btn-success w-100"><i class="bi bi-plus"></i></button>
        </div>
    </form>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Channel</th>
                <th>Tytuł</th>
                <th>Sport</th>
                <th>Status</th>
                <th>Public</th>
                <th>Started</th>
                <th>Last update</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($all)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Brak kanałów.</td></tr>
        <?php else: foreach ($all as $ch):
            $statusCls = match($ch['status']) { 'live'=>'danger', 'finished'=>'secondary', default=>'info' };
        ?>
            <tr>
                <td><code><?= View::e($ch['channel']) ?></code></td>
                <td><?= View::e($ch['title']) ?></td>
                <td><?= View::e($ch['sport_key'] ?? '—') ?></td>
                <td><span class="badge bg-<?= $statusCls ?>"><?= $ch['status'] === 'live' ? '● LIVE' : View::e($ch['status']) ?></span></td>
                <td><?= (int)$ch['is_public'] === 1 ? '<i class="bi bi-globe text-success"></i>' : '<i class="bi bi-lock text-muted"></i>' ?></td>
                <td><small><?= $ch['started_at'] ? format_datetime($ch['started_at']) : '—' ?></small></td>
                <td><small><?= $ch['last_update_at'] ? format_datetime($ch['last_update_at']) : '—' ?></small></td>
                <td class="text-end">
                    <?php if ($ch['status'] === 'scheduled'): ?>
                        <form method="POST" action="<?= url('live/admin/start/' . (int)$ch['id']) ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-danger" title="Start"><i class="bi bi-broadcast"></i></button>
                        </form>
                    <?php elseif ($ch['status'] === 'live'): ?>
                        <a href="<?= url('live/stream/' . urlencode($ch['channel'])) ?>" target="_blank" class="btn btn-sm btn-info" title="Stream raw"><i class="bi bi-eye"></i></a>
                        <form method="POST" action="<?= url('live/admin/end/' . (int)$ch['id']) ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-secondary" title="Zakończ"><i class="bi bi-stop-circle"></i></button>
                        </form>
                    <?php endif; ?>
                    <form method="POST" action="<?= url('live/admin/delete/' . (int)$ch['id']) ?>" class="d-inline" onsubmit="return confirm('Usunąć kanał?');">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger" title="Usuń"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php if ($ch['status'] === 'live'): ?>
                <tr>
                    <td colspan="8" class="bg-light">
                        <?= View::partial('live/_widget', ['channel' => $ch['channel'], 'title' => $ch['title']]) ?>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
