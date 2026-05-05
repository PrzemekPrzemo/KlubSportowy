<?php
use App\Helpers\View;
use App\Helpers\SportModuleLoader;

// Pre-load wszystkie manifesty raz (50 sportow) — uniknij N+1 zapytan.
$manifestsByKey = SportModuleLoader::load();

// Human-readable labels dla feature flags (UI tooltipy / pillsy).
$featureLabels = [
    'demo-ready'     => 'Demo gotowe',
    'teams'          => 'Drużyny',
    'matches'        => 'Mecze',
    'players'        => 'Zawodnicy',
    'positions'      => 'Pozycje',
    'belts'          => 'Pasy/stopnie',
    'results'        => 'Wyniki',
    'tournaments'    => 'Turnieje',
    'rankings'       => 'Rankingi',
    'records'        => 'Rekordy',
    'medicals'       => 'Badania lekarskie',
    'transfers'      => 'Transfery',
    'leagues'        => 'Ligi',
    'stats'          => 'Statystyki',
    'attendance'     => 'Frekwencja',
    'calendar'       => 'Kalendarz',
    'horses'         => 'Konie',
    'owners'         => 'Właściciele',
    'riders'         => 'Riderzy PZJ',
    'pzj_license'    => 'Licencje PZJ',
    'fei_passport'   => 'Paszport FEI',
    'pzj_passport'   => 'Paszport PZJ',
    'competitions'   => 'Zawody',
    'disciplines'    => 'Konkurencje',
    'fis_points'     => 'Punkty FIS',
    'sinclair'       => 'Sinclair',
    'wilks'          => 'Wilks',
    'weight_categories' => 'Kategorie wagowe',
    'weight_classes' => 'Klasy wagowe',
    'fight_record'   => 'Rekord W-L',
    'amateur_pro'    => 'Amator/Pro',
    'kata'           => 'Kata',
    'kumite'         => 'Kumite',
    'gi_nogi'        => 'Gi/No-Gi',
    'tes_pcs'        => 'TES/PCS',
    'sp_fs'          => 'SP/FS',
    'difficulty_score' => 'D-score',
    'execution_score'  => 'E-score',
    'minor_protection' => 'RODO małol.',
    'couples'        => 'Pary',
    'pairs'          => 'Pary',
    'classes'        => 'Klasy',
    'standard'       => 'Standard',
    'latin'          => 'Latin',
    'partnerships'   => 'Pary',
    'imp_mp'         => 'IMP/MP',
    'pzbs_points'    => 'Punkty PZBS',
    'ratings'        => 'Rankingi ELO',
    'routes'         => 'Drogi',
    'sends'          => 'Przejścia',
    'grades'         => 'Stopnie',
    'wods'           => 'WODs',
    'scores'         => 'Wyniki WOD',
    'personal_records' => 'PR',
    'leaderboard'    => 'Leaderboard',
    'open_competition' => 'CrossFit Open',
    'boats'          => 'Łodzie',
    'crew'           => 'Załoga',
    'races'          => 'Regaty',
    'licenses'       => 'Licencje',
    'handicap'       => 'Handicap',
    'handicap_whs'   => 'Handicap WHS',
    'rounds'         => 'Rundy',
    'courses'        => 'Pole golfowe',
    'tees'           => 'Tee',
    'splits'         => 'Splity',
    'age_groups'     => 'Grupy wiekowe',
    'distances'      => 'Dystanse',
    'distance_time'  => 'Czas/dystans',
    'shooting_accuracy' => 'Strzelania',
    'run_time'       => 'Czas biegu',
    'penalties'      => 'Kary',
    'jumps'          => 'Skoki',
    'hill_k'         => 'Punkt K',
    'fencers'        => 'Szermierze',
    'weapons'        => 'Bronie',
    'fie_id'         => 'FIE ID',
    'equipment'      => 'Sprzęt',
    'recurve'        => 'Recurve',
    'compound'       => 'Compound',
    'styles'         => 'Style',
    'technique'      => 'Technika',
    'distance'       => 'Dystans',
    'h2h'            => 'H2H',
    'surfaces'       => 'Nawierzchnie',
    'courts'         => 'Korty',
    'methods'        => 'Metody',
    'fighters'       => 'Zawodnicy',
    'reservations'   => 'Rezerwacje',
    'club_records'   => 'Rekordy klubu',
    'personal_bests' => 'Rekordy PB',
    'power_watts'    => 'Power (W)',
    'ftp_tests'      => 'Testy FTP',
    'uci_categories' => 'Kategorie UCI',
    'qualifications' => 'Kwalifikacje',
    'pzpn_license'   => 'Licencja PZPN',
    'pzps_license'   => 'Licencja PZPS',
    'pzkosz_license' => 'Licencja PZKosz',
    'pzla_license'   => 'Licencja PZLA',
    'fouls'          => 'Faule',
    'sets'           => 'Sety',
    'player_stats'   => 'Statystyki',
    'cards'          => 'Kartki',
];
?>

