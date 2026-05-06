<?php use App\Helpers\View; ?>

<style>
.pricing-card { transition: transform .2s, box-shadow .2s; height: 100%; }
.pricing-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,.12); }
.pricing-card.popular { border: 2px solid #EE2C28; transform: scale(1.03); }
.pricing-card .price-big { font-size: 2.4rem; font-weight: 700; }
.pricing-card .feature-list li { padding: .35rem 0; border-bottom: 1px solid #f1f1f1; font-size: .9rem; }
.pricing-card .feature-list li:last-child { border-bottom: 0; }
.pricing-card .feature-list .bi-check2 { color: #198754; margin-right: 6px; }
.pricing-card .feature-list .bi-x { color: #ccc; margin-right: 6px; }
.badge-popular { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); }
</style>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold mb-3">Cennik ClubDesk</h1>
        <p class="lead text-muted">
            Wybierz plan dopasowany do wielkości Twojego klubu.<br>
            Wszystkie plany — bez ukrytych kosztów, anuluj kiedy chcesz, 14 dni za darmo.
        </p>
    </div>

    <!-- Toggle monthly / yearly -->
    <div class="text-center mb-4">
        <div class="btn-group" role="group" aria-label="Okres rozliczenia">
            <input type="radio" class="btn-check" name="billing" id="billing-monthly" autocomplete="off" checked>
            <label class="btn btn-outline-primary" for="billing-monthly">Miesięcznie</label>
            <input type="radio" class="btn-check" name="billing" id="billing-yearly" autocomplete="off">
            <label class="btn btn-outline-primary" for="billing-yearly">
                Rocznie <span class="badge bg-success ms-1">-17%</span>
            </label>
        </div>
    </div>

    <div class="row g-4 align-items-stretch">
        <?php foreach ($plans as $p):
            $f = $p['features_decoded'] ?? [];
            $isPopular  = ($f['badge'] ?? '') === 'NAJPOPULARNIEJSZY';
            $isFederation = ($f['pricing_model'] ?? '') === 'custom_quote';
            $isTrial = !empty($f['trial_days']);
            $isUnlimited = $p['max_members'] === null;
            $monthlyDisplay = $isFederation ? 'Wycena' : ($isTrial ? '0 zł' : number_format((float)$p['price_monthly'], 0, ',', ' ') . ' zł');
            $yearlyDisplay  = $isFederation ? 'Wycena' : ($isTrial ? '0 zł' : number_format((float)$p['price_yearly']  / 12, 0, ',', ' ') . ' zł');
        ?>
        <div class="col-md-6 col-lg-4 col-xl-2 d-flex">
            <div class="card pricing-card <?= $isPopular ? 'popular' : '' ?> p-4 w-100 position-relative">
                <?php if ($isPopular): ?>
                    <span class="badge bg-danger badge-popular">★ NAJPOPULARNIEJSZY</span>
                <?php elseif ($isTrial): ?>
                    <span class="badge bg-success badge-popular">DARMOWY</span>
                <?php endif; ?>

                <h3 class="mb-2"><?= View::e($p['name']) ?></h3>
                <small class="text-muted mb-3 d-block" style="min-height: 36px;">
                    <?= View::e($f['description'] ?? '') ?>
                </small>

                <div class="mb-3 text-center">
                    <div class="price-big" data-monthly="<?= View::e($monthlyDisplay) ?>" data-yearly="<?= View::e($yearlyDisplay) ?>">
                        <?= $monthlyDisplay ?>
                    </div>
                    <small class="text-muted price-sub"><span class="period-label">/ miesiąc</span></small>
                    <?php if (!$isFederation && !$isTrial): ?>
                        <div class="small text-muted">
                            <span class="yearly-total" style="display:none;">
                                Rocznie: <?= number_format((float)$p['price_yearly'], 0, ',', ' ') ?> zł
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <ul class="feature-list list-unstyled mb-4 flex-grow-1">
                    <li>
                        <i class="bi bi-people"></i>
                        <strong><?= $isUnlimited ? 'Bez limitu' : (int)$p['max_members'] ?></strong> zawodników
                    </li>
                    <li>
                        <i class="bi bi-trophy"></i>
                        <strong><?= $p['max_sports'] === null ? 'Bez limitu' : (int)$p['max_sports'] ?></strong> sekcji sportowych
                    </li>
                    <li><i class="bi bi-<?= !empty($f['backup']) ? 'check2' : 'x' ?>"></i> Kopie zapasowe</li>
                    <li><i class="bi bi-<?= !empty($f['sms']) ? 'check2' : 'x' ?>"></i> Powiadomienia SMS</li>
                    <li><i class="bi bi-<?= !empty($f['api']) ? 'check2' : 'x' ?>"></i> Dostęp API</li>
                    <li><i class="bi bi-<?= !empty($f['custom_css']) ? 'check2' : 'x' ?>"></i> Custom branding (CSS)</li>
                    <li><i class="bi bi-<?= !empty($f['white_label']) ? 'check2' : 'x' ?>"></i> White-label (bez logo CD)</li>
                    <li><i class="bi bi-<?= !empty($f['custom_domain']) ? 'check2' : 'x' ?>"></i> Custom domena</li>
                    <li><i class="bi bi-<?= !empty($f['medical']) ? 'check2' : 'x' ?>"></i> Moduł medyczny + WADA</li>
                    <li><i class="bi bi-<?= !empty($f['compliance']) ? 'check2' : 'x' ?>"></i> Anti-doping consent</li>
                    <li><i class="bi bi-<?= !empty($f['analytics']) ? 'check2' : 'x' ?>"></i> Zaawansowana analityka</li>
                    <li>
                        <i class="bi bi-credit-card"></i>
                        Bramki: <?= View::e(strtoupper((string)($f['gateways'] ?? 'manual'))) ?>
                    </li>
                    <li>
                        <i class="bi bi-headset"></i>
                        <?php
                        $supportLabels = [
                            'community' => 'Wsparcie społeczności',
                            'email' => 'Email support',
                            'email_priority' => 'Priority email',
                            'email_priority,phone' => 'Email + telefon',
                            'dedicated_account_manager' => 'Dedykowany opiekun',
                            'sla,dedicated_team' => 'SLA + zespół',
                        ];
                        echo View::e($supportLabels[$f['support'] ?? 'community'] ?? $f['support'] ?? 'Email');
                        ?>
                    </li>
                </ul>

                <?php if ($isFederation): ?>
                    <a href="mailto:kontakt@clubdesk.pl?subject=Plan%20Federacja%20-%20zapytanie"
                       class="btn btn-outline-primary mt-auto">
                        <i class="bi bi-envelope"></i> Zapytaj o wycenę
                    </a>
                <?php elseif ($isTrial): ?>
                    <a href="<?= url('register') ?>" class="btn btn-success mt-auto">
                        <i class="bi bi-rocket-takeoff"></i> Rozpocznij za darmo
                    </a>
                <?php else: ?>
                    <a href="<?= url('register') ?>?plan=<?= View::e($p['code']) ?>"
                       class="btn <?= $isPopular ? 'btn-danger' : 'btn-primary' ?> mt-auto">
                        Wybieram plan <?= View::e($p['name']) ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- FAQ -->
    <div class="row mt-5">
        <div class="col-lg-8 offset-lg-2">
            <h3 class="mb-4 text-center">Najczęstsze pytania</h3>
            <div class="accordion" id="pricingFaq">
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">Czy mogę zmienić plan w trakcie?</button></h2>
                    <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#pricingFaq"><div class="accordion-body">
                        Tak. W każdej chwili możesz przejść na wyższy lub niższy plan. Płacisz tylko za wykorzystany okres — proporcjonalne rozliczenie automatycznie.
                    </div></div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">Co po 14 dniach trial?</button></h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#pricingFaq"><div class="accordion-body">
                        Przed końcem otrzymasz email z propozycją upgrade do płatnego planu. Bez decyzji konto przechodzi w tryb tylko-do-odczytu (dane bezpieczne, brak utraty). Możesz wybrać plan w dowolnym momencie.
                    </div></div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">Czy są ukryte koszty?</button></h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#pricingFaq"><div class="accordion-body">
                        Nie. Cena = wszystko co widzisz. SMS-y w planach Klub+ są w pakiecie (do 500/m-c). Płatności online — prowizje pobiera bramka (Przelewy24/PayU/Tpay/Stripe), nie ClubDesk.
                    </div></div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">Migracja z innego systemu?</button></h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#pricingFaq"><div class="accordion-body">
                        Bezpłatna pomoc w migracji z dowolnego systemu (Excel, Klubduden, TeamSnap, eClub). Importujemy zawodników, wpłaty i historię z CSV. Plany od Klub wzwyż — Twój sukces na początku jest dla nas priorytetem.
                    </div></div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">Faktura VAT?</button></h2>
                    <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#pricingFaq"><div class="accordion-body">
                        Tak. Każda subskrypcja jest fakturowana z polską stawką VAT 23%. Faktury w PDF dostajesz emailem co miesiąc / rok zgodnie z planem.
                    </div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle monthly/yearly prices
document.addEventListener('DOMContentLoaded', function() {
    var monthlyRadio = document.getElementById('billing-monthly');
    var yearlyRadio  = document.getElementById('billing-yearly');
    if (!monthlyRadio || !yearlyRadio) return;

    function update() {
        var isYearly = yearlyRadio.checked;
        document.querySelectorAll('.price-big').forEach(function(el) {
            el.textContent = isYearly ? el.dataset.yearly : el.dataset.monthly;
        });
        document.querySelectorAll('.period-label').forEach(function(el) {
            el.textContent = isYearly ? '/ miesiąc (płatne rocznie)' : '/ miesiąc';
        });
        document.querySelectorAll('.yearly-total').forEach(function(el) {
            el.style.display = isYearly ? 'inline' : 'none';
        });
    }

    monthlyRadio.addEventListener('change', update);
    yearlyRadio.addEventListener('change', update);
});
</script>
