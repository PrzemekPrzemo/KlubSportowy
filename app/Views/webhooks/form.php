<?php use App\Helpers\View; ?>

<div class="card">
    <div class="card-header"><i class="bi bi-link-45deg"></i> Nowy webhook endpoint</div>
    <div class="card-body">
        <form method="POST" action="<?= url('club/webhooks/store') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="url" class="form-label">URL endpointu <span class="text-danger">*</span></label>
                <input type="url" name="url" id="url" class="form-control"
                       placeholder="https://example.com/webhook" required
                       value="<?= View::e(old('url')) ?>">
                <div class="form-text">Adres URL, na ktory beda wysylane powiadomienia (POST, JSON).</div>
            </div>

            <div class="mb-3">
                <label for="secret" class="form-label">Secret (klucz HMAC) <span class="text-danger">*</span></label>
                <input type="text" name="secret" id="secret" class="form-control"
                       placeholder="wpisz-tajny-klucz" required
                       value="<?= View::e(old('secret')) ?>">
                <div class="form-text">Uzywany do generowania sygnatury HMAC-SHA256 w naglowku <code>X-Webhook-Signature</code>.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Subskrybowane eventy <span class="text-danger">*</span></label>
                <div class="row">
                    <?php foreach ($availableEvents as $ev): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="events[]" value="<?= View::e($ev) ?>"
                                       id="ev_<?= View::e($ev) ?>" class="form-check-input">
                                <label for="ev_<?= View::e($ev) ?>" class="form-check-label">
                                    <code><?= View::e($ev) ?></code>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Zapisz</button>
                <a href="<?= url('club/webhooks') ?>" class="btn btn-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>
