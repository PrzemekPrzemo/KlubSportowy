<?php
use App\Helpers\View;

$isEdit = !empty($report);
$existingConfig = $isEdit ? (json_decode($report['config_json'] ?? '{}', true) ?: []) : [];
$existingSource = $isEdit ? (string)($report['data_source'] ?? $defaultSource) : (string)$defaultSource;

$formAction = $isEdit
    ? url('club/reports-builder/' . (int)$report['id'] . '/update')
    : url('club/reports-builder/store');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">
        <i class="bi bi-tools text-primary"></i>
        <?= $isEdit ? 'Edycja raportu' : 'Nowy raport' ?>
    </h1>
    <a href="<?= url('club/reports-builder') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót do listy
    </a>
</div>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= View::e($flashError) ?></div>
<?php endif; ?>

<form method="post" action="<?= View::e($formAction) ?>" id="reportBuilderForm">
    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
    <input type="hidden" name="config_json" id="config_json" value="">

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nazwa raportu *</label>
                    <input type="text" name="name" class="form-control" required maxlength="200"
                           value="<?= View::e($report['name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Źródło danych</label>
                    <select name="data_source" id="dataSource" class="form-select" <?= $isEdit ? 'disabled' : '' ?>>
                        <?php foreach ($dataSources as $key => $src): ?>
                            <option value="<?= View::e($key) ?>" <?= $key === $existingSource ? 'selected' : '' ?>>
                                <?= View::e($src['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="data_source" value="<?= View::e($existingSource) ?>">
                        <small class="text-muted">Źródło nie może być zmienione po utworzeniu.</small>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="is_shared" name="is_shared" value="1"
                               <?= ($isEdit && (int)$report['is_shared'] === 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_shared">
                            <i class="bi bi-people"></i> Współdziel z innymi użytkownikami klubu
                        </label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Opis (opcjonalny)</label>
                    <textarea name="description" class="form-control" rows="2" maxlength="500"><?= View::e($report['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- LEFT: dostępne kolumny -->
        <div class="col-lg-3">
            <div class="card h-100">
                <div class="card-header bg-light"><strong>Dostępne kolumny</strong></div>
                <div class="card-body" id="availableColumns" style="max-height:600px;overflow-y:auto;">
                    <!-- wypełniane JS -->
                </div>
            </div>
        </div>

        <!-- MIDDLE: drop zones -->
        <div class="col-lg-5">
            <div class="card mb-2">
                <div class="card-header bg-primary text-white py-2"><i class="bi bi-table"></i> Kolumny do wyświetlenia</div>
                <div class="card-body drop-zone" id="zoneColumns" data-zone="columns" style="min-height:80px;">
                    <div class="text-muted small zone-placeholder">Przeciągnij kolumny tutaj…</div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header bg-warning py-2"><i class="bi bi-funnel"></i> Filtry</div>
                <div class="card-body drop-zone" id="zoneFilters" data-zone="filters" style="min-height:80px;">
                    <div class="text-muted small zone-placeholder">Przeciągnij kolumny tutaj, aby filtrować…</div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header bg-info text-white py-2"><i class="bi bi-collection"></i> Grupuj po</div>
                <div class="card-body drop-zone" id="zoneGroupBy" data-zone="group_by" style="min-height:60px;">
                    <div class="text-muted small zone-placeholder">Opcjonalne — grupowanie danych…</div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header bg-success text-white py-2"><i class="bi bi-sort-down"></i> Sortuj po</div>
                <div class="card-body drop-zone" id="zoneOrderBy" data-zone="order_by" style="min-height:60px;">
                    <div class="text-muted small zone-placeholder">Opcjonalne — sortowanie…</div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header bg-secondary text-white py-2"><i class="bi bi-calculator"></i> Agregacje</div>
                <div class="card-body drop-zone" id="zoneAggs" data-zone="aggregations" style="min-height:60px;">
                    <div class="text-muted small zone-placeholder">Opcjonalne — SUM/AVG/COUNT…</div>
                </div>
            </div>
        </div>

        <!-- RIGHT: preview + chart + actions -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header bg-light"><strong>Wykres</strong></div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label small">Typ</label>
                        <select id="chartType" class="form-select form-select-sm">
                            <option value="none">Brak</option>
                            <option value="bar">Słupkowy</option>
                            <option value="line">Liniowy</option>
                            <option value="pie">Kołowy</option>
                            <option value="doughnut">Pączek</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Oś X</label>
                        <select id="chartX" class="form-select form-select-sm"></select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Oś Y</label>
                        <select id="chartY" class="form-select form-select-sm"></select>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-light"><strong>Limit wierszy</strong></div>
                <div class="card-body">
                    <input type="number" id="limit" class="form-control form-control-sm" min="1" max="10000" value="1000">
                    <small class="text-muted">Max 10 000 wierszy.</small>
                </div>
            </div>

            <div class="d-grid gap-2 mb-3">
                <button type="button" class="btn btn-outline-primary" id="btnPreview">
                    <i class="bi bi-eye"></i> Podgląd (top 10)
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> <?= $isEdit ? 'Zapisz zmiany' : 'Zapisz raport' ?>
                </button>
            </div>

            <div id="previewBox" class="card d-none">
                <div class="card-header bg-light"><strong>Podgląd</strong></div>
                <div class="card-body p-2">
                    <div id="previewContent" class="small" style="max-height:300px;overflow:auto;"></div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
(function () {
    // Wszystkie źródła danych — z PHP do JS
    const DATA_SOURCES = <?= json_encode($dataSources, JSON_UNESCAPED_UNICODE) ?>;
    const OPERATORS = <?= json_encode(\App\Helpers\Reports\DataSourceRegistry::allowedOperators()) ?>;
    const AGG_FNS = <?= json_encode(\App\Helpers\Reports\DataSourceRegistry::allowedAggregations()) ?>;
    const CSRF = <?= json_encode($csrf) ?>;
    const PREVIEW_URL = <?= json_encode(url('club/reports-builder/preview')) ?>;
    const INITIAL_CONFIG = <?= json_encode($existingConfig, JSON_UNESCAPED_UNICODE) ?>;
    const INITIAL_SOURCE = <?= json_encode($existingSource) ?>;

    // Stan kreatora
    const state = {
        source: INITIAL_SOURCE,
        columns: Array.isArray(INITIAL_CONFIG.columns) ? INITIAL_CONFIG.columns.slice() : [],
        filters: Array.isArray(INITIAL_CONFIG.filters) ? INITIAL_CONFIG.filters.slice() : [],
        group_by: Array.isArray(INITIAL_CONFIG.group_by) ? INITIAL_CONFIG.group_by.slice() : [],
        order_by: Array.isArray(INITIAL_CONFIG.order_by) ? INITIAL_CONFIG.order_by.slice() : [],
        aggregations: Array.isArray(INITIAL_CONFIG.aggregations) ? INITIAL_CONFIG.aggregations.slice() : [],
        chart: INITIAL_CONFIG.chart || { type: 'none', x: '', y: '' },
        limit: INITIAL_CONFIG.limit || 1000,
    };

    const $ = (id) => document.getElementById(id);
    const sourceSelect = $('dataSource');
    const availableEl  = $('availableColumns');

    function currentColumns() {
        const src = DATA_SOURCES[state.source];
        return src ? src.columns : {};
    }

    function renderAvailable() {
        const cols = currentColumns();
        availableEl.innerHTML = '';
        Object.keys(cols).forEach(key => {
            const c = cols[key];
            const div = document.createElement('div');
            div.className = 'badge bg-light text-dark border me-1 mb-1 p-2 col-chip';
            div.draggable = true;
            div.dataset.colKey = key;
            div.innerHTML = '<i class="bi bi-grip-vertical text-muted"></i> ' +
                escapeHtml(c.label) + ' <small class="text-muted">' + escapeHtml(c.type) + '</small>';
            div.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/colKey', key);
                e.dataTransfer.effectAllowed = 'copy';
            });
            availableEl.appendChild(div);
        });
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[c]));
    }

    function renderZones() {
        renderColumnsZone();
        renderFiltersZone();
        renderGroupByZone();
        renderOrderByZone();
        renderAggsZone();
        renderChartSelects();
    }

    function makePill(label, onRemove) {
        const pill = document.createElement('span');
        pill.className = 'badge bg-primary me-1 mb-1 p-2';
        pill.innerHTML = escapeHtml(label) + ' <a href="#" class="text-white ms-2 text-decoration-none rm">×</a>';
        pill.querySelector('.rm').addEventListener('click', (e) => { e.preventDefault(); onRemove(); });
        return pill;
    }

    function renderColumnsZone() {
        const el = $('zoneColumns');
        el.innerHTML = '';
        if (!state.columns.length) {
            el.innerHTML = '<div class="text-muted small zone-placeholder">Przeciągnij kolumny tutaj…</div>';
            return;
        }
        const cols = currentColumns();
        state.columns.forEach((k, idx) => {
            const label = cols[k] ? cols[k].label : k;
            el.appendChild(makePill(label, () => {
                state.columns.splice(idx, 1);
                renderZones();
            }));
        });
    }

    function renderFiltersZone() {
        const el = $('zoneFilters');
        el.innerHTML = '';
        if (!state.filters.length) {
            el.innerHTML = '<div class="text-muted small zone-placeholder">Przeciągnij kolumny tutaj, aby filtrować…</div>';
            return;
        }
        const cols = currentColumns();
        state.filters.forEach((f, idx) => {
            const wrap = document.createElement('div');
            wrap.className = 'input-group input-group-sm mb-1';
            const colLabel = cols[f.field] ? cols[f.field].label : f.field;
            let valHtml = '<input type="text" class="form-control filter-val" value="' + escapeHtml(Array.isArray(f.value) ? f.value.join(',') : (f.value ?? '')) + '">';
            if (f.op === 'IS NULL' || f.op === 'IS NOT NULL') {
                valHtml = '<input type="text" class="form-control" value="—" disabled>';
            }
            wrap.innerHTML =
                '<span class="input-group-text bg-light"><strong>' + escapeHtml(colLabel) + '</strong></span>' +
                '<select class="form-select filter-op" style="max-width:110px;">' +
                    OPERATORS.map(o => '<option value="' + o + '"' + (o === f.op ? ' selected' : '') + '>' + o + '</option>').join('') +
                '</select>' +
                valHtml +
                '<button type="button" class="btn btn-outline-danger rm-filter">×</button>';
            wrap.querySelector('.filter-op').addEventListener('change', (e) => {
                state.filters[idx].op = e.target.value;
                renderZones();
            });
            const valInput = wrap.querySelector('.filter-val');
            if (valInput) {
                valInput.addEventListener('input', (e) => {
                    let v = e.target.value;
                    if (state.filters[idx].op === 'IN' || state.filters[idx].op === 'NOT IN') {
                        v = v.split(',').map(s => s.trim()).filter(s => s !== '');
                    }
                    state.filters[idx].value = v;
                });
            }
            wrap.querySelector('.rm-filter').addEventListener('click', () => {
                state.filters.splice(idx, 1);
                renderZones();
            });
            el.appendChild(wrap);
        });
    }

    function renderGroupByZone() {
        const el = $('zoneGroupBy');
        el.innerHTML = '';
        if (!state.group_by.length) {
            el.innerHTML = '<div class="text-muted small zone-placeholder">Opcjonalne — grupowanie danych…</div>';
            return;
        }
        const cols = currentColumns();
        state.group_by.forEach((k, idx) => {
            const label = cols[k] ? cols[k].label : k;
            el.appendChild(makePill(label, () => {
                state.group_by.splice(idx, 1);
                renderZones();
            }));
        });
    }

    function renderOrderByZone() {
        const el = $('zoneOrderBy');
        el.innerHTML = '';
        if (!state.order_by.length) {
            el.innerHTML = '<div class="text-muted small zone-placeholder">Opcjonalne — sortowanie…</div>';
            return;
        }
        const cols = currentColumns();
        state.order_by.forEach((o, idx) => {
            const wrap = document.createElement('div');
            wrap.className = 'input-group input-group-sm mb-1';
            const colLabel = cols[o.field] ? cols[o.field].label : o.field;
            wrap.innerHTML =
                '<span class="input-group-text bg-light">' + escapeHtml(colLabel) + '</span>' +
                '<select class="form-select order-dir" style="max-width:110px;">' +
                    '<option value="asc"' + (o.dir === 'asc' ? ' selected' : '') + '>↑ rosnąco</option>' +
                    '<option value="desc"' + (o.dir === 'desc' ? ' selected' : '') + '>↓ malejąco</option>' +
                '</select>' +
                '<button type="button" class="btn btn-outline-danger rm-ord">×</button>';
            wrap.querySelector('.order-dir').addEventListener('change', (e) => {
                state.order_by[idx].dir = e.target.value;
            });
            wrap.querySelector('.rm-ord').addEventListener('click', () => {
                state.order_by.splice(idx, 1);
                renderZones();
            });
            el.appendChild(wrap);
        });
    }

    function renderAggsZone() {
        const el = $('zoneAggs');
        el.innerHTML = '';
        if (!state.aggregations.length) {
            el.innerHTML = '<div class="text-muted small zone-placeholder">Opcjonalne — SUM/AVG/COUNT…</div>';
            return;
        }
        const cols = currentColumns();
        state.aggregations.forEach((a, idx) => {
            const wrap = document.createElement('div');
            wrap.className = 'input-group input-group-sm mb-1';
            const colLabel = cols[a.field] ? cols[a.field].label : a.field;
            wrap.innerHTML =
                '<select class="form-select agg-fn" style="max-width:100px;">' +
                    AGG_FNS.map(fn => '<option value="' + fn + '"' + (a.fn === fn ? ' selected' : '') + '>' + fn.toUpperCase() + '</option>').join('') +
                '</select>' +
                '<span class="input-group-text">' + escapeHtml(colLabel) + ' →</span>' +
                '<input type="text" class="form-control agg-alias" placeholder="alias" value="' + escapeHtml(a.alias) + '">' +
                '<button type="button" class="btn btn-outline-danger rm-agg">×</button>';
            wrap.querySelector('.agg-fn').addEventListener('change', (e) => {
                state.aggregations[idx].fn = e.target.value;
                renderChartSelects();
            });
            wrap.querySelector('.agg-alias').addEventListener('input', (e) => {
                state.aggregations[idx].alias = e.target.value.replace(/[^a-zA-Z0-9_]/g, '_').slice(0, 50);
                renderChartSelects();
            });
            wrap.querySelector('.rm-agg').addEventListener('click', () => {
                state.aggregations.splice(idx, 1);
                renderZones();
            });
            el.appendChild(wrap);
        });
    }

    function renderChartSelects() {
        const cols = currentColumns();
        const xSel = $('chartX'), ySel = $('chartY'), tSel = $('chartType');
        const allKeys = [
            ...state.columns,
            ...state.group_by,
            ...state.aggregations.map(a => a.alias).filter(Boolean),
        ];
        const opts = (sel) => '<option value="">— wybierz —</option>' + allKeys.map(k => {
            const label = cols[k] ? cols[k].label : k;
            return '<option value="' + escapeHtml(k) + '"' + (sel === k ? ' selected' : '') + '>' + escapeHtml(label) + '</option>';
        }).join('');
        xSel.innerHTML = opts(state.chart.x || '');
        ySel.innerHTML = opts(state.chart.y || '');
        tSel.value = state.chart.type || 'none';
    }

    // Drop zone setup
    function setupDropZones() {
        document.querySelectorAll('.drop-zone').forEach(zone => {
            zone.addEventListener('dragover', (e) => {
                e.preventDefault();
                zone.classList.add('bg-light');
            });
            zone.addEventListener('dragleave', () => zone.classList.remove('bg-light'));
            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                zone.classList.remove('bg-light');
                const key = e.dataTransfer.getData('text/colKey');
                if (!key) return;
                const z = zone.dataset.zone;
                handleDrop(z, key);
            });
            // Click-to-add fallback for screen readers/mobile: dblclick on available adds to columns
        });
    }

    function handleDrop(zone, key) {
        if (zone === 'columns') {
            if (!state.columns.includes(key)) state.columns.push(key);
        } else if (zone === 'filters') {
            state.filters.push({ field: key, op: '=', value: '' });
        } else if (zone === 'group_by') {
            if (!state.group_by.includes(key)) state.group_by.push(key);
        } else if (zone === 'order_by') {
            if (!state.order_by.find(o => o.field === key)) state.order_by.push({ field: key, dir: 'asc' });
        } else if (zone === 'aggregations') {
            state.aggregations.push({ field: key, fn: 'count', alias: 'agg_' + (state.aggregations.length + 1) });
        }
        renderZones();
    }

    // Klik na dostępną kolumnę → dodaj do columns (mobile-friendly)
    availableEl.addEventListener('click', (e) => {
        const chip = e.target.closest('.col-chip');
        if (!chip) return;
        const key = chip.dataset.colKey;
        if (!state.columns.includes(key)) state.columns.push(key);
        renderZones();
    });

    // Chart inputs
    $('chartType').addEventListener('change', (e) => { state.chart.type = e.target.value; });
    $('chartX').addEventListener('change', (e) => { state.chart.x = e.target.value; });
    $('chartY').addEventListener('change', (e) => { state.chart.y = e.target.value; });
    $('limit').addEventListener('input', (e) => {
        let v = parseInt(e.target.value, 10) || 1000;
        if (v < 1) v = 1;
        if (v > 10000) v = 10000;
        state.limit = v;
    });
    $('limit').value = state.limit;

    // Source change → reset state (kolumny w innym źródle to inne klucze)
    sourceSelect.addEventListener('change', (e) => {
        if (state.columns.length || state.filters.length || state.group_by.length
            || state.order_by.length || state.aggregations.length) {
            if (!confirm('Zmiana źródła zresetuje wybrane kolumny/filtry. Kontynuować?')) {
                e.target.value = state.source;
                return;
            }
        }
        state.source = e.target.value;
        state.columns = []; state.filters = []; state.group_by = [];
        state.order_by = []; state.aggregations = [];
        state.chart = { type: 'none', x: '', y: '' };
        renderAvailable();
        renderZones();
    });

    // Preview
    $('btnPreview').addEventListener('click', async () => {
        const box = $('previewBox');
        const content = $('previewContent');
        box.classList.remove('d-none');
        content.innerHTML = '<div class="text-center text-muted p-3"><i class="bi bi-hourglass-split"></i> Ładowanie…</div>';
        const fd = new FormData();
        fd.append('_csrf', CSRF);
        fd.append('data_source', state.source);
        fd.append('config_json', JSON.stringify(buildConfig()));
        try {
            const res = await fetch(PREVIEW_URL, { method: 'POST', body: fd });
            const data = await res.json();
            if (!data.ok) {
                content.innerHTML = '<div class="alert alert-danger small mb-0">' + escapeHtml(data.error || 'Błąd') + '</div>';
                return;
            }
            renderPreviewTable(content, data.result);
        } catch (err) {
            content.innerHTML = '<div class="alert alert-danger small mb-0">Błąd sieci.</div>';
        }
    });

    function renderPreviewTable(container, result) {
        if (!result.rows.length) {
            container.innerHTML = '<div class="text-muted p-2">Brak wyników.</div>';
            return;
        }
        let html = '<div class="small text-muted mb-1">Zwrócono ' + result.total + ' wierszy w ' + result.duration_ms + ' ms.</div>';
        html += '<table class="table table-sm table-bordered mb-0"><thead><tr>';
        result.columns.forEach(c => html += '<th>' + escapeHtml(c.label) + '</th>');
        html += '</tr></thead><tbody>';
        result.rows.forEach(r => {
            html += '<tr>';
            result.columns.forEach(c => html += '<td>' + escapeHtml(r[c.key] ?? '') + '</td>');
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function buildConfig() {
        return {
            columns: state.columns,
            filters: state.filters,
            group_by: state.group_by,
            order_by: state.order_by,
            aggregations: state.aggregations,
            chart: state.chart,
            limit: state.limit,
        };
    }

    // Submit — pakujemy config do hidden inputa
    document.getElementById('reportBuilderForm').addEventListener('submit', () => {
        document.getElementById('config_json').value = JSON.stringify(buildConfig());
    });

    // Init
    renderAvailable();
    setupDropZones();
    renderZones();
})();
</script>

<style>
.drop-zone { transition: background 0.15s; }
.col-chip { cursor: grab; user-select: none; }
.col-chip:active { cursor: grabbing; }
.zone-placeholder { padding: 8px 0; }
</style>
