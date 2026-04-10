<?php use App\Helpers\View; ?>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card p-3">
            <h5><?= View::e($member['first_name']) ?> <?= View::e($member['last_name']) ?></h5>
            <div class="text-muted small mb-2">#<?= View::e($member['member_number']) ?></div>
            <dl class="row small mb-0">
                <dt class="col-5">Status</dt>
                <dd class="col-7"><span class="badge bg-<?= $member['status']==='aktywny'?'success':'secondary' ?>"><?= View::e($member['status']) ?></span></dd>
                <dt class="col-5">Data wstąpienia</dt>
                <dd class="col-7"><?= format_date($member['join_date']) ?></dd>
                <?php if (!empty($member['birth_date'])): ?>
                <dt class="col-5">Data urodzenia</dt>
                <dd class="col-7"><?= format_date($member['birth_date']) ?></dd>
                <?php endif; ?>
                <?php if (!empty($member['pesel'])): ?>
                <dt class="col-5">PESEL</dt>
                <dd class="col-7"><?= View::e($member['pesel']) ?></dd>
                <?php endif; ?>
                <?php if (!empty($member['email'])): ?>
                <dt class="col-5">E-mail</dt>
                <dd class="col-7"><?= View::e($member['email']) ?></dd>
                <?php endif; ?>
                <?php if (!empty($member['phone'])): ?>
                <dt class="col-5">Telefon</dt>
                <dd class="col-7"><?= View::e($member['phone']) ?></dd>
                <?php endif; ?>
            </dl>
            <hr>
            <div class="d-flex gap-2">
                <a href="<?= url('members/' . (int)$member['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil"></i> Edytuj
                </a>
                <form method="POST" action="<?= url('members/' . (int)$member['id'] . '/delete') ?>"
                      onsubmit="return confirm('Usunąć zawodnika?')" class="m-0">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card p-3">
            <h5 class="mb-3">Sekcje sportowe</h5>
            <?php if (empty($member['sports'])): ?>
                <div class="text-muted">Zawodnik nie jest przypisany do żadnej sekcji.</div>
            <?php else: ?>
                <?php foreach ($member['sports'] as $s): ?>
                    <div class="border rounded p-2 mb-2 d-flex justify-content-between">
                        <div>
                            <strong style="color: <?= View::e($s['color']) ?>">
                                <i class="bi <?= View::e($s['icon']) ?>"></i>
                                <?= View::e($s['sport_name']) ?>
                            </strong>
                            <small class="text-muted d-block">
                                <?php if (!empty($s['class_name'])): ?>
                                    Klasa: <?= View::e($s['class_name']) ?>
                                <?php endif; ?>
                                <?php if (!empty($s['discipline_name'])): ?>
                                    • <?= View::e($s['discipline_name']) ?>
                                <?php endif; ?>
                                <?php if (!empty($s['position'])): ?>
                                    • pozycja: <?= View::e($s['position']) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <small class="text-muted">od <?= format_date($s['joined_at']) ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
