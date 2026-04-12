<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link <?= ($tab ?? 'inbox') === 'inbox' ? 'active' : '' ?>" href="<?= url('messages') ?>">
                <i class="bi bi-inbox"></i> Odebrane
                <?php if (!empty($unread)): ?>
                    <span class="badge bg-danger"><?= (int)$unread ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($tab ?? '') === 'sent' ? 'active' : '' ?>" href="<?= url('messages/sent') ?>">
                <i class="bi bi-send"></i> Wysłane
            </a>
        </li>
    </ul>
    <a href="<?= url('messages/compose') ?>" class="btn btn-success">
        <i class="bi bi-pencil-square"></i> Nowa wiadomość
    </a>
</div>

<?php if (empty($pagination['data'])): ?>
    <div class="card p-4 text-center text-muted">Brak wiadomości.</div>
<?php else: ?>
    <div class="card">
        <div class="list-group list-group-flush">
            <?php foreach ($pagination['data'] as $msg):
                $isUnread = ($tab ?? 'inbox') === 'inbox' && empty($msg['read_at']);
            ?>
                <a href="<?= url('messages/' . (int)$msg['id']) ?>"
                   class="list-group-item list-group-item-action <?= $isUnread ? 'fw-bold bg-light' : '' ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <?php if ($isUnread): ?>
                                <span class="badge bg-primary me-1">Nowa</span>
                            <?php endif; ?>
                            <strong><?= View::e($msg['subject']) ?></strong>
                            <div class="small text-muted mt-1">
                                <?php if (($tab ?? 'inbox') === 'inbox'): ?>
                                    Od: <?= View::e($msg['sender_name'] ?? '—') ?>
                                <?php else: ?>
                                    Do: <?= View::e($msg['recipient_name'] ?? '—') ?>
                                <?php endif; ?>
                                <?php if (!empty($msg['reply_count'])): ?>
                                    &middot; <i class="bi bi-chat-dots"></i> <?= (int)$msg['reply_count'] ?> odpowiedzi
                                <?php endif; ?>
                            </div>
                        </div>
                        <small class="text-muted text-nowrap ms-3">
                            <?= format_datetime($msg['created_at']) ?>
                        </small>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($pagination['last_page'] > 1): ?>
        <nav class="mt-3">
            <ul class="pagination justify-content-center">
                <?php for ($p = 1; $p <= $pagination['last_page']; $p++): ?>
                    <li class="page-item <?= $p === $pagination['current_page'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url('messages' . (($tab ?? 'inbox') === 'sent' ? '/sent' : '') . '?page=' . $p) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>
