<?php
/**
 * Reużywalny empty state — pokazuje przyjazną ilustrację gdy lista jest pusta.
 *
 * @var string  $icon       Klasa ikony (bi-people, bi-trophy, etc.)
 * @var string  $title      Główny komunikat (np. "Brak zawodników")
 * @var string  $message    Drugi komunikat — zachęta lub wyjaśnienie
 * @var string|null $actionUrl  URL przycisku CTA (opcjonalne)
 * @var string|null $actionLabel Etykieta CTA
 * @var string|null $helpUrl    URL do "Dowiedz się więcej"
 *
 * Użycie:
 *   <?php $icon='bi-people'; $title='Brak zawodników'; $message='Dodaj pierwszego zawodnika lub zaimportuj z CSV.'; $actionUrl=url('members/new'); $actionLabel='+ Dodaj zawodnika'; include __DIR__ . '/../_partials/empty_state.php'; ?>
 */
use App\Helpers\View;

$icon       = $icon       ?? 'bi-inbox';
$title      = $title      ?? 'Brak danych';
$message    = $message    ?? '';
$actionUrl  = $actionUrl  ?? null;
$actionLabel= $actionLabel?? null;
$helpUrl    = $helpUrl    ?? null;
?>

<div class="empty-state text-center py-5" style="padding: 4rem 1rem !important;">
    <div class="mb-3">
        <i class="bi <?= View::e($icon) ?>" style="font-size: 4rem; color: #cbd5e1; opacity: .6;"></i>
    </div>
    <h4 class="mb-2 text-secondary"><?= View::e($title) ?></h4>
    <?php if ($message): ?>
        <p class="text-muted mb-3" style="max-width: 480px; margin: 0 auto;">
            <?= View::e($message) ?>
        </p>
    <?php endif; ?>
    <?php if ($actionUrl && $actionLabel): ?>
        <div class="mt-3">
            <a href="<?= View::e($actionUrl) ?>" class="btn btn-primary">
                <?= View::e($actionLabel) ?>
            </a>
            <?php if ($helpUrl): ?>
                <a href="<?= View::e($helpUrl) ?>" class="btn btn-link text-muted">
                    <i class="bi bi-question-circle"></i> Jak to działa?
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
