<?php use App\Helpers\View; ?>
<div class="mb-3">
    <a href="<?= url('messages') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót do skrzynki
    </a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><?= View::e($message['subject'] ?? '') ?></h5>
    </div>
</div>

<?php foreach (($thread ?? []) as $msg): ?>
    <div class="card mb-2 <?= empty($msg['read_at']) && $msg['id'] !== ($message['id'] ?? 0) ? 'border-primary' : '' ?>">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <div>
                    <strong><i class="bi bi-person-circle"></i> <?= View::e($msg['sender_name'] ?? '—') ?></strong>
                    <span class="badge bg-secondary"><?= View::e($msg['sender_type']) ?></span>
                </div>
                <small class="text-muted"><?= format_datetime($msg['created_at']) ?></small>
            </div>
            <div class="mt-2" style="white-space: pre-wrap;"><?= View::e($msg['body']) ?></div>
        </div>
    </div>
<?php endforeach; ?>

<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-reply"></i> Odpowiedz</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url('messages/store') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="parent_id" value="<?= (int)($message['parent_id'] ?: $message['id']) ?>">
            <input type="hidden" name="recipient_type" value="<?= View::e($message['sender_type']) ?>">
            <input type="hidden" name="recipient_id" value="<?= (int)$message['sender_id'] ?>">
            <input type="hidden" name="subject" value="<?= View::e('Re: ' . $message['subject']) ?>">

            <div class="mb-3">
                <textarea name="body" class="form-control" rows="4" required placeholder="Napisz odpowiedź..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-send"></i> Wyślij odpowiedź
            </button>
        </form>
    </div>
</div>
