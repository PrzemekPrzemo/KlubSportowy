<?php use App\Helpers\View; ?>

<p class="text-muted">Zarządzaj swoimi zgodami na przetwarzanie danych. Możesz w każdej chwili udzielić lub wycofać zgodę — zmiana jest zapisywana z datą i adresem IP.</p>

<div class="row g-3">
<?php foreach ($consents as $key => $c): ?>
    <div class="col-md-6">
        <div class="card <?= $c['granted'] ? 'border-success' : '' ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="fw-semibold"><?= View::e($c['label']) ?></div>
                    <?php if ($c['granted']): ?>
                        <span class="badge bg-success">Udzielona</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Nieudzielona</span>
                    <?php endif; ?>
                </div>

                <?php if ($c['granted'] && !empty($c['granted_at'])): ?>
                    <div class="text-muted small mb-1">
                        <i class="bi bi-check-circle text-success me-1"></i>Udzielono: <?= View::e($c['granted_at']) ?>
                    </div>
                <?php endif; ?>
                <?php if (!$c['granted'] && !empty($c['revoked_at'])): ?>
                    <div class="text-muted small mb-1">
                        <i class="bi bi-x-circle text-danger me-1"></i>Wycofano: <?= View::e($c['revoked_at']) ?>
                    </div>
                <?php endif; ?>

                <div class="d-flex gap-2 mt-2">
                    <?php if (!$c['granted']): ?>
                        <form method="POST" action="<?= url('portal/consents/update') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="type" value="<?= View::e($key) ?>">
                            <input type="hidden" name="granted" value="1">
                            <button class="btn btn-success btn-sm">
                                <i class="bi bi-check-lg me-1"></i>Udziel zgody
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="<?= url('portal/consents/update') ?>"
                              onsubmit="return confirm('Wycofać tę zgodę?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="type" value="<?= View::e($key) ?>">
                            <input type="hidden" name="granted" value="0">
                            <button class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-x-lg me-1"></i>Wycofaj zgodę
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<div class="alert alert-info mt-4 small">
    <i class="bi bi-info-circle me-2"></i>
    Wycofanie zgody nie wpłynie na legalność przetwarzania danych, które miało miejsce przed jej wycofaniem.
    W przypadku pytań skontaktuj się z administratorem danych w swoim klubie.
</div>
