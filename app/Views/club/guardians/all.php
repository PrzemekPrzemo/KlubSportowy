<?php
use App\Helpers\View;
?>
<div class="container py-3">
    <h1 class="h4">Opiekunowie klubu</h1>
    <p class="text-muted small">
        Wszyscy opiekunowie zaproszeni do portalu rodzica w tym klubie.
    </p>

    <?php if (empty($guardians)): ?>
        <div class="alert alert-info">Brak opiekunow. Mozesz ich zapraszac z profilu czlonka.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>E-mail</th>
                        <th>Imie i nazwisko</th>
                        <th>Telefon</th>
                        <th>Dzieci</th>
                        <th>Status</th>
                        <th>Ostatnie logowanie</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($guardians as $g): ?>
                        <tr>
                            <td><?= View::e($g['email']) ?></td>
                            <td><?= View::e(trim(($g['first_name'] ?? '') . ' ' . ($g['last_name'] ?? ''))) ?></td>
                            <td><?= View::e($g['phone'] ?? '') ?></td>
                            <td><span class="badge bg-secondary"><?= (int)($g['children_count'] ?? 0) ?></span></td>
                            <td>
                                <?php if (!empty($g['email_verified_at'])): ?>
                                    <span class="badge bg-success">Aktywny</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Zaproszony</span>
                                <?php endif; ?>
                            </td>
                            <td><?= View::e($g['last_login_at'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
