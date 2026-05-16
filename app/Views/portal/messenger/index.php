<?php
use App\Helpers\Csrf;
use App\Helpers\View;

/** @var array $threads */
/** @var ?int $activeThreadId */
/** @var ?array $activeThread */
/** @var array $activeParticipants */
/** @var array $activeMessages */
/** @var ?array $activeOther */
/** @var array $candidates */
/** @var int $currentMemberId */

$csrf = Csrf::token();

$threadTitle = function(array $t, ?array $other = null): string {
    if (!empty($t['title'])) {
        return (string)$t['title'];
    }
    if (($t['thread_type'] ?? '') === 'direct' && $other) {
        return trim(($other['first_name'] ?? '') . ' ' . ($other['last_name'] ?? ''));
    }
    $labels = [
        'group'   => 'Grupa',
        'section' => 'Sekcja sportowa',
        'event'   => 'Wydarzenie',
        'direct'  => 'Rozmowa prywatna',
    ];
    return $labels[$t['thread_type'] ?? 'direct'] ?? 'Watek';
};
?>

<meta name="csrf-token" content="<?= View::e($csrf) ?>">

<style>
    .msgr-wrap { display:flex; height: calc(100vh - 220px); min-height: 480px; background:#fff; border:1px solid #e5e7eb; border-radius:.5rem; overflow:hidden; }
    .msgr-sidebar { width: 300px; min-width: 260px; border-right:1px solid #e5e7eb; display:flex; flex-direction:column; background:#fafafa; }
    .msgr-sidebar-head { padding:.6rem .75rem; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; background:#fff; }
    .msgr-thread-list { flex:1; overflow-y:auto; }
    .msgr-thread-item { display:block; padding:.65rem .75rem; border-bottom:1px solid #f1f1f1; color:inherit; text-decoration:none; }
    .msgr-thread-item:hover, .msgr-thread-item.active { background:#eef2ff; color:#1f2937; }
    .msgr-thread-title { font-weight:600; font-size:.92rem; display:flex; justify-content:space-between; gap:.4rem; }
    .msgr-thread-preview { font-size:.8rem; color:#6b7280; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .msgr-badge-unread { background:#EE2C28; color:#fff; border-radius:10px; font-size:.7rem; padding:0 .4rem; min-width:18px; text-align:center; }
    .msgr-main { flex:1; display:flex; flex-direction:column; min-width:0; }
    .msgr-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; flex:1; color:#9ca3af; padding:2rem; text-align:center; }
    .msgr-head { padding:.6rem .9rem; border-bottom:1px solid #e5e7eb; display:flex; gap:.6rem; align-items:center; background:#fff; }
    .msgr-head .name { font-weight:600; }
    .msgr-head .status { font-size:.75rem; color:#9ca3af; }
    .msgr-msglist { flex:1; overflow-y:auto; padding:1rem; background:#f5f7fb; }
    .msgr-msg { max-width:75%; margin-bottom:.5rem; padding:.5rem .75rem; border-radius:.6rem; word-wrap:break-word; white-space:pre-wrap; box-shadow:0 1px 0 rgba(0,0,0,.04); }
    .msgr-msg.mine { background:#dbeafe; margin-left:auto; }
    .msgr-msg.theirs { background:#fff; border:1px solid #e5e7eb; }
    .msgr-meta { font-size:.7rem; color:#9ca3af; margin-bottom:.15rem; }
    .msgr-footer { border-top:1px solid #e5e7eb; padding:.5rem; background:#fff; display:flex; gap:.5rem; }
    .msgr-footer textarea { resize:none; flex:1; border:1px solid #d1d5db; border-radius:.4rem; padding:.4rem .55rem; font-size:.95rem; }
    .msgr-footer button { white-space:nowrap; }
    @media (max-width: 768px) {
        .msgr-wrap { flex-direction:column; height: calc(100vh - 180px); }
        .msgr-sidebar { width: 100%; border-right:0; border-bottom:1px solid #e5e7eb; max-height: 45vh; }
        .msgr-main { min-height: 50vh; }
    }
</style>

<div class="msgr-wrap" id="msgrApp"
     data-current-member-id="<?= (int)$currentMemberId ?>"
     data-active-thread-id="<?= (int)($activeThreadId ?? 0) ?>"
     data-base-url="<?= View::e(url('portal/messenger')) ?>">
    <aside class="msgr-sidebar">
        <div class="msgr-sidebar-head">
            <strong><i class="bi bi-chat-dots me-1"></i>Rozmowy</strong>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newDirectModal" title="Nowa rozmowa">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>
        <div class="msgr-thread-list">
            <?php if (empty($threads)): ?>
                <div class="p-3 text-muted small">Brak rozmow. Kliknij + aby rozpoczac.</div>
            <?php endif; ?>
            <?php foreach ($threads as $t):
                $isActive = ($activeThreadId !== null && (int)$t['id'] === (int)$activeThreadId);
                // Direct: probujemy znalezc "inna" nazwe z chat preview niemozliwe bez participantow,
                // wiec uzywamy title lub generic; w przyszlosci mozna dociagnac other participant.
                $titleStr = $threadTitle($t);
                $unread = (int)($t['unread_count'] ?? 0);
            ?>
                <a class="msgr-thread-item <?= $isActive ? 'active' : '' ?>"
                   href="<?= View::e(url('portal/messenger/' . (int)$t['id'])) ?>">
                    <div class="msgr-thread-title">
                        <span><?= View::e($titleStr) ?></span>
                        <?php if ($unread > 0): ?><span class="msgr-badge-unread"><?= $unread ?></span><?php endif; ?>
                    </div>
                    <div class="msgr-thread-preview">
                        <?= $t['last_body'] !== null ? View::e(mb_substr((string)$t['last_body'], 0, 80)) : '<em>brak wiadomosci</em>' ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <main class="msgr-main">
        <?php if ($activeThread === null): ?>
            <div class="msgr-empty">
                <i class="bi bi-chat-square-text" style="font-size:3rem;"></i>
                <h5 class="mt-3">Wybierz rozmowe</h5>
                <p class="small">Kliknij watek z listy po lewej lub zaloz nowy.</p>
            </div>
        <?php else: ?>
            <?php
                $headerName = $threadTitle($activeThread, $activeOther);
                $headerSub  = '';
                if (($activeThread['thread_type'] ?? '') === 'direct') {
                    $headerSub = 'Rozmowa prywatna';
                } else {
                    $headerSub = count($activeParticipants) . ' uczestnikow';
                }
            ?>
            <div class="msgr-head">
                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" style="width:38px;height:38px;font-size:1rem;">
                    <i class="bi bi-person"></i>
                </div>
                <div>
                    <div class="name"><?= View::e($headerName) ?></div>
                    <div class="status"><?= View::e($headerSub) ?> &middot; <span id="msgrConnStatus">laczenie...</span></div>
                </div>
            </div>
            <div class="msgr-msglist" id="msgrList">
                <?php foreach ($activeMessages as $m):
                    $isMine = ((int)$m['sender_member_id'] === (int)$currentMemberId);
                    $cls = $isMine ? 'mine' : 'theirs';
                ?>
                    <div class="msgr-msg <?= $cls ?>" data-mid="<?= (int)$m['id'] ?>">
                        <?php if (!$isMine): ?>
                            <div class="msgr-meta"><?= View::e(trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? ''))) ?> &middot; <?= View::e(date('H:i', strtotime((string)$m['created_at']))) ?></div>
                        <?php else: ?>
                            <div class="msgr-meta text-end"><?= View::e(date('H:i', strtotime((string)$m['created_at']))) ?></div>
                        <?php endif; ?>
                        <?= nl2br(View::e((string)$m['body'])) ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <form class="msgr-footer" id="msgrSendForm" autocomplete="off">
                <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                <input type="hidden" name="thread_id" value="<?= (int)$activeThread['id'] ?>">
                <textarea name="body" rows="2" placeholder="Napisz wiadomosc... (Enter aby wyslac, Shift+Enter = nowa linia)" maxlength="4000" required></textarea>
                <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i></button>
            </form>
        <?php endif; ?>
    </main>
</div>

<!-- Modal: nowa rozmowa 1-1 -->
<div class="modal fade" id="newDirectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="<?= View::e(url('portal/messenger/new-direct')) ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus"></i> Nowa rozmowa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body">
                <?php if (empty($candidates)): ?>
                    <p class="text-muted mb-0">Brak innych aktywnych czlonkow w klubie.</p>
                <?php else: ?>
                    <label for="targetMember" class="form-label">Wybierz czlonka klubu:</label>
                    <select name="target_member_id" id="targetMember" class="form-select" required>
                        <option value="">— wybierz —</option>
                        <?php foreach ($candidates as $c): ?>
                            <option value="<?= (int)$c['id'] ?>">
                                <?= View::e(trim(($c['last_name'] ?? '') . ' ' . ($c['first_name'] ?? ''))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                <button type="submit" class="btn btn-primary" <?= empty($candidates) ? 'disabled' : '' ?>>
                    <i class="bi bi-chat"></i> Rozpocznij
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    var app = document.getElementById('msgrApp');
    if (!app) return;
    var threadId = parseInt(app.getAttribute('data-active-thread-id') || '0', 10);
    if (!threadId) return;

    var currentMemberId = parseInt(app.getAttribute('data-current-member-id') || '0', 10);
    var baseUrl = app.getAttribute('data-base-url') || '/portal/messenger';
    var list = document.getElementById('msgrList');
    var form = document.getElementById('msgrSendForm');
    var status = document.getElementById('msgrConnStatus');
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function scrollBottom() {
        if (!list) return;
        list.scrollTop = list.scrollHeight;
    }
    scrollBottom();

    function lastSeenId() {
        var nodes = list.querySelectorAll('.msgr-msg[data-mid]');
        var maxId = 0;
        nodes.forEach(function(n){
            var v = parseInt(n.getAttribute('data-mid'), 10);
            if (v > maxId) maxId = v;
        });
        return maxId;
    }

    function escapeHtml(s) {
        return s.replace(/[&<>"']/g, function(c){
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
        });
    }

    function renderMessage(m) {
        var existing = list.querySelector('.msgr-msg[data-mid="' + (m.id|0) + '"]');
        if (existing) return; // dedup
        var isMine = (parseInt(m.sender_member_id, 10) === currentMemberId);
        var div = document.createElement('div');
        div.className = 'msgr-msg ' + (isMine ? 'mine' : 'theirs');
        div.setAttribute('data-mid', String(m.id|0));
        var meta = document.createElement('div');
        meta.className = 'msgr-meta' + (isMine ? ' text-end' : '');
        var time = '';
        try {
            var d = new Date(String(m.created_at).replace(' ', 'T'));
            time = isNaN(d.getTime()) ? '' : ('0'+d.getHours()).slice(-2) + ':' + ('0'+d.getMinutes()).slice(-2);
        } catch (e) {}
        meta.textContent = isMine ? time : ((m.sender_name || '') + ' · ' + time);
        var body = document.createElement('div');
        body.innerHTML = escapeHtml(String(m.body || '')).replace(/\n/g, '<br>');
        div.appendChild(meta);
        div.appendChild(body);
        list.appendChild(div);
        scrollBottom();
    }

    function markRead() {
        var fd = new FormData();
        fd.append('_csrf', csrf);
        fetch(baseUrl + '/' + threadId + '/mark-read', { method:'POST', body: fd, credentials:'same-origin' })
            .catch(function(){});
    }

    // ── SSE primary ──
    var es = null;
    var pollTimer = null;
    function startSSE() {
        if (typeof EventSource === 'undefined') {
            return startPoll();
        }
        try {
            es = new EventSource(baseUrl + '/' + threadId + '/stream?since=' + lastSeenId());
            es.addEventListener('open', function(){
                status.textContent = 'online (SSE)';
            });
            es.addEventListener('message', function(ev){
                try {
                    var m = JSON.parse(ev.data);
                    renderMessage(m);
                    markRead();
                } catch (e) {}
            });
            es.addEventListener('error', function(){
                status.textContent = 'reconnect...';
                if (es) { es.close(); es = null; }
                // EventSource auto-reconnect; if it failed entirely fall back to polling.
                setTimeout(function(){
                    if (!es) startPoll();
                }, 3000);
            });
        } catch (e) {
            startPoll();
        }
    }
    function startPoll() {
        if (pollTimer) return;
        status.textContent = 'online (poll)';
        pollTimer = setInterval(function(){
            fetch(baseUrl + '/' + threadId + '/poll?since=' + lastSeenId(), { credentials:'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(j){
                    if (j && j.ok && Array.isArray(j.messages)) {
                        j.messages.forEach(renderMessage);
                        if (j.messages.length) markRead();
                    }
                }).catch(function(){});
        }, 4000);
    }

    startSSE();
    // Mark current state as read.
    markRead();

    // ── Send via AJAX (optimistic UI) ──
    if (form) {
        var textarea = form.querySelector('textarea[name="body"]');
        form.addEventListener('submit', function(ev){
            ev.preventDefault();
            var body = (textarea.value || '').trim();
            if (!body) return;
            var fd = new FormData(form);
            textarea.value = '';
            textarea.focus();
            // Optimistic placeholder (negative id, replaced on response).
            var tmpId = -Date.now();
            renderMessage({
                id: tmpId,
                sender_member_id: currentMemberId,
                sender_name: 'Ty',
                body: body,
                created_at: new Date().toISOString().replace('T',' ').substring(0,19)
            });
            fetch(baseUrl + '/send', { method:'POST', body: fd, credentials:'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(j){
                    if (j && j.ok && j.message) {
                        var ph = list.querySelector('.msgr-msg[data-mid="' + tmpId + '"]');
                        if (ph) ph.setAttribute('data-mid', String(j.message.id|0));
                    } else {
                        status.textContent = 'blad wysylki';
                    }
                }).catch(function(){
                    status.textContent = 'offline';
                });
        });
        // Enter wysyla, Shift+Enter nowa linia.
        textarea.addEventListener('keydown', function(ev){
            if (ev.key === 'Enter' && !ev.shiftKey) {
                ev.preventDefault();
                form.requestSubmit();
            }
        });
    }
})();
</script>
