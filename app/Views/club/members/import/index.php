<?php
/**
 * Formularz uploadu CSV/XLSX dla importu członków (sekretariat).
 *
 * @var array  $dbColumns
 * @var int    $maxUploadBytes
 */
$maxMb = max(1, (int)round(($maxUploadBytes ?? 0) / (1024 * 1024)));
?>
<div class="row justify-content-center">
    <div class="col-lg-9">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0"><i class="bi bi-upload"></i> Import członków klubu</h1>
            <div class="btn-group">
                <a href="<?= url('club/members/import/template.csv') ?>" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Pobierz wzorzec CSV
                </a>
                <a href="<?= url('sekretariat') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Powrót do biura
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <p class="text-muted">
                    Prześlij plik <code>.csv</code> z danymi członków. Po wgraniu zobaczysz
                    podgląd pierwszych <strong>50 wierszy</strong> oraz mapowanie kolumn —
                    możesz je skorygować przed wykonaniem importu.
                </p>

                <form method="POST" action="<?= url('club/members/import/preview') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="import_file" class="form-label">Plik z danymi członków</label>
                        <input type="file" name="import_file" id="import_file"
                               class="form-control" accept=".csv,.txt,.xlsx" required>
                        <div class="form-text">
                            Dozwolone formaty: <code>.csv</code>, <code>.txt</code>, <code>.xlsx</code>.
                            Maksymalny rozmiar pliku: <strong><?= $maxMb ?> MB</strong>.
                            Pierwszy wiersz powinien zawierać nazwy kolumn.
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cloud-arrow-up"></i> Wgraj i pokaż podgląd
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-info-circle"></i> Obsługiwane kolumny</h6>
                <p class="small text-muted mb-2">
                    Nazwy kolumn rozpoznawane są po polsku i angielsku (np. <em>imie</em>,
                    <em>first_name</em>). Duplikaty wykrywane są po numerze członkowskim
                    oraz adresie email.
                </p>
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach (($dbColumns ?? []) as $col): ?>
                        <span class="badge bg-light text-dark border"><?= htmlspecialchars((string)$col, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card mt-3 border-warning">
            <div class="card-body">
                <h6 class="card-title text-warning"><i class="bi bi-shield-lock"></i> Bezpieczeństwo i RODO</h6>
                <ul class="mb-0 small text-muted">
                    <li>Numery <strong>PESEL</strong> są zapisywane w bazie zgodnie z polityką klubu (szyfrowanie kolumnowe, gdy włączone).</li>
                    <li>Każdy import jest <strong>logowany</strong> w dzienniku dostępu (kto, kiedy, ile wierszy).</li>
                    <li>Import nie nadpisuje istniejących profili — duplikaty są pomijane z informacją w raporcie.</li>
                    <li>Po wykonaniu importu plik tymczasowy jest <strong>natychmiast usuwany</strong> z serwera.</li>
                </ul>
            </div>
        </div>

    </div>
</div>
