<?php
use App\Helpers\Csrf;
use App\Helpers\View;
?>
<div class="container py-3">
    <a href="<?= View::e(url('members/' . (int)$member['id'])) ?>" class="text-decoration-none small">
        <i class="bi bi-arrow-left"></i> Wroc do profilu czlonka
    </a>

    <h1 class="h4 mt-2">Opiekunowie: <?= View::e(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')) ?></h1>
    <p class="text-muted small">
        Lista opiekunow uprawnionych do portalu rodzica dla tego dziecka.
        Klub moze zapraszac kolejnych opiekunow lub odpinac istniejacych.
    </p>

    <?php include __DIR__ . '/invite_modal.php'; ?>

    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#inviteGuardianModal">
        <i class="bi bi-person-plus"></i> Zapros opiekuna
    </button>

    <?php if (empty($guardians)): ?>
        <div class="alert alert-warning">
            Brak opiekunow przypisanych do tego dziecka.
            <?php
            $age = null;
            if (!empty($member['birth_date'])) {
                try {
                    $bd = new DateTime($member['birth_date']);
                    $age = (int)$bd->diff(new DateTime())->y;
                } catch (\Throwable) {}
            }
            ?>
            <?php if ($age !== null && $age < 16): ?>
                <strong class="text-danger d-block mt-2">
                    UWAGA: dziecko ma <?= (int)$age ?> lat — RODO art. 8 wymaga zgody opiekuna.
                </strong>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Opiekun</th>
                        <th>E-mail</th>
                        <th>Relacja</th>
                        <th>Uprawnienia</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($guardians as $g): ?>
                        <tr>
                            <td>
                                <?= View::e(trim(($g['g_first_name'] ?? '') . ' ' . ($g['g_last_name'] ?? ''))) ?>
                                <?php if (!empty($g['primary_guardian'])): ?>
                                    <span class="badge bg-primary ms-1">Glowny</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="mailto:<?= View::e($g['email']) ?>"><?= View::e($g['email']) ?></a>
                            </td>
                            <td><?= View::e(ucfirst((string)($g['relationship'] ?? 'parent'))) ?></td>
                            <td>
                                <?php if (!empty($g['can_pay'])): ?><span class="badge bg-success">Platnosci</span><?php endif; ?>
                                <?php if (!empty($g['can_consent'])): ?><span class="badge bg-info">Zgody</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($g['email_verified_at'])): ?>
                                    <span class="badge bg-success">Aktywny</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Zaproszony</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <form method="post" action="<?= View::e(url('club/guardians/' . (int)$g['id'] . '/remove')) ?>"
                                      onsubmit="return confirm('Odpiac opiekuna od dziecka? Konto opiekuna zostanie zachowane.');" class="d-inline">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
