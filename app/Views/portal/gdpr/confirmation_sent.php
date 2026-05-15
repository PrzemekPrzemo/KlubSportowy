<?php use App\Helpers\View; ?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-info text-center">
            <div class="card-body p-5">
                <i class="bi bi-envelope-check fs-1 text-info mb-3"></i>
                <h4>Sprawdz swoja skrzynke e-mail</h4>
                <p class="text-muted">
                    Wyslalismy link potwierdzajacy na adres <strong><?= View::e($member['email'] ?? '-') ?></strong>.
                </p>
                <p class="small">
                    Aby kontynuowac, kliknij link w e-mailu. <strong>Link wygasa po 24 godzinach.</strong>
                </p>

                <?php if (($type ?? '') === 'delete'): ?>
                    <div class="alert alert-warning text-start small">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Po kliknieciu linku Twoje dane osobowe zostana zanonimizowane. Sesja zostanie zakonczona.
                    </div>
                <?php elseif (($type ?? '') === 'export'): ?>
                    <div class="alert alert-info text-start small">
                        <i class="bi bi-info-circle me-2"></i>
                        Po kliknieciu linku rozpocznie sie generowanie archiwum ZIP. Otrzymasz powiadomienie e-mail
                        gdy plik bedzie gotowy do pobrania.
                    </div>
                <?php endif; ?>

                <div class="d-grid gap-2 mt-3">
                    <a href="<?= url('portal/gdpr') ?>" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-1"></i> Powrot do moich danych
                    </a>
                </div>

                <p class="small text-muted mt-3 mb-0">
                    Nie otrzymales e-maila? Sprawdz folder spam lub skontaktuj sie z administratorem klubu.
                </p>
            </div>
        </div>
    </div>
</div>
