<?php
/**
 * @var array              $invoice
 * @var array<int,array>   $items
 * @var array<int,array>   $members
 * @var string             $mode  'create' | 'edit'
 */
use App\Helpers\View;

$isEdit  = ($mode ?? 'create') === 'edit';
$postUrl = $isEdit
    ? url('club/invoices/' . (int)$invoice['id'] . '/update')
    : url('club/invoices/store');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-receipt me-2"></i>
        <?= $isEdit ? 'Edycja szkicu faktury' : 'Nowa faktura sprzedaży' ?>
    </h3>
    <a href="<?= url('club/invoices') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= View::e($flashError) ?></div>
<?php endif; ?>

<form method="POST" action="<?= View::e($postUrl) ?>">
    <?= csrf_field() ?>

    <div class="row g-3">
        <!-- Nabywca -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header py-2"><strong><i class="bi bi-person"></i> Nabywca</strong></div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label small">Wybierz członka (opcjonalnie)</label>
                        <select name="buyer_member_id" id="buyerMember" class="form-select form-select-sm">
                            <option value="">— brak (manualny nabywca) —</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= (int)$m['id'] ?>"
                                    data-name="<?= View::e(($m['last_name'] ?? '') . ' ' . ($m['first_name'] ?? '')) ?>"
                                    data-email="<?= View::e($m['email'] ?? '') ?>"
                                    <?= (int)($invoice['buyer_member_id'] ?? 0) === (int)$m['id'] ? 'selected' : '' ?>>
                                    <?= View::e(($m['last_name'] ?? '') . ' ' . ($m['first_name'] ?? '')) ?>
                                    (#<?= View::e($m['member_number'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Nazwa nabywcy *</label>
                        <input type="text" name="buyer_name" id="buyerName" required class="form-control form-control-sm"
                               value="<?= View::e((string)($invoice['buyer_name'] ?? '')) ?>">
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">NIP (10 cyfr, B2B)</label>
                            <input type="text" name="buyer_nip" pattern="\d{10}" maxlength="13"
                                   class="form-control form-control-sm font-monospace"
                                   value="<?= View::e((string)($invoice['buyer_nip'] ?? '')) ?>"
                                   placeholder="puste = B2C">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Email</label>
                            <input type="email" name="buyer_email" id="buyerEmail" class="form-control form-control-sm"
                                   value="<?= View::e((string)($invoice['buyer_email'] ?? '')) ?>">
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label small">Adres</label>
                        <textarea name="buyer_address" rows="2" class="form-control form-control-sm"><?= View::e((string)($invoice['buyer_address'] ?? '')) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meta -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header py-2"><strong><i class="bi bi-calendar-event"></i> Dane faktury</strong></div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Rodzaj</label>
                            <select name="invoice_type" class="form-select form-select-sm">
                                <?php foreach (['VAT'=>'VAT','proforma'=>'Proforma','paragon'=>'Paragon','VAT_korekta'=>'VAT korekta','VAT_RR'=>'VAT RR'] as $k=>$lab): ?>
                                    <option value="<?= $k ?>" <?= ($invoice['invoice_type'] ?? 'VAT') === $k ? 'selected' : '' ?>><?= $lab ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Numer</label>
                            <input type="text" class="form-control form-control-sm" disabled
                                   value="<?= View::e((string)($invoice['invoice_number'] ?? 'DRAFT')) ?>">
                            <small class="text-muted">Nadany przy wystawieniu.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Data wystawienia *</label>
                            <input type="date" name="issue_date" required class="form-control form-control-sm"
                                   value="<?= View::e((string)($invoice['issue_date'] ?? date('Y-m-d'))) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Data sprzedaży *</label>
                            <input type="date" name="sale_date" required class="form-control form-control-sm"
                                   value="<?= View::e((string)($invoice['sale_date'] ?? date('Y-m-d'))) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Termin płatności</label>
                            <input type="date" name="due_date" class="form-control form-control-sm"
                                   value="<?= View::e((string)($invoice['due_date'] ?? '')) ?>">
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label small">Uwagi</label>
                        <textarea name="notes" rows="2" class="form-control form-control-sm"><?= View::e((string)($invoice['notes'] ?? '')) ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pozycje -->
    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <strong><i class="bi bi-list-ul"></i> Pozycje</strong>
            <button type="button" id="addItemBtn" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-plus-lg"></i> Dodaj pozycję
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle" id="itemsTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:32%">Opis *</th>
                        <th style="width:9%">Ilość</th>
                        <th style="width:8%">Jedn.</th>
                        <th style="width:13%">Cena netto</th>
                        <th style="width:9%">VAT %</th>
                        <th style="width:11%">Netto</th>
                        <th style="width:11%">Brutto</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr class="invoice-item-row">
                            <td>
                                <input type="text" name="item_description[]" required class="form-control form-control-sm"
                                       value="<?= View::e((string)($it['description'] ?? '')) ?>">
                                <small class="d-block text-muted mt-1">
                                    <input type="text" name="item_pkwiu[]" placeholder="PKWiU" class="form-control form-control-sm d-inline-block w-50"
                                           value="<?= View::e((string)($it['pkwiu'] ?? '')) ?>" maxlength="20">
                                    <input type="text" name="item_gtu_code[]" placeholder="GTU" class="form-control form-control-sm d-inline-block w-25"
                                           value="<?= View::e((string)($it['gtu_code'] ?? '')) ?>" maxlength="5">
                                </small>
                            </td>
                            <td>
                                <input type="number" step="0.001" min="0" name="item_quantity[]" required
                                       class="form-control form-control-sm item-calc"
                                       value="<?= View::e((string)($it['quantity'] ?? 1)) ?>">
                            </td>
                            <td>
                                <input type="text" name="item_unit[]" class="form-control form-control-sm" maxlength="20"
                                       value="<?= View::e((string)($it['unit'] ?? 'szt.')) ?>">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="item_unit_price_net[]" required
                                       class="form-control form-control-sm item-calc"
                                       value="<?= View::e((string)($it['unit_price_net'] ?? 0)) ?>">
                            </td>
                            <td>
                                <select name="item_vat_rate[]" class="form-select form-select-sm item-calc">
                                    <?php foreach (['23'=>'23%','8'=>'8%','5'=>'5%','0'=>'0%','-1'=>'ZW','-2'=>'NP'] as $v=>$lab): ?>
                                        <option value="<?= $v ?>" <?= (string)(float)($it['vat_rate'] ?? 23) === (string)(float)$v ? 'selected' : '' ?>><?= $lab ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="text-end small text-muted item-net">0,00</td>
                            <td class="text-end small text-muted item-gross">0,00</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Usuń">
                                    <i class="bi bi-x"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="5" class="text-end"><strong>Razem:</strong></td>
                        <td class="text-end"><strong id="totalNet">0,00</strong></td>
                        <td class="text-end"><strong id="totalGross">0,00</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-3">
        <a href="<?= url('club/invoices') ?>" class="btn btn-outline-secondary">Anuluj</a>
        <button class="btn btn-primary">
            <i class="bi bi-save"></i> Zapisz <?= $isEdit ? 'zmiany' : 'szkic' ?>
        </button>
    </div>
</form>

<script>
(function() {
    var table = document.getElementById('itemsTable');
    var tbody = table.querySelector('tbody');
    var nf = new Intl.NumberFormat('pl-PL', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    function recalcRow(row) {
        var qty   = parseFloat(row.querySelector('[name="item_quantity[]"]').value)       || 0;
        var price = parseFloat(row.querySelector('[name="item_unit_price_net[]"]').value) || 0;
        var rate  = parseFloat(row.querySelector('[name="item_vat_rate[]"]').value)       || 0;
        var net   = Math.round(qty * price * 100) / 100;
        var vat   = rate >= 0 ? Math.round(net * (rate/100) * 100) / 100 : 0;
        var gross = Math.round((net + vat) * 100) / 100;
        row.querySelector('.item-net').textContent   = nf.format(net);
        row.querySelector('.item-gross').textContent = nf.format(gross);
        return {net: net, gross: gross};
    }

    function recalcAll() {
        var totN = 0, totG = 0;
        tbody.querySelectorAll('.invoice-item-row').forEach(function(row) {
            var r = recalcRow(row);
            totN += r.net; totG += r.gross;
        });
        document.getElementById('totalNet').textContent   = nf.format(totN);
        document.getElementById('totalGross').textContent = nf.format(totG);
    }

    table.addEventListener('input', function(e) {
        if (e.target.matches('.item-calc')) recalcAll();
    });
    table.addEventListener('change', function(e) {
        if (e.target.matches('.item-calc')) recalcAll();
    });

    table.addEventListener('click', function(e) {
        var btn = e.target.closest('.remove-item');
        if (btn) {
            if (tbody.querySelectorAll('.invoice-item-row').length > 1) {
                btn.closest('tr').remove();
                recalcAll();
            }
        }
    });

    document.getElementById('addItemBtn').addEventListener('click', function() {
        var firstRow = tbody.querySelector('.invoice-item-row');
        var clone = firstRow.cloneNode(true);
        clone.querySelectorAll('input[type="text"], input[type="number"]').forEach(function(i) {
            if (i.name === 'item_quantity[]')    i.value = 1;
            else if (i.name === 'item_unit[]')   i.value = 'szt.';
            else if (i.name === 'item_unit_price_net[]') i.value = 0;
            else i.value = '';
        });
        // reset VAT select to 23
        var sel = clone.querySelector('select[name="item_vat_rate[]"]');
        if (sel) sel.value = '23';
        tbody.appendChild(clone);
        recalcAll();
    });

    // Auto-fill buyer when member selected
    var memSel = document.getElementById('buyerMember');
    if (memSel) {
        memSel.addEventListener('change', function() {
            var opt = memSel.options[memSel.selectedIndex];
            if (!opt || !opt.value) return;
            var nameInput  = document.getElementById('buyerName');
            var emailInput = document.getElementById('buyerEmail');
            if (nameInput && !nameInput.value) nameInput.value = opt.getAttribute('data-name') || '';
            if (emailInput && !emailInput.value) emailInput.value = opt.getAttribute('data-email') || '';
        });
    }

    recalcAll();
})();
</script>
