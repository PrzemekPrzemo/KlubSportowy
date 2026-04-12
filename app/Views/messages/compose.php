<?php use App\Helpers\View; ?>
<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= url('messages/store') ?>">
            <?= csrf_field() ?>

            <?php if (!empty($parent)): ?>
                <input type="hidden" name="parent_id" value="<?= (int)$parent['id'] ?>">
                <div class="alert alert-secondary mb-3">
                    <strong>Odpowiedź na:</strong> <?= View::e($parent['subject']) ?>
                </div>
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Typ odbiorcy</label>
                    <select name="recipient_type" id="recipientType" class="form-select" onchange="toggleRecipient()">
                        <option value="user">Użytkownik</option>
                        <option value="member">Zawodnik</option>
                        <option value="group">Grupa</option>
                    </select>
                </div>
                <div class="col-md-4" id="recipientUserWrap">
                    <label class="form-label">Odbiorca (użytkownik)</label>
                    <select name="recipient_id" id="recipientUser" class="form-select">
                        <option value="">— wybierz —</option>
                        <?php foreach (($users ?? []) as $u): ?>
                            <option value="<?= (int)$u['id'] ?>"><?= View::e($u['full_name']) ?> (<?= View::e($u['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-none" id="recipientMemberWrap">
                    <label class="form-label">Odbiorca (zawodnik)</label>
                    <select name="recipient_id_member" id="recipientMember" class="form-select">
                        <option value="">— wybierz —</option>
                        <?php foreach (($members ?? []) as $m): ?>
                            <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-none" id="groupScopeWrap">
                    <label class="form-label">Zakres grupy</label>
                    <select name="group_scope" class="form-select">
                        <option value="club">Cały klub</option>
                        <option value="sport">Sekcja sportowa</option>
                        <option value="team">Drużyna</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Temat</label>
                <input type="text" name="subject" class="form-control" required maxlength="200"
                       value="<?= !empty($parent) ? View::e('Re: ' . $parent['subject']) : '' ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Treść</label>
                <textarea name="body" class="form-control" rows="8" required></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Wyślij</button>
                <a href="<?= url('messages') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleRecipient() {
    var type = document.getElementById('recipientType').value;
    document.getElementById('recipientUserWrap').classList.toggle('d-none', type !== 'user');
    document.getElementById('recipientMemberWrap').classList.toggle('d-none', type !== 'member');
    document.getElementById('groupScopeWrap').classList.toggle('d-none', type !== 'group');
    // Synchronize the correct recipient_id value
    if (type === 'member') {
        document.getElementById('recipientUser').name = '_disabled_user';
        document.getElementById('recipientMember').name = 'recipient_id';
    } else {
        document.getElementById('recipientUser').name = 'recipient_id';
        document.getElementById('recipientMember').name = '_disabled_member';
    }
}
</script>
