<?php
use App\Helpers\Csrf;
use App\Helpers\View;
?>
<div class="modal fade" id="inviteGuardianModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?= View::e(url('club/members/' . (int)$member['id'] . '/guardians/invite')) ?>" class="modal-content">
            <?= Csrf::field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Zaproszenie opiekuna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">E-mail opiekuna *</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="row g-2">
                    <div class="col">
                        <label class="form-label">Imie</label>
                        <input type="text" name="first_name" class="form-control" maxlength="200">
                    </div>
                    <div class="col">
                        <label class="form-label">Nazwisko</label>
                        <input type="text" name="last_name" class="form-control" maxlength="200">
                    </div>
                </div>
                <div class="mt-2">
                    <label class="form-label">Telefon</label>
                    <input type="tel" name="phone" class="form-control" maxlength="20">
                </div>
                <div class="mt-2">
                    <label class="form-label">Relacja</label>
                    <select name="relationship" class="form-select">
                        <option value="parent">Rodzic</option>
                        <option value="legal_guardian">Opiekun prawny</option>
                        <option value="grandparent">Dziadek/Babcia</option>
                        <option value="other">Inny</option>
                    </select>
                </div>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="primary_guardian" id="ig-primary">
                    <label class="form-check-label" for="ig-primary">Glowny opiekun</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="can_pay" id="ig-pay" checked>
                    <label class="form-check-label" for="ig-pay">Moze oplacac skladki</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="can_consent" id="ig-cons" checked>
                    <label class="form-check-label" for="ig-cons">Moze udzielac zgod RODO</label>
                </div>
                <p class="small text-muted mt-3 mb-0">
                    Opiekun otrzyma e-mail z linkiem aktywacyjnym (waznym 7 dni).
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                <button type="submit" class="btn btn-primary">Wyslij zaproszenie</button>
            </div>
        </form>
    </div>
</div>
