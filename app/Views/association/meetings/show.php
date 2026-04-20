<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="<?= url('association/meetings') ?>" class="btn btn-sm btn-outline-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Lista posiedzeń
        </a>
        <h4 class="mb-0"><?= View::e($title) ?></h4>
        <div class="text-muted small">
            <?= View::e($meeting['meeting_date']) ?>
            <?php if (!empty($meeting['location'])): ?> • <?= View::e($meeting['location']) ?><?php endif; ?>
        </div>
    </div>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#voteModal">
        <i class="bi bi-plus-circle"></i> Dodaj uchwałę
    </button>
</div>

<!-- Meeting details -->
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card p-3">
            <h6 class="fw-semibold mb-2"><i class="bi bi-list-check me-1"></i>Porządek obrad</h6>
            <?php if (!empty($meeting['agenda'])): ?>
                <pre class="mb-0" style="font-size:.9rem;white-space:pre-wrap"><?= View::e($meeting['agenda']) ?></pre>
            <?php else: ?>
                <p class="text-muted small mb-0">Brak porządku obrad.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3">
            <div class="d-flex flex-column gap-2">
                <div class="d-flex justify-content-between">
                    <span class="text-muted small">Kworum</span>
                    <?php if ($meeting['quorum_reached']): ?>
                        <span class="badge bg-success">Osiągnięte</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Nieosiągnięte</span>
                    <?php endif; ?>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted small">Uchwały</span>
                    <span class="badge bg-primary"><?= count($votes) ?></span>
                </div>
                <?php if (!empty($meeting['protocol_path'])): ?>
                <div>
                    <a href="<?= View::e($meeting['protocol_path']) ?>" class="btn btn-sm btn-outline-primary w-100" target="_blank">
                        <i class="bi bi-file-pdf me-1"></i>Protokół
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Votes / Resolutions -->
<h5 class="mb-3"><i class="bi bi-file-text me-1"></i>Uchwały</h5>
<?php if (empty($votes)): ?>
    <div class="alert alert-secondary">Brak uchwał dla tego posiedzenia.</div>
<?php else: ?>
    <div class="row g-3">
    <?php foreach ($votes as $v):
        $ri = $voteResults[$v['result']] ?? ['label' => $v['result'], 'class' => 'secondary'];
        $total = (int)$v['vote_yes'] + (int)$v['vote_no'] + (int)$v['vote_abstain'];
    ?>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold small"><?= View::e($v['resolution_number']) ?></span>
                    <span class="badge bg-<?= $ri['class'] ?>"><?= $ri['label'] ?></span>
                </div>
                <div class="card-body">
                    <div class="fw-bold mb-2"><?= View::e($v['title']) ?></div>
                    <?php if (!empty($v['content'])): ?>
                        <p class="small text-muted mb-2"><?= nl2br(View::e($v['content'])) ?></p>
                    <?php endif; ?>
                    <?php if ($total > 0): ?>
                    <div class="d-flex gap-2 mt-2">
                        <span class="badge bg-success"><i class="bi bi-hand-thumbs-up me-1"></i><?= (int)$v['vote_yes'] ?> Za</span>
                        <span class="badge bg-danger"><i class="bi bi-hand-thumbs-down me-1"></i><?= (int)$v['vote_no'] ?> Przeciw</span>
                        <span class="badge bg-secondary"><?= (int)$v['vote_abstain'] ?> Wstrz.</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal: Dodaj uchwałę -->
<div class="modal fade" id="voteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('association/meetings/' . (int)$meeting['id'] . '/vote') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-file-text me-1"></i> Nowa uchwała</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Numer uchwały</label>
                            <input type="text" name="resolution_number" class="form-control"
                                   value="<?= View::e($nextNum) ?>" placeholder="np. U/2024/001">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Tytuł uchwały *</label>
                            <input type="text" name="title" class="form-control" required
                                   placeholder="np. Uchwała w sprawie zatwierdzenia budżetu">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Treść uchwały</label>
                        <textarea name="content" class="form-control" rows="4"></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Głosów za</label>
                            <input type="number" name="vote_yes" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Głosów przeciw</label>
                            <input type="number" name="vote_no" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Wstrzymujących</label>
                            <input type="number" name="vote_abstain" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Wynik *</label>
                            <select name="result" class="form-select" required>
                                <?php foreach ($voteResults as $key => $ri): ?>
                                    <option value="<?= View::e($key) ?>"><?= View::e($ri['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Zapisz uchwałę</button>
                </div>
            </form>
        </div>
    </div>
</div>
