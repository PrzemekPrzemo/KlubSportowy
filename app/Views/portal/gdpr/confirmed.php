<?php use App\Helpers\View; ?>

<div class="row justify-content-center mt-5">
    <div class="col-lg-6">
        <div class="card border-success text-center">
            <div class="card-body p-5">
                <i class="bi bi-check-circle-fill fs-1 text-success mb-3"></i>
                <h4>Prosba potwierdzona</h4>

                <?php if (($type ?? '') === 'delete'): ?>
                    <p class="text-muted">
                        Twoja prosba o usuniecie danych zostala potwierdzona i jest realizowana.
                        Otrzymasz e-mail z potwierdzeniem po zakonczeniu anonimizacji.
                    </p>
                    <p class="small">Sesja zostala zakonczona.</p>
                    <a href="<?= url('portal/login') ?>" class="btn btn-outline-secondary">Powrot do logowania</a>
                <?php elseif (($type ?? '') === 'export'): ?>
                    <p class="text-muted">
                        Twoj eksport danych jest generowany. Otrzymasz e-mail z linkiem do pobrania
                        gdy plik bedzie gotowy. Link bedzie wazny przez 7 dni.
                    </p>
                    <a href="<?= url('portal/gdpr') ?>" class="btn btn-primary">
                        <i class="bi bi-list-ul me-1"></i> Zobacz status moich prosb
                    </a>
                <?php else: ?>
                    <p class="text-muted">
                        Twoja prosba (#<?= (int)($reqId ?? 0) ?>) zostala potwierdzona i przekazana administratorowi klubu.
                    </p>
                    <a href="<?= url('portal/gdpr') ?>" class="btn btn-primary">Powrot</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
