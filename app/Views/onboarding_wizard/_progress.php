<?php
/** @var int $currentStep */
use App\Helpers\View;
$steps = [
    1 => ['label' => 'Dane klubu', 'icon' => 'bi-building'],
    2 => ['label' => 'Branding',   'icon' => 'bi-palette'],
    3 => ['label' => 'Sporty',     'icon' => 'bi-trophy'],
    4 => ['label' => 'Skladki',    'icon' => 'bi-cash-coin'],
    5 => ['label' => 'Twoje konto','icon' => 'bi-person-circle'],
];
?>
<div class="wizard-progress mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <?php foreach ($steps as $n => $s):
            $active = ($n === ($currentStep ?? 1));
            $done   = ($n < ($currentStep ?? 1));
        ?>
            <div class="text-center flex-fill position-relative">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                     style="width:42px;height:42px;background:<?= $active ? '#EE2C28' : ($done ? '#198754' : '#e9ecef') ?>;color:<?= ($active||$done) ? '#fff' : '#6c757d' ?>;font-weight:600;">
                    <?php if ($done): ?>
                        <i class="bi bi-check-lg"></i>
                    <?php else: ?>
                        <?= $n ?>
                    <?php endif; ?>
                </div>
                <div class="small mt-1" style="color:<?= $active ? '#EE2C28' : '#6c757d' ?>;font-weight:<?= $active ? '600' : '400' ?>;">
                    <?= View::e($s['label']) ?>
                </div>
            </div>
            <?php if ($n < 5): ?>
                <div class="flex-grow-0 mx-1" style="height:2px;background:<?= $done ? '#198754' : '#e9ecef' ?>;width:30px;align-self:flex-start;margin-top:21px;"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
