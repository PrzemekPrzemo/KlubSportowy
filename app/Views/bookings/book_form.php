<?php use App\Helpers\View; ?>

<div class="row">
    <!-- Booking form -->
    <div class="col-lg-5 mb-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-calendar-plus"></i> Nowa rezerwacja</h6></div>
            <div class="card-body">
                <form method="POST" action="<?= url('bookings/book') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Obiekt</label>
                        <select name="facility_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach (($facilities ?? []) as $f): ?>
                                <option value="<?= (int)$f['id'] ?>"><?= View::e($f['name']) ?> (<?= View::e($f['type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tytuł</label>
                        <input type="text" name="title" class="form-control" required maxlength="150">
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Od</label>
                            <input type="datetime-local" name="start_time" class="form-control" required>
                        </div>
                        <div class="col">
                            <label class="form-label">Do</label>
                            <input type="datetime-local" name="end_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dla zawodnika (opcjonalnie)</label>
                        <select name="booked_for_id" class="form-select">
                            <option value="">— brak —</option>
                            <?php foreach (($members ?? []) as $m): ?>
                                <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notatki</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-check"></i> Zarezerwuj</button>
                </form>
            </div>
        </div>
    </div>

    <!-- My bookings list -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-list-ul"></i> Moje rezerwacje</h6>
                <a href="<?= url('bookings/calendar') ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-calendar-week"></i> Kalendarz
                </a>
            </div>
            <?php if (empty($pagination['data'])): ?>
                <div class="card-body text-center text-muted">Brak rezerwacji.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Obiekt</th>
                                <th>Tytuł</th>
                                <th>Od</th>
                                <th>Do</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagination['data'] as $b):
                                $cls = match($b['status']) {
                                    'confirmed' => 'success',
                                    'pending'   => 'warning',
                                    'cancelled' => 'secondary',
                                    default     => 'secondary',
                                };
                            ?>
                                <tr>
                                    <td><?= View::e($b['facility_name'] ?? '—') ?></td>
                                    <td><?= View::e($b['title']) ?></td>
                                    <td><?= format_datetime($b['start_time']) ?></td>
                                    <td><?= format_datetime($b['end_time']) ?></td>
                                    <td><span class="badge bg-<?= $cls ?>"><?= View::e($b['status']) ?></span></td>
                                    <td>
                                        <?php if ($b['status'] !== 'cancelled'): ?>
                                            <form method="POST" action="<?= url('bookings/' . (int)$b['id'] . '/cancel') ?>"
                                                  onsubmit="return confirm('Anulować rezerwację?')" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($pagination['last_page'] > 1): ?>
                    <div class="card-footer">
                        <nav>
                            <ul class="pagination pagination-sm justify-content-center mb-0">
                                <?php for ($p = 1; $p <= $pagination['last_page']; $p++): ?>
                                    <li class="page-item <?= $p === $pagination['current_page'] ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= url('bookings?page=' . $p) ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
