<?php use App\Helpers\View; ?><!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($title ?? 'Onboarding') ?> — <?= View::e($appName ?? 'KlubSportowy') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; min-height: 100vh; font-family: system-ui, -apple-system, sans-serif; }
        .onboarding-header { background: #fff; border-bottom: 1px solid #dee2e6; padding: 1rem 0; }
        .onboarding-brand { font-size: 1.4rem; font-weight: 700; color: #0d6efd; text-decoration: none; }
        .progress-steps { display: flex; justify-content: center; gap: 0; max-width: 700px; margin: 0 auto; padding: 1rem 0; }
        .progress-step { flex: 1; text-align: center; position: relative; }
        .progress-step .step-circle {
            width: 36px; height: 36px; border-radius: 50%; display: inline-flex;
            align-items: center; justify-content: center; font-weight: 600; font-size: .85rem;
            border: 2px solid #dee2e6; background: #fff; color: #6c757d; position: relative; z-index: 1;
        }
        .progress-step.active .step-circle { border-color: #0d6efd; background: #0d6efd; color: #fff; }
        .progress-step.done .step-circle { border-color: #198754; background: #198754; color: #fff; }
        .progress-step .step-label { display: block; font-size: .72rem; color: #6c757d; margin-top: .3rem; }
        .progress-step.active .step-label { color: #0d6efd; font-weight: 600; }
        .progress-step.done .step-label { color: #198754; }
        .progress-step::before {
            content: ''; position: absolute; top: 18px; left: 0; right: 50%;
            height: 2px; background: #dee2e6; z-index: 0;
        }
        .progress-step::after {
            content: ''; position: absolute; top: 18px; left: 50%; right: 0;
            height: 2px; background: #dee2e6; z-index: 0;
        }
        .progress-step:first-child::before { display: none; }
        .progress-step:last-child::after { display: none; }
        .progress-step.done::before, .progress-step.done::after { background: #198754; }
        .progress-step.active::before { background: #198754; }
        .onboarding-content { max-width: 800px; margin: 2rem auto; padding: 0 1rem; }
        .card { border: 0; box-shadow: 0 1px 3px rgba(0,0,0,.08); border-radius: 12px; }
    </style>
</head>
<body>

<div class="onboarding-header">
    <div class="container">
        <div class="text-center">
            <a href="<?= url('dashboard') ?>" class="onboarding-brand">
                <i class="bi bi-trophy"></i> <?= View::e($appName ?? 'KlubSportowy') ?>
            </a>
        </div>
        <?php
        $currentStep = $currentStep ?? 1;
        $steps = [
            1 => 'Dane klubu',
            2 => 'Sporty',
            3 => 'Branding',
            4 => 'Zawodnicy',
            5 => 'Podsumowanie',
        ];
        ?>
        <div class="progress-steps">
            <?php foreach ($steps as $num => $label): ?>
                <?php
                $class = '';
                if ($num < $currentStep) $class = 'done';
                elseif ($num === $currentStep) $class = 'active';
                ?>
                <div class="progress-step <?= $class ?>">
                    <span class="step-circle">
                        <?php if ($num < $currentStep): ?>
                            <i class="bi bi-check"></i>
                        <?php else: ?>
                            <?= $num ?>
                        <?php endif; ?>
                    </span>
                    <span class="step-label"><?= View::e($label) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="onboarding-content">
    <?php foreach (['flashSuccess'=>'success','flashError'=>'danger','flashWarning'=>'warning','flashInfo'=>'info'] as $k => $cls): ?>
        <?php if (!empty($$k)): ?>
            <div class="alert alert-<?= $cls ?> alert-dismissible fade show">
                <?= View::e($$k) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?= $content ?? '' ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
