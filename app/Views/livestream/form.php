<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('livestream/store') ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8"><label class="form-label">Tytuł *</label>
            <input type="text" name="title" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Platforma</label>
            <select name="platform" class="form-select">
                <option value="youtube">YouTube</option>
                <option value="twitch">Twitch</option>
                <option value="facebook">Facebook</option>
                <option value="inne">Inna</option>
            </select></div>
        <div class="col-md-8"><label class="form-label">URL transmisji *</label>
            <input type="url" name="stream_url" class="form-control" required placeholder="https://youtube.com/live/..."></div>
        <div class="col-md-4"><label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="zaplanowana">Zaplanowana</option>
                <option value="na_zywo">Na żywo</option>
            </select></div>
        <div class="col-md-6"><label class="form-label">Zaplanowana na</label>
            <input type="datetime-local" name="scheduled_at" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Powiązane wydarzenie</label>
            <select name="event_id" class="form-select">
                <option value="">— brak —</option>
                <?php foreach ($events as $e): ?>
                    <option value="<?= (int)$e['id'] ?>"><?= View::e($e['name']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-12">
            <div class="form-check"><input type="checkbox" name="is_public" value="1" checked class="form-check-input" id="pub">
            <label for="pub" class="form-check-label">Publiczna (widoczna na stronie klubu)</label></div>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('livestream') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
