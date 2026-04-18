<?php use App\Helpers\View; ?>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card p-3">
            <h5>#<?= (int)$ticket['id'] ?> <?= View::e($ticket['subject']) ?></h5>
            <dl class="row small mb-0">
                <dt class="col-5">Klub</dt><dd class="col-7"><?= View::e($ticket['club_name'] ?? '—') ?></dd>
                <dt class="col-5">Autor</dt><dd class="col-7"><?= View::e($ticket['author_name'] ?? '—') ?></dd>
                <dt class="col-5">Priorytet</dt><dd class="col-7"><span class="badge bg-<?= $ticket['priority']==='urgent'?'danger':'secondary' ?>"><?= View::e($ticket['priority']) ?></span></dd>
                <dt class="col-5">Kategoria</dt><dd class="col-7"><?= View::e($ticket['category']) ?></dd>
                <dt class="col-5">Status</dt><dd class="col-7"><span class="badge bg-<?= $ticket['status']==='open'?'danger':'info' ?>"><?= View::e($ticket['status']) ?></span></dd>
                <dt class="col-5">Utworzono</dt><dd class="col-7"><?= format_datetime($ticket['created_at']) ?></dd>
            </dl>
            <hr>
            <div class="small"><?= nl2br(View::e($ticket['body'])) ?></div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card p-3">
            <h5>Odpowiedzi</h5>
            <?php foreach ($replies as $r): ?>
                <div class="border rounded p-2 mb-2">
                    <strong class="small"><?= View::e($r['author_name'] ?? 'System') ?></strong>
                    <small class="text-muted float-end"><?= format_datetime($r['created_at']) ?></small>
                    <div class="mt-1"><?= nl2br(View::e($r['body'])) ?></div>
                </div>
            <?php endforeach; ?>
            <hr>
            <form method="POST" action="<?= url('admin/platform/support/' . (int)$ticket['id'] . '/reply') ?>">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <textarea name="body" class="form-control" rows="4" placeholder="Napisz odpowiedź..." required></textarea>
                </div>
                <div class="d-flex gap-2">
                    <select name="status" class="form-select" style="max-width:200px">
                        <option value="in_progress">W trakcie</option>
                        <option value="waiting">Oczekujące</option>
                        <option value="closed">Zamknięte</option>
                    </select>
                    <button class="btn btn-primary"><i class="bi bi-send"></i> WYŚLIJ</button>
                </div>
            </form>
        </div>
    </div>
</div>
