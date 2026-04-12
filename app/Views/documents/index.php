<?php use App\Helpers\View; ?>

<div class="row mb-4">
    <div class="col-12">
        <p class="text-muted">Wybierz zawodnika i wygeneruj odpowiedni dokument w formacie PDF.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Umowa członkowska -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-file-earmark-text display-4 text-primary mb-3 d-block"></i>
                <h5 class="card-title">Umowa członkowska</h5>
                <p class="card-text text-muted small">Formalna umowa regulująca warunki członkostwa w klubie sportowym.</p>
                <div class="mt-3">
                    <select class="form-select form-select-sm mb-2 member-select" data-doc="agreement">
                        <option value="">— wybierz zawodnika —</option>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= (int)$m['id'] ?>">
                                <?= View::e($m['last_name'] ?? '') ?> <?= View::e($m['first_name'] ?? '') ?>
                                <?= !empty($m['member_number']) ? '(' . View::e($m['member_number']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <a href="#" class="btn btn-sm btn-primary doc-download-btn" data-base="<?= url('documents/agreement') ?>">
                        <i class="bi bi-file-pdf"></i> Generuj PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Zgoda na treningi -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-clipboard-check display-4 text-success mb-3 d-block"></i>
                <h5 class="card-title">Zgoda na treningi</h5>
                <p class="card-text text-muted small">Formularz zgody na udział w treningach sportowych.</p>
                <div class="mt-3">
                    <select class="form-select form-select-sm mb-2 member-select" data-doc="consent">
                        <option value="">— wybierz zawodnika —</option>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= (int)$m['id'] ?>">
                                <?= View::e($m['last_name'] ?? '') ?> <?= View::e($m['first_name'] ?? '') ?>
                                <?= !empty($m['member_number']) ? '(' . View::e($m['member_number']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <a href="#" class="btn btn-sm btn-success doc-download-btn" data-base="<?= url('documents/consent') ?>">
                        <i class="bi bi-file-pdf"></i> Generuj PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Oświadczenie o odpowiedzialności -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-shield-exclamation display-4 text-warning mb-3 d-block"></i>
                <h5 class="card-title">Oświadczenie o odpowiedzialności</h5>
                <p class="card-text text-muted small">Oświadczenie o dobrowolnym udziale i akceptacji ryzyka.</p>
                <div class="mt-3">
                    <select class="form-select form-select-sm mb-2 member-select" data-doc="waiver">
                        <option value="">— wybierz zawodnika —</option>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= (int)$m['id'] ?>">
                                <?= View::e($m['last_name'] ?? '') ?> <?= View::e($m['first_name'] ?? '') ?>
                                <?= !empty($m['member_number']) ? '(' . View::e($m['member_number']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <a href="#" class="btn btn-sm btn-warning doc-download-btn" data-base="<?= url('documents/waiver') ?>">
                        <i class="bi bi-file-pdf"></i> Generuj PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.doc-download-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            var card = btn.closest('.card-body');
            var select = card.querySelector('.member-select');
            var memberId = select ? select.value : '';
            if (!memberId) {
                e.preventDefault();
                alert('Wybierz zawodnika z listy.');
                return;
            }
            btn.href = btn.dataset.base + '/' + memberId;
        });
    });
});
</script>
