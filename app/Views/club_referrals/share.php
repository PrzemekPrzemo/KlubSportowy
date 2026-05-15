<?php
use App\Helpers\View;
/**
 * @var string $code
 * @var string $shareLink
 * @var string $emailSubject
 * @var string $emailBody
 */
$mailHref = 'mailto:?subject=' . rawurlencode($emailSubject) . '&body=' . rawurlencode($emailBody);
$tweetHref = 'https://twitter.com/intent/tweet?text=' . rawurlencode('Polecam ClubDesk! Skorzystaj z mojego kodu: ' . $code . ' ' . $shareLink);
$fbHref = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($shareLink);
$liHref = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($shareLink);
?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h4 mb-3"><i class="bi bi-send me-2"></i> Udostepnij zaproszenie</h2>

                    <p class="text-muted">
                        Wybierz kanal i wyslij swoje zaproszenie. Kazda osoba ktora
                        zarejestruje klub przez Twoj link, generuje rabat dla Ciebie.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Twoj kod:</label>
                        <div><code class="fs-5"><?= View::e($code) ?></code></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Link do udostepnienia:</label>
                        <input type="text" class="form-control" value="<?= View::e($shareLink) ?>" readonly>
                    </div>

                    <hr>

                    <h5 class="mb-3">Udostepnij na:</h5>
                    <div class="d-flex gap-2 flex-wrap mb-4">
                        <a href="<?= View::e($mailHref) ?>" class="btn btn-outline-primary">
                            <i class="bi bi-envelope"></i> Email
                        </a>
                        <a href="<?= View::e($tweetHref) ?>" target="_blank" rel="noopener" class="btn btn-outline-info">
                            <i class="bi bi-twitter-x"></i> Twitter / X
                        </a>
                        <a href="<?= View::e($fbHref) ?>" target="_blank" rel="noopener" class="btn btn-outline-primary">
                            <i class="bi bi-facebook"></i> Facebook
                        </a>
                        <a href="<?= View::e($liHref) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary">
                            <i class="bi bi-linkedin"></i> LinkedIn
                        </a>
                    </div>

                    <h5 class="mb-2">Szablon emaila:</h5>
                    <div class="mb-3">
                        <label class="form-label small">Temat:</label>
                        <input type="text" class="form-control" value="<?= View::e($emailSubject) ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Tresc:</label>
                        <textarea class="form-control" rows="8" readonly><?= View::e($emailBody) ?></textarea>
                    </div>

                    <a href="<?= url('club/referrals') ?>" class="btn btn-link">
                        <i class="bi bi-arrow-left"></i> Wroc do dashboardu
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
