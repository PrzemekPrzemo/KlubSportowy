<?php
use App\Helpers\View;
/** @var array $tiles */
?>
<style>
    .legal-hero { background: linear-gradient(135deg, #232232, #3a334d); color: #fff; padding: 3rem 0 2.5rem; }
    .legal-hero h1 { font-weight: 700; }
    .legal-card { transition: transform .15s ease, box-shadow .15s ease; height: 100%; }
    .legal-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
    .legal-card .icon { width: 48px; height: 48px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        background: rgba(238,44,40,.08); color: #EE2C28; font-size: 1.4rem; }
    .legal-card .badge-ver { background: #e9ecef; color: #495057; font-weight: 500; }
</style>

<section class="legal-hero">
    <div class="container">
        <h1 class="mb-2">Dokumenty prawne ClubDesk</h1>
        <p class="lead mb-0 opacity-75" style="max-width: 720px;">
            Komplet dokumentów regulujących korzystanie z platformy ClubDesk.
            Wszystkie dokumenty są wersjonowane, a historia zmian dostępna jest pod każdym dokumentem.
        </p>
        <p class="small mt-3 mb-0 opacity-75">
            Operator: <strong>Sendormeco Holding Sp. z o.o.</strong> &middot;
            NIP 5252866457 &middot; KRS 0000906110 &middot;
            ul. Złota 75A/7, 00-819 Warszawa
        </p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <?php foreach ($tiles as $t): ?>
                <div class="col-md-6 col-lg-4">
                    <a href="<?= $t['available'] ? url('legal/' . $t['slug']) : '#' ?>"
                       class="text-decoration-none <?= $t['available'] ? '' : 'pe-none opacity-50' ?>">
                        <div class="card legal-card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="icon"><i class="bi bi-file-earmark-text"></i></div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-0 text-dark"><?= View::e($t['label']) ?></h5>
                                        <?php if ($t['version']): ?>
                                            <small class="text-muted">
                                                Wersja <span class="badge badge-ver"><?= View::e($t['version']) ?></span>
                                                <?php if ($t['date']): ?>
                                                    &middot; od <?= View::e(date('d.m.Y', strtotime((string)$t['date']))) ?>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="text-muted small mb-3"><?= View::e($t['description']) ?></p>
                                <div class="text-end">
                                    <?php if ($t['available']): ?>
                                        <span class="text-primary small">
                                            Czytaj dokument <i class="bi bi-arrow-right ms-1"></i>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Wkrótce</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-5 p-4 bg-light rounded-3">
            <h5 class="mb-2"><i class="bi bi-info-circle me-2 text-primary"></i> Kontakt w sprawach prawnych</h5>
            <p class="mb-1 small">
                Wszelkie pytania związane z regulaminami, ochroną danych osobowych oraz umową powierzenia
                prosimy kierować na:
            </p>
            <ul class="small mb-0">
                <li>Kontakt ogólny: <a href="mailto:kontakt@clubdesk.pl">kontakt@clubdesk.pl</a></li>
                <li>Inspektor Ochrony Danych (IOD): <a href="mailto:iod@clubdesk.pl">iod@clubdesk.pl</a></li>
                <li>Adres korespondencyjny: Sendormeco Holding Sp. z o.o., ul. Złota 75A/7, 00-819 Warszawa</li>
            </ul>
        </div>
    </div>
</section>
