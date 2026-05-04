<?php use App\Helpers\View; ?>

<?php if (empty($exams)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>Brak zarejestrowanych badań lekarskich.
</div>
<?php else: ?>
<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Typ badania</th>
                <th>Data badania</th>
                <th>Ważne do</th>
                <th>Lekarz</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($exams as $e):
            $days = (int)$e['days_remaining'];
            if ($days < 0)       { $badge = 'danger';  $label = 'Wygasłe'; }
            elseif ($days <= 30) { $badge = 'warning'; $label = 'Wygasa wkrótce'; }
            else                 { $badge = 'success'; $label = 'Ważne'; }
        ?>
            <tr>
                <td><?= View::e($e['exam_type']) ?></td>
                <td><?= View::e($e['exam_date']) ?></td>
                <td>
                    <?= View::e($e['valid_until']) ?>
                    <?php if ($days >= 0 && $days <= 30): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $days ?> dni</span>
                    <?php elseif ($days < 0): ?>
                        <span class="badge bg-danger ms-1">Wygasłe <?= abs($days) ?> dni temu</span>
                    <?php endif; ?>
                </td>
                <td><?= View::e($e['doctor_name'] ?? '—') ?></td>
                <td><span class="badge bg-<?= $badge ?>"><?= $label ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php
$expiring = array_filter($exams, fn($e) => (int)$e['days_remaining'] >= 0 && (int)$e['days_remaining'] <= 30);
if (!empty($expiring)):
?>
<div class="alert alert-warning mt-3">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Uwaga!</strong> Jedno lub więcej Twoich badań lekarskich wygasa w ciągu 30 dni. Skontaktuj się z trenerem lub sekretariatem klubu.
</div>
<?php endif; ?>
