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
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= url('members/' . (int)$member['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil"></i> Edytuj
                </a>
                <?php if (!empty($isSuperAdmin)): ?>
                    <form method="POST" action="<?= url('admin/clubs/' . (int)$member['club_id'] . '/members/' . (int)$member['id'] . '/impersonate-member') ?>" class="m-0">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-warning" title="Zaloguj się do portalu jako ten zawodnik">
                            <i class="bi bi-person-badge"></i> Portal zawodnika
                        </button>
                    </form>
                <?php endif; ?>
                <form method="POST" action="<?= url('members/' . (int)$member['id'] . '/delete') ?>"
                      onsubmit="return confirm('Usunąć zawodnika?')" class="m-0">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>

        <div class="card p-3 mt-3">
            <h6><i class="bi bi-key"></i> Dostęp do portalu</h6>
            <?php if (!empty($member['portal_password'])): ?>
                <div class="alert alert-success py-2 small mb-2">
                    Konto aktywne
                    <?php if (!empty($member['portal_last_login'])): ?>
                        <br>ostatnie logowanie: <?= format_datetime($member['portal_last_login']) ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-secondary py-2 small mb-2">Brak konta portalu</div>
            <?php endif; ?>
            <form method="POST" action="<?= url('members/' . (int)$member['id'] . '/portal-password') ?>">
                <?= csrf_field() ?>
                <div class="input-group input-group-sm">
                    <input type="password" name="portal_password" class="form-control" placeholder="Nowe hasło (min 8 znaków)" minlength="8" required>
                    <button class="btn btn-warning">Ustaw</button>
                </div>
                <small class="text-muted d-block mt-1">Przekaż hasło zawodnikowi bezpiecznym kanałem.</small>
            </form>
        </div>

        <?php if (\App\Helpers\Auth::hasRole(['zarzad', 'admin']) && \App\Helpers\Feature::enabled('inpost_shipping')): ?>
        <div class="card p-3 mt-3">
            <h6><i class="bi bi-truck"></i> Wysyłka InPost</h6>
            <p class="small text-muted mb-2">
                Utwórz przesyłkę paczkomatową lub kuriera dla tego zawodnika.
                Dane odbiorcy zostaną pre-wypełnione z karty.
            </p>
            <a href="<?= url('club/shipping/create?member_id=' . (int)$member['id']) ?>"
               class="btn btn-sm btn-outline-primary w-100">
                <i class="bi bi-box-arrow-up-right"></i> Utwórz przesyłkę
            </a>
        </div>
        <?php endif; ?>

        <div class="card p-3 mt-3">
            <h6><i class="bi bi-file-earmark-pdf"></i> Dokumenty PDF</h6>
            <div class="d-grid gap-2">
                <a href="<?= url('documents/membership/' . (int)$member['id']) ?>"
                   class="btn btn-sm btn-outline-primary" target="_blank">
                    <i class="bi bi-file-earmark-text"></i> Zaświadczenie członkostwa
                </a>
                <a href="<?= url('documents/contract/' . (int)$member['id']) ?>"
                   class="btn btn-sm btn-outline-primary" target="_blank">
                    <i class="bi bi-file-earmark-text"></i> Umowa członkowska
                </a>
                <form method="GET" action="<?= url('documents/certificate/' . (int)$member['id']) ?>"
                      target="_blank" class="m-0">
                    <div class="input-group input-group-sm">
                        <input type="text" name="achievement" class="form-control"
                               placeholder="Osiągnięcie (np. I miejsce w turnieju)" required>
                        <button class="btn btn-outline-success">
                            <i class="bi bi-award"></i> Certyfikat
                        </button>
                    </div>
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
