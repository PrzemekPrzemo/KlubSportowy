<?php use App\Helpers\View; ?>

<div class="mb-3">
    <a href="<?= url('admin/clubs') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Wróć do klubów</a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-sliders"></i> Konfiguracja klubu: <?= View::e($club['name'] ?? '') ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url('admin/clubs/' . (int)$club['id'] . '/config/save') ?>">
            <?= csrf_field() ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:25%">Klucz</th>
                            <th style="width:35%">Wartość</th>
                            <th style="width:15%">Typ</th>
                            <th style="width:25%">Etykieta</th>
                        </tr>
                    </thead>
                    <tbody id="settings-body">
                        <?php if (empty($settings)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Brak ustawień. Dodaj nowe poniżej.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($settings as $i => $s): ?>
                                <tr>
                                    <td>
                                        <input type="text" name="keys[<?= $i ?>]" value="<?= View::e($s['key']) ?>" class="form-control form-control-sm" readonly>
                                    </td>
                                    <td>
                                        <input type="text" name="values[<?= $i ?>]" value="<?= View::e($s['value'] ?? '') ?>" class="form-control form-control-sm">
                                    </td>
                                    <td>
                                        <input type="text" name="types[<?= $i ?>]" value="<?= View::e($s['type'] ?? 'text') ?>" class="form-control form-control-sm">
                                    </td>
                                    <td>
                                        <input type="text" name="labels[<?= $i ?>]" value="<?= View::e($s['label'] ?? '') ?>" class="form-control form-control-sm">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="border-top pt-3 mt-2">
                <h6>Dodaj nowe ustawienie</h6>
                <div class="row g-2">
                    <div class="col-md-3">
                        <input type="text" name="keys[new]" class="form-control form-control-sm" placeholder="Klucz">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="values[new]" class="form-control form-control-sm" placeholder="Wartość">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="types[new]" class="form-control form-control-sm" placeholder="Typ" value="text">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="labels[new]" class="form-control form-control-sm" placeholder="Etykieta">
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Zapisz ustawienia</button>
            </div>
        </form>
    </div>
</div>
