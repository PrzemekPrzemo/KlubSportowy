<?php use App\Helpers\View; ?>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card p-3">
            <h5><?= View::e($training['name']) ?></h5>
            <dl class="row small mb-0">
                <dt class="col-5">Start</dt>
                <dd class="col-7"><?= format_datetime($training['start_time']) ?></dd>
                <?php if (!empty($training['end_time'])): ?>
                <dt class="col-5">Koniec</dt>
                <dd class="col-7"><?= format_datetime($training['end_time']) ?></dd>
                <?php endif; ?>
                <?php if (!empty($training['location'])): ?>
                <dt class="col-5">Miejsce</dt>
                <dd class="col-7"><?= View::e($training['location']) ?></dd>
                <?php endif; ?>
                <dt class="col-5">Status</dt>
                <dd class="col-7"><span class="badge bg-info"><?= View::e($training['status']) ?></span></dd>
                <?php if ($training['max_participants']): ?>
                <dt class="col-5">Limit</dt>
                <dd class="col-7"><?= (int)$training['max_participants'] ?></dd>
                <?php endif; ?>
            </dl>
            <?php if (!empty($training['description'])): ?>
                <hr>
                <div class="small"><?= nl2br(View::e($training['description'])) ?></div>
            <?php endif; ?>
            <hr>
            <div class="d-flex gap-2">
                <a href="<?= url('trainings/' . (int)$training['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil"></i> Edytuj
                </a>
                <form method="POST" action="<?= url('trainings/' . (int)$training['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')" class="m-0">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card p-3">
            <h5 class="mb-3">Obecność (<?= count($training['attendees']) ?>)</h5>

            <form method="POST" action="<?= url('trainings/' . (int)$training['id'] . '/attendee/add') ?>" class="d-flex gap-2 mb-3">
                <?= csrf_field() ?>
                <select name="member_id" class="form-select">
                    <option value="">— wybierz zawodnika —</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary"><i class="bi bi-plus"></i> Dodaj</button>
            </form>

            <?php if (empty($training['attendees'])): ?>
                <div class="text-muted">Brak zapisanych zawodników.</div>
            <?php else: ?>
                <form method="POST" action="<?= url('trainings/' . (int)$training['id'] . '/attendance') ?>">
                    <?= csrf_field() ?>
                    <table class="table table-sm">
                        <thead><tr><th>Zawodnik</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($training['attendees'] as $a): ?>
                            <tr>
                                <td><?= View::e($a['last_name']) ?> <?= View::e($a['first_name']) ?></td>
                                <td>
                                    <select name="status[<?= (int)$a['id'] ?>]" class="form-select form-select-sm">
                                        <?php foreach (['zapisany','obecny','nieobecny','spozniony','wypisany'] as $s): ?>
                                            <option value="<?= $s ?>" <?= $a['status']===$s?'selected':'' ?>><?= $s ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="text-end">
                                    <a href="<?= url('trainings/' . (int)$training['id'] . '/attendee/' . (int)$a['id'] . '/remove') ?>"
                                       onclick="event.preventDefault();if(confirm('Usunąć?'))this.nextElementSibling.submit();"
                                       class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></a>
                                    <form method="POST" action="<?= url('trainings/' . (int)$training['id'] . '/attendee/' . (int)$a['id'] . '/remove') ?>" style="display:none"><?= csrf_field() ?></form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz obecność</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
