<?php use App\Helpers\View; ?>

<!-- HERO -->
<section class="hero-section text-center">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3">Wielosportowy portal zarzadzania klubem</h1>
        <p class="lead mb-4" style="max-width:700px; margin:0 auto;">
            Kompleksowe narzedzie do zarzadzania klubem sportowym — czlonkowie, finanse, wydarzenia,
            treningi, raporty i wiele wiecej. Wszystko w jednym miejscu.
        </p>
        <a href="<?= url('register') ?>" class="btn btn-light btn-lg px-5">
            <i class="bi bi-rocket-takeoff"></i> Zaloz klub za darmo
        </a>
    </div>
</section>

<!-- FEATURES -->
<section class="py-5 bg-light" id="features">
    <div class="container">
        <h2 class="text-center fw-bold mb-5">Funkcjonalnosci</h2>
        <div class="row g-4">
            <?php
            $features = [
                ['icon' => 'bi-trophy',              'title' => 'Multi-sport',           'desc' => 'Zarzadzaj wieloma sekcjami sportowymi w jednym klubie — pilka nozna, koszykowka, lekkoatletyka i wiele innych.'],
                ['icon' => 'bi-cash-coin',           'title' => 'Finanse',               'desc' => 'Skladki czlonkowskie, platnosci online, faktury i pelna kontrola finansow klubu.'],
                ['icon' => 'bi-person-badge',        'title' => 'Portal zawodnika',      'desc' => 'Kazdy zawodnik ma wlasny portal z harmonogramem, wynikami i platnosciami.'],
                ['icon' => 'bi-file-earmark-bar-graph', 'title' => 'Raporty',            'desc' => 'Generuj raporty PDF/CSV — listy czlonkow, finanse, protokoly zawodow.'],
                ['icon' => 'bi-code-slash',          'title' => 'API',                   'desc' => 'REST API do integracji z zewnetrznymi systemami i aplikacjami.'],
                ['icon' => 'bi-phone',               'title' => 'Mobilna apka',          'desc' => 'Responsywny interfejs dzialajacy na kazdym urzadzeniu — telefon, tablet, komputer.'],
            ];
            foreach ($features as $f): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card feature-card h-100 p-4 text-center">
                    <div class="mb-3">
                        <i class="bi <?= $f['icon'] ?>" style="font-size: 2.5rem; color: #0d6efd;"></i>
                    </div>
                    <h5 class="fw-bold"><?= $f['title'] ?></h5>
                    <p class="text-muted mb-0"><?= $f['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- SPORTS -->
<section class="py-5" id="sports">
    <div class="container">
        <h2 class="text-center fw-bold mb-5">Wspierane sporty</h2>
        <div class="row g-3 justify-content-center">
            <?php
            $defaultSports = [
                ['name' => 'Pilka nozna',    'icon' => 'bi-dribbble'],
                ['name' => 'Koszykowka',     'icon' => 'bi-basketball'],
                ['name' => 'Siatkowka',      'icon' => 'bi-volleyball'],
                ['name' => 'Lekkoatletyka',  'icon' => 'bi-stopwatch'],
                ['name' => 'Plywanie',       'icon' => 'bi-water'],
                ['name' => 'Tenis',          'icon' => 'bi-controller'],
                ['name' => 'Boks',           'icon' => 'bi-hand-index'],
                ['name' => 'Judo',           'icon' => 'bi-person-arms-up'],
                ['name' => 'Gimnastyka',     'icon' => 'bi-stars'],
                ['name' => 'Kolarstwo',      'icon' => 'bi-bicycle'],
                ['name' => 'Wioslowanie',    'icon' => 'bi-tsunami'],
                ['name' => 'Szermierka',     'icon' => 'bi-lightning'],
            ];
            foreach ($defaultSports as $sport): ?>
            <div class="col-4 col-sm-3 col-md-2 text-center">
                <div class="p-3">
                    <i class="bi <?= $sport['icon'] ?>" style="font-size: 2rem; color: #6610f2;"></i>
                    <div class="small mt-1"><?= $sport['name'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="text-center text-muted mt-3">...i wiele wiecej. Mozesz dodac dowolny sport!</p>
    </div>
</section>

<!-- PRICING -->
<section class="py-5 bg-light" id="pricing">
    <div class="container">
        <h2 class="text-center fw-bold mb-5">Cennik</h2>
        <div class="row g-4 justify-content-center">
            <?php if (!empty($plans)): ?>
                <?php foreach ($plans as $plan):
                    $isFeatured = ($plan['code'] ?? '') === 'standard';
                    $features = [];
                    if (!empty($plan['features'])) {
                        $decoded = is_string($plan['features']) ? json_decode($plan['features'], true) : $plan['features'];
                        if (is_array($decoded)) $features = $decoded;
                    }
                ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card pricing-card h-100 <?= $isFeatured ? 'featured' : '' ?>">
                        <?php if ($isFeatured): ?>
                        <div class="card-header bg-primary text-white text-center fw-bold">Najpopularniejszy</div>
                        <?php endif; ?>
                        <div class="card-body text-center d-flex flex-column">
                            <h5 class="fw-bold"><?= View::e($plan['name']) ?></h5>
                            <div class="display-6 fw-bold my-3">
                                <?php if ((float)$plan['price_monthly'] > 0): ?>
                                    <?= number_format((float)$plan['price_monthly'], 0, ',', ' ') ?> <small class="fs-6 text-muted">zl/mies.</small>
                                <?php else: ?>
                                    Za darmo
                                <?php endif; ?>
                            </div>
                            <ul class="list-unstyled text-start mb-4 flex-grow-1">
                                <li class="mb-1">
                                    <i class="bi bi-people text-primary"></i>
                                    <?= $plan['max_members'] ? 'Do ' . (int)$plan['max_members'] . ' zawodnikow' : 'Bez limitu zawodnikow' ?>
                                </li>
                                <li class="mb-1">
                                    <i class="bi bi-trophy text-primary"></i>
                                    <?= $plan['max_sports'] ? 'Do ' . (int)$plan['max_sports'] . ' sekcji' : 'Bez limitu sekcji' ?>
                                </li>
                                <?php foreach ($features as $feat): ?>
                                <li class="mb-1"><i class="bi bi-check-circle text-success"></i> <?= View::e($feat) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <a href="<?= url('register') ?>" class="btn <?= $isFeatured ? 'btn-primary' : 'btn-outline-primary' ?> w-100">
                                Wybierz plan
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback pricing cards -->
                <?php
                $fallbackPlans = [
                    ['name' => 'Trial',    'price' => 'Za darmo',  'members' => '25',  'sports' => '1', 'featured' => false],
                    ['name' => 'Basic',    'price' => '49 zl/mies.', 'members' => '100', 'sports' => '3', 'featured' => false],
                    ['name' => 'Standard', 'price' => '99 zl/mies.', 'members' => '300', 'sports' => '8', 'featured' => true],
                    ['name' => 'Premium',  'price' => '199 zl/mies.','members' => 'Bez limitu', 'sports' => 'Bez limitu', 'featured' => false],
                ];
                foreach ($fallbackPlans as $fp): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card pricing-card h-100 <?= $fp['featured'] ? 'featured' : '' ?>">
                        <?php if ($fp['featured']): ?>
                        <div class="card-header bg-primary text-white text-center fw-bold">Najpopularniejszy</div>
                        <?php endif; ?>
                        <div class="card-body text-center">
                            <h5 class="fw-bold"><?= $fp['name'] ?></h5>
                            <div class="display-6 fw-bold my-3"><?= $fp['price'] ?></div>
                            <ul class="list-unstyled text-start mb-4">
                                <li class="mb-1"><i class="bi bi-people text-primary"></i> <?= $fp['members'] ?> zawodnikow</li>
                                <li class="mb-1"><i class="bi bi-trophy text-primary"></i> <?= $fp['sports'] ?> sekcji</li>
                            </ul>
                            <a href="<?= url('register') ?>" class="btn <?= $fp['featured'] ? 'btn-primary' : 'btn-outline-primary' ?> w-100">
                                Wybierz plan
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="py-5" id="faq">
    <div class="container" style="max-width: 800px;">
        <h2 class="text-center fw-bold mb-5">Najczesciej zadawane pytania</h2>
        <div class="accordion" id="faqAccordion">
            <?php
            $faqs = [
                [
                    'q' => 'Co to jest KlubSportowy?',
                    'a' => 'KlubSportowy to kompleksowa platforma SaaS do zarzadzania klubami sportowymi. Umozliwia zarzadzanie czlonkami, finansami, wydarzeniami, treningami, komunikacja i wiele wiecej — wszystko w jednym miejscu.'
                ],
                [
                    'q' => 'Ile kosztuje?',
                    'a' => 'Oferujemy darmowy okres probny (Trial), a nastepnie plany od 49 zl miesiecznie. Kazdy plan mozna przetestowac przez 30 dni za darmo. Nie wymagamy karty kredytowej do rejestracji.'
                ],
                [
                    'q' => 'Czy moge zarzadzac wieloma sportami?',
                    'a' => 'Tak! KlubSportowy obsluguje wiele sekcji sportowych w jednym klubie. Mozesz dodac pilke nozna, koszykowke, lekkoatletyka i dowolny inny sport — kazda sekcja ma wlasne ustawienia i modul.'
                ],
                [
                    'q' => 'Jak dziala portal zawodnika?',
                    'a' => 'Kazdy zawodnik otrzymuje dostep do wlasnego portalu, gdzie moze przegladac harmonogram treningow, wyniki, platnosci, dane kontaktowe i wiele wiecej. Portal jest responsywny i dziala na kazdym urzadzeniu.'
                ],
                [
                    'q' => 'Czy jest API?',
                    'a' => 'Tak, udostepniamy REST API v1 z dokumentacja. API pozwala na integracje z zewnetrznymi systemami — mozna pobierac dane zawodnikow, wydarzen, platnosci i sportow. Kazdy klub moze wygenerowac wlasne klucze API.'
                ],
            ];
            foreach ($faqs as $i => $faq): ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>" type="button"
                            data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
                        <?= $faq['q'] ?>
                    </button>
                </h2>
                <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>"
                     data-bs-parent="#faqAccordion">
                    <div class="accordion-body"><?= $faq['a'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