<div class="card">
    <div class="card-body p-4">
        <h4 class="mb-1"><i class="bi bi-trophy"></i> Sekcje sportowe</h4>
        <p class="text-muted mb-4">Wybierz sporty, w których działa Twój klub.</p>

        <?php $limit = $sportLimit['limit'] ?? null; ?>
        <?php if ($limit !== null): ?>
            <div class="alert alert-info d-flex justify-content-between align-items-center" id="sport-limit-alert">
                <div>
                    Twój plan subskrypcji pozwala wybrać maksymalnie
                    <strong><?= (int)$limit ?></strong>
                    <?= $limit === 1 ? 'sekcję' : 'sekcji' ?>.
                </div>
                <div class="text-end small">
                    Wybrano: <strong id="sport-counter">0</strong> / <?= (int)$limit ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('onboarding/step2') ?>" id="onboarding-step2-form">
            <?= csrf_field() ?>

            <div class="row g-3 mb-4">
                <?php foreach ($allSports as $sport):
                    $checked   = in_array((int)$sport['id'], $clubSportIds, true);
                    $manifest  = $manifestsByKey[$sport['key']] ?? null;
                    $features  = $manifest['features'] ?? [];
                    $demoReady = in_array('demo-ready', $features, true);
                    // Top 4 inne features (poza demo-ready) — pokazujemy max 4 zeby nie zaladowac UI
                    $topFeatures = array_slice(array_filter($features, fn($f) => $f !== 'demo-ready'), 0, 4);
                ?>
                    <div class="col-md-4 col-sm-6">
                        <label class="card h-100 border <?= $checked ? 'border-primary' : '' ?> position-relative" style="cursor:pointer;">
                            <?php if ($demoReady): ?>
                                <span class="position-absolute top-0 start-0 m-2 badge bg-success" title="Plugin gotowy do demo — zawiera dane przykładowe">
                                    <i class="bi bi-check2-circle"></i> Demo
                                </span>
                            <?php endif; ?>
                            <div class="card-body text-center p-3">
                                <input type="checkbox" name="sports[]" value="<?= (int)$sport['id'] ?>"
                                       class="form-check-input position-absolute top-0 end-0 m-2 sport-checkbox"
                                       <?= $checked ? 'checked' : '' ?>>
                                <div class="fs-1 mb-2">
                                    <i class="bi <?= View::e($sport['icon'] ?? 'bi-trophy') ?>"
                                       style="color: <?= View::e($sport['color'] ?? '#0d6efd') ?>"></i>
                                </div>
                                <h6 class="mb-1"><?= View::e($sport['name']) ?></h6>
                                <?php if (!empty($sport['federation_name'])): ?>
                                    <small class="text-muted d-block mb-2"><?= View::e($sport['federation_name']) ?></small>
                                <?php endif; ?>
                                <?php if (!empty($topFeatures)): ?>
                                    <div class="d-flex flex-wrap gap-1 justify-content-center mt-2">
                                        <?php foreach ($topFeatures as $featKey): ?>
                                            <span class="badge bg-light text-secondary border" style="font-size:0.65rem; font-weight:400;">
                                                <?= View::e($featureLabels[$featKey] ?? $featKey) ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <?php if (count($features) - ($demoReady ? 1 : 0) > 4): ?>
                                            <span class="badge bg-light text-muted border" style="font-size:0.65rem; font-weight:400;">
                                                +<?= count($features) - ($demoReady ? 1 : 0) - 4 ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($allSports)): ?>
                <div class="alert alert-info">
                    Brak dostepnych sportow w katalogu. Skontaktuj sie z administratorem.
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between">
                <a href="<?= url('onboarding/step1') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Wstecz
                </a>
                <button type="submit" class="btn btn-primary">
                    Dalej <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        <div class="text-center mt-3"><a href="<?= url('onboarding/skip') ?>" class="text-muted small">Dokończ później &rarr;</a></div>
</form>
    </div>
</div>

<?php if ($limit !== null): ?>
<script>
(function() {
    const limit    = <?= (int)$limit ?>;
    const checks   = document.querySelectorAll('.sport-checkbox');
    const counter  = document.getElementById('sport-counter');
    const alertBox = document.getElementById('sport-limit-alert');

    function refresh() {
        const selected = Array.from(checks).filter(c => c.checked);
        const count    = selected.length;
        if (counter) counter.textContent = count;
        if (alertBox) {
            alertBox.classList.toggle('alert-info', count <= limit);
            alertBox.classList.toggle('alert-warning', count > limit);
        }
        // Disable any remaining unchecked once at limit (UX hint, server still validates)
        checks.forEach(c => {
            if (!c.checked) c.disabled = (count >= limit);
        });
    }
    checks.forEach(c => c.addEventListener('change', refresh));
    refresh();
})();
</script>
<?php endif; ?>
