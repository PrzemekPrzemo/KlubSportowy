<?php use App\Helpers\View; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-image"></i> Zdjecia wynikow</h2>
        <a href="<?= url('results/upload') ?>" class="btn btn-primary">
            <i class="bi bi-upload"></i> Dodaj zdjecie
        </a>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger"><?= View::e($flashError) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Miniatura</th>
                        <th>Plik</th>
                        <th>Wydarzenie</th>
                        <th>Zawodnik</th>
                        <th>Sport</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pagination['data'])): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Brak zdjec.</td></tr>
                    <?php else: ?>
                        <?php
                        $statusColors = ['uploaded' => 'secondary', 'processed' => 'warning', 'verified' => 'success'];
                        $statusLabels = ['uploaded' => 'Przeslane', 'processed' => 'Przetworzono', 'verified' => 'Zweryfikowane'];
                        foreach ($pagination['data'] as $img): ?>
                            <tr>
                                <td>
                                    <a href="<?= url('results/' . $img['id']) ?>">
                                        <img src="<?= url($img['image_path']) ?>" alt="" style="width:60px;height:40px;object-fit:cover;border-radius:4px;">
                                    </a>
                                </td>
                                <td><small><?= View::e($img['original_filename']) ?></small></td>
                                <td><?= View::e($img['event_name'] ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($img['first_name'])): ?>
                                        <?= View::e($img['last_name'] . ' ' . $img['first_name']) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= View::e($img['sport_name'] ?? '-') ?></td>
                                <td>
                                    <span class="badge bg-<?= $statusColors[$img['status']] ?? 'secondary' ?>">
                                        <?= View::e($statusLabels[$img['status']] ?? $img['status']) ?>
                                    </span>
                                </td>
                                <td><?= format_datetime($img['created_at']) ?></td>
                                <td>
                                    <a href="<?= url('results/' . $img['id']) ?>" class="btn btn-sm btn-outline-primary" title="Szczegoly">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form method="POST" action="<?= url('results/' . $img['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Usunac zdjecie?')">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger" title="Usun"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (($pagination['last_page'] ?? 1) > 1): ?>
        <nav class="mt-3">
            <ul class="pagination justify-content-center">
                <?php for ($p = 1; $p <= $pagination['last_page']; $p++): ?>
                    <li class="page-item <?= $p === $pagination['current_page'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url('results?page=' . $p) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>
