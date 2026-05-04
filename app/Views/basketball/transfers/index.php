<?php use App\Helpers\View; ?>
<div class="mb-3 text-end">
    <a href="<?= url('basketball/transfers/create') ?>" class="btn btn-success"><i class="bi bi-plus"></i> Nowy transfer</a>
</div>
<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Data</th><th>Zawodnik</th><th>Kierunek</th><th>Z/Do</th><th>Kwota</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($pagination['data'])): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Brak transferów.</td></tr>
        <?php else: foreach ($pagination['data'] as $t):
            $dir_cls = match($t['direction']) { 'przychodzacy'=>'success', 'odchodzacy'=>'danger', default=>'warning' };
        ?>
            <tr>
                <td><?= format_date($t['transfer_date']) ?></td>
                <td><?= View::e($t['last_name']) ?> <?= View::e($t['first_name']) ?></td>
                <td><span class="badge bg-<?= $dir_cls ?>"><?= View::e($t['direction']) ?></span></td>
                <td><?= View::e($t['from_club'] ?? '') ?> &rarr; <?= View::e($t['to_club'] ?? '') ?></td>
                <td><?= $t['fee'] !== null ? format_money($t['fee']) : '—' ?></td>
                <td class="text-end">
                    <form method="POST" action="<?= url('basketball/transfers/' . (int)$t['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')" class="d-inline">
                        <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
