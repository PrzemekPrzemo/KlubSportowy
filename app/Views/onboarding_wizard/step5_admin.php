<?php
use App\Helpers\Csrf;
use App\Helpers\View;
/** @var array $data */
/** @var array $summary */
$club = $summary['club'] ?? [];
$brand = $summary['branding'] ?? [];
$nSports = count($summary['sports'] ?? []);
$nFees   = count($summary['fees'] ?? []);
?>
<section class="py-5" style="background:#f6f7fb;min-height:80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <?php include __DIR__ . '/_progress.php'; ?>

                        <div class="row">
                            <div class="col-md-7">
                                <h2 class="mb-1">Twoje konto admina</h2>
                                <p class="text-muted">Ostatni krok! Utworzmy Ci konto prezesa/zarzadu.</p>

                                <?php if (!empty($flashError)): ?>
                                    <div class="alert alert-danger"><?= View::e($flashError) ?></div>
                                <?php endif; ?>

                                <form method="post" action="<?= url('trial/admin') ?>" autocomplete="off">
                                    <?= Csrf::field() ?>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Imie <span class="text-danger">*</span></label>
                                            <input type="text" name="first_name" class="form-control"
                                                   value="<?= View::e($data['first_name'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nazwisko <span class="text-danger">*</span></label>
                                            <input type="text" name="last_name" class="form-control"
                                                   value="<?= View::e($data['last_name'] ?? '') ?>" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control"
                                               value="<?= View::e($data['email'] ?? '') ?>" required
                                               placeholder="prezes@twojklub.pl">
                                        <small class="text-muted">Bedziesz logowal sie tym adresem.</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Haslo <span class="text-danger">*</span></label>
                                        <input type="password" name="password" class="form-control" required
                                               minlength="8" placeholder="Minimum 8 znakow, litera + cyfra">
                                    </div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="accept_terms" id="t1" required>
                                        <label class="form-check-label" for="t1">
                                            Akceptuję <a href="<?= url('legal/regulamin') ?>" target="_blank">Regulamin</a>
                                            i <a href="<?= url('legal/polityka-prywatnosci') ?>" target="_blank">Politykę prywatności</a>
                                            <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="accept_dpa" id="t_dpa" required>
                                        <label class="form-check-label" for="t_dpa">
                                            Akceptuję
                                            <a href="<?= url('legal/dpa') ?>" target="_blank">umowę powierzenia przetwarzania danych osobowych (DPA)</a>,
                                            niezbędną do przetwarzania danych Członków klubu w platformie ClubDesk
                                            (art. 28 RODO) <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                    <div class="form-check mb-4">
                                        <input class="form-check-input" type="checkbox" name="accept_marketing" id="t2"
                                               <?= !empty($data['marketing']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="t2">
                                            Chcę otrzymywać informacje o nowościach i poradach (opcjonalnie).
                                        </label>
                                    </div>

                                    <div class="d-flex justify-content-between mt-2">
                                        <a href="<?= url('trial/fees') ?>" class="btn btn-link text-muted">
                                            <i class="bi bi-arrow-left"></i> Wstecz
                                        </a>
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="bi bi-rocket-takeoff me-1"></i> Zaloz klub
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="col-md-5">
                                <div class="card border-0" style="background:#f8f9fa;">
                                    <div class="card-body">
                                        <h6 class="text-uppercase text-muted small mb-3">Podsumowanie</h6>

                                        <div class="mb-2">
                                            <strong><i class="bi bi-building me-1"></i><?= View::e($club['name'] ?? '') ?></strong><br>
                                            <small class="text-muted"><?= View::e($club['city'] ?? '') ?> &middot; <?= View::e($club['email'] ?? '') ?></small>
                                        </div>

                                        <?php if (!empty($brand['subdomain'])): ?>
                                            <div class="mb-2 small">
                                                <i class="bi bi-globe me-1"></i>
                                                <code><?= View::e($brand['subdomain']) ?>.clubdesk.pl</code>
                                            </div>
                                        <?php endif; ?>

                                        <hr>
                                        <div class="small">
                                            <div><i class="bi bi-trophy me-1 text-warning"></i> <?= (int)$nSports ?> sekcje sportowe</div>
                                            <div><i class="bi bi-cash-coin me-1 text-success"></i> <?= (int)$nFees ?> stawek skladek</div>
                                            <div><i class="bi bi-clock-history me-1 text-primary"></i> 30 dni trial</div>
                                        </div>

                                        <hr>
                                        <small class="text-muted">
                                            <i class="bi bi-shield-check"></i> Bez karty kredytowej.
                                            Po 30 dniach moglsz wybrac plan platny lub konto zostanie zawieszone.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
