<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-trophy text-primary me-2"></i>
        Integracje z federacjami sportowymi
    </h3>
    <a href="<?= url('federation') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Stara wersja (lookup licencji)
    </a>
</div>

<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Każdy klub może mieć własne credentials</strong> do federacji
    (PZPN/PZSS/PZKosz/PZLA). Pozwala automatyczne zgłaszanie zawodników,
    aktualizacje danych i odnawianie licencji. Wrażliwe pola (login/hasło/token)
    są szyfrowane AES-256-GCM przed zapisem do bazy. Federacje bez API obsługujemy
    przez eksport CSV (manualny import w panelu federacji).
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($supported as $code => $label):
        $row = $existing[$code] ?? null;
        $isConfigured = $row !== null;
        $isActive     = $isConfigured && !empty($row['is_active']);
        $hasCreds     = $isConfigured && (!empty($row['has_username']) || !empty($row['has_token']));
    ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 <?= $isActive ? 'border-success' : '' ?>">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-shield-check text-primary fs-1 me-3"></i>
                        <div class="flex-grow-1">
                            <h5 class="mb-0"><?= View::e($code) ?></h5>
                            <small class="text-muted"><?= View::e($label) ?></small>
                            <div class="mt-1">
                                <?php if ($isActive): ?>
                                    <span class="badge bg-success">Aktywna</span>
                                <?php elseif ($isConfigured): ?>
                                    <span class="badge bg-secondary">Nieaktywna</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border">Nieskonfigurowana</span>
                                <?php endif; ?>
                                <?php if (!empty($row['is_sandbox'])): ?>
                                    <span class="badge bg-warning text-dark">Sandbox</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($isConfigured): ?>
                        <ul class="list-unstyled small mb-3">
                            <?php if (!empty($row['organization_id'])): ?>
                                <li><strong>Org. ID:</strong> <code><?= View::e($row['organization_id']) ?></code></li>
                            <?php endif; ?>
                            <li><strong>Login:</strong>
                                <?php if (!empty($row['has_username'])): ?>
                                    <span class="text-success"><i class="bi bi-check2"></i> ustawiony</span>
                                <?php else: ?>
                                    <span class="text-muted">brak</span>
                                <?php endif; ?>
                            </li>
                            <li><strong>Token API:</strong>
                                <?php if (!empty($row['has_token'])): ?>
                                    <span class="text-success"><i class="bi bi-check2"></i> ustawiony</span>
                                <?php else: ?>
                                    <span class="text-muted">brak</span>
                                <?php endif; ?>
                            </li>
                            <?php if (!empty($row['last_export_at'])): ?>
                                <li><strong>Ostatni eksport:</strong>
                                    <?= View::e($row['last_export_at']) ?>
                                    (<?= View::e($row['last_export_status'] ?? '—') ?>)
                                </li>
                            <?php endif; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted small mb-3">
                            Skonfiguruj credentials, aby eksportować zawodników do federacji.
                        </p>
                    <?php endif; ?>

                    <div class="d-flex gap-1">
                        <a href="<?= url('club/federations/' . $code . '/edit') ?>"
                           class="btn btn-primary btn-sm flex-grow-1">
                            <i class="bi bi-<?= $isConfigured ? 'pencil' : 'plus-circle' ?>"></i>
                            <?= $isConfigured ? 'Edytuj' : 'Skonfiguruj' ?>
                        </a>
                        <?php if ($isConfigured): ?>
                            <form method="POST" action="<?= url('club/federations/' . $code . '/toggle') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-<?= $isActive ? 'warning' : 'success' ?> btn-sm"
                                        title="<?= $isActive ? 'Dezaktywuj' : 'Aktywuj' ?>">
                                    <i class="bi bi-<?= $isActive ? 'pause' : 'play' ?>"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="mt-4 alert alert-secondary small">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Adaptery STUB.</strong> Aktualnie zaimplementowane są sygnatury
    + sanity-check credentials. Pełna integracja z API/portalami federacji
    wymaga osobnych ticketów (każda federacja ma inną dokumentację, niektóre
    są zamknięte za logowaniem). Fallback dla federacji bez API: eksport CSV.
</div>
