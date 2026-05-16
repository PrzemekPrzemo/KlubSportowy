<?php
/**
 * Stopka layoutu manuala — domyka <article> i <div.manual-wrapper> + container,
 * oraz renderuje pager (poprzednia / następna strona).
 *
 * Wejście (opcjonalne):
 *   $prev = ['slug' => '...', 'title' => '...'];
 *   $next = ['slug' => '...', 'title' => '...'];
 */
use App\Helpers\View;

$prev      = $prev ?? null;
$next      = $next ?? null;
$manualNav = $manualNav ?? ['base' => 'help', 'items' => []];
?>
            <div class="manual-pager">
                <?php if ($prev): ?>
                    <a href="<?= url($manualNav['base'] . '/' . $prev['slug']) ?>"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i>
                        <span class="d-none d-md-inline"><?= View::e($prev['title']) ?></span>
                        <span class="d-md-none">Poprzednia</span>
                    </a>
                <?php else: ?><span></span><?php endif; ?>

                <?php if ($next): ?>
                    <a href="<?= url($manualNav['base'] . '/' . $next['slug']) ?>"
                       class="btn btn-primary btn-sm">
                        <span class="d-none d-md-inline"><?= View::e($next['title']) ?></span>
                        <span class="d-md-none">Następna</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                <?php else: ?><span></span><?php endif; ?>
            </div>
        </article>
    </div>
</div>
