<?php use App\Helpers\View; ?>

<div class="container py-4" style="max-width: 960px;">
    <h1 class="mb-3"><i class="bi bi-braces"></i> Public API v2</h1>
    <p class="lead">Wersja REST API ClubDesk dla zewnetrznych integracji (CRM, ksiegowosc, BI, automatyzacja).</p>

    <hr>

    <h2 class="h4 mt-4">Authentication</h2>
    <p>Wszystkie endpointy v2 wymagaja naglowka <code>Authorization: Bearer &lt;token&gt;</code>.</p>
    <p>Token generujesz w <a href="<?= url('club/integrations') ?>">/club/integrations</a> (zakladka <em>Tokeny API v2</em>).
       Plain token jest pokazany <strong>tylko raz</strong> po utworzeniu — baza trzyma jedynie SHA-256 hash.</p>

    <pre class="bg-dark text-light p-3 rounded"><code>curl https://app.clubdesk.io/api/v2/me \
  -H "Authorization: Bearer cdk_v2_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"</code></pre>

    <h2 class="h4 mt-4">Scopes</h2>
    <ul>
        <li><code>members:read</code> — odczyt zawodnikow</li>
        <li><code>trainings:read</code> — odczyt treningow</li>
        <li><code>tournaments:read</code> — odczyt turniejow</li>
        <li><code>payments:read</code> — odczyt platnosci</li>
    </ul>

    <h2 class="h4 mt-4">Rate limits</h2>
    <p><strong>100 requests / minute</strong> per token. Przy przekroczeniu otrzymasz <code>429 rate_limited</code>.</p>

    <h2 class="h4 mt-4">Endpointy</h2>
    <table class="table table-bordered">
        <thead class="table-light">
            <tr><th>Metoda</th><th>Sciezka</th><th>Scope</th><th>Opis</th></tr>
        </thead>
        <tbody>
            <tr><td>GET</td><td><code>/api/v2/me</code></td><td>(any)</td><td>Info o tokenie</td></tr>
            <tr><td>GET</td><td><code>/api/v2/members</code></td><td><code>members:read</code></td><td>Lista zawodnikow. Query: <code>page</code>, <code>per_page</code> (1-100), <code>q</code>, <code>status</code>, <code>sport_section_id</code></td></tr>
            <tr><td>GET</td><td><code>/api/v2/members/:id</code></td><td><code>members:read</code></td><td>Pojedynczy zawodnik (z przypisanymi sportami)</td></tr>
            <tr><td>GET</td><td><code>/api/v2/trainings</code></td><td><code>trainings:read</code></td><td>Lista treningow. Query: <code>from</code> (YYYY-MM-DD), <code>sport_section_id</code></td></tr>
            <tr><td>GET</td><td><code>/api/v2/tournaments</code></td><td><code>tournaments:read</code></td><td>Lista turniejow. Query: <code>sport_key</code></td></tr>
            <tr><td>GET</td><td><code>/api/v2/payments</code></td><td><code>payments:read</code></td><td>Lista platnosci. Query: <code>member_id</code>, <code>year</code></td></tr>
        </tbody>
    </table>

    <h2 class="h4 mt-4">Format odpowiedzi</h2>
    <pre class="bg-dark text-light p-3 rounded"><code>{
  "data": [ ... ],
  "meta": { "page": 1, "per_page": 50, "total": 1234, "last_page": 25 }
}</code></pre>

    <h2 class="h4 mt-4">Error codes</h2>
    <table class="table table-sm">
        <thead class="table-light"><tr><th>HTTP</th><th>code</th><th>kiedy</th></tr></thead>
        <tbody>
            <tr><td>401</td><td><code>missing_token</code></td><td>Brak naglowka Authorization</td></tr>
            <tr><td>401</td><td><code>invalid_token</code></td><td>Token nie istnieje</td></tr>
            <tr><td>401</td><td><code>token_revoked</code></td><td>Token uniewazniony</td></tr>
            <tr><td>401</td><td><code>token_expired</code></td><td>Token przeterminowany</td></tr>
            <tr><td>403</td><td><code>insufficient_scope</code></td><td>Token nie ma wymaganego scope</td></tr>
            <tr><td>429</td><td><code>rate_limited</code></td><td>Przekroczono 100 req/min</td></tr>
        </tbody>
    </table>

    <hr>

    <h2 class="h4 mt-5"><i class="bi bi-broadcast"></i> Webhooki</h2>
    <p>Klub moze subskrybowac eventy systemowe i otrzymywac POST na wybrany URL.
       Konfiguracja w <a href="<?= url('club/integrations') ?>">/club/integrations</a> (zakladka <em>Webhooki</em>).</p>

    <h3 class="h5 mt-3">Dostepne eventy</h3>
    <ul>
        <li><code>member.created</code> — utworzono zawodnika</li>
        <li><code>member.updated</code> — zaktualizowano dane zawodnika</li>
        <li><code>member.deleted</code> — usunieto zawodnika (np. GDPR)</li>
        <li><code>payment.received</code> — webhook platnosci OK</li>
        <li><code>training.completed</code> — zapisano obecnosc na treningu</li>
        <li><code>tournament.finished</code> — oznaczono turniej jako zakonczony</li>
        <li><code>subscription.changed</code> — zmiana subskrypcji klubu</li>
        <li><code>webhook.test</code> — manualny test z UI</li>
    </ul>

    <h3 class="h5 mt-3">Format payloadu</h3>
    <pre class="bg-dark text-light p-3 rounded"><code>{
  "event":    "member.created",
  "club_id":  42,
  "data":     { "member_id": 1234, "first_name": "Anna", "last_name": "Nowak" },
  "sent_at":  "2026-05-16T12:34:56+02:00"
}</code></pre>

    <h3 class="h5 mt-3">Weryfikacja podpisu</h3>
    <p>Naglowek <code>X-ClubDesk-Signature: sha256=&lt;hex&gt;</code> to HMAC-SHA256 z RAW body i Twojego secretu webhooka.</p>

    <p class="mb-1"><strong>PHP:</strong></p>
    <pre class="bg-dark text-light p-3 rounded"><code>$rawBody = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_X_CLUBDESK_SIGNATURE'] ?? '';
$expected = 'sha256=' . hash_hmac('sha256', $rawBody, $WEBHOOK_SECRET);
if (!hash_equals($expected, $sigHeader)) { http_response_code(403); exit; }
$payload = json_decode($rawBody, true);</code></pre>

    <p class="mb-1"><strong>Python:</strong></p>
    <pre class="bg-dark text-light p-3 rounded"><code>import hmac, hashlib
raw = request.get_data()
expected = "sha256=" + hmac.new(SECRET.encode(), raw, hashlib.sha256).hexdigest()
if not hmac.compare_digest(expected, request.headers.get("X-ClubDesk-Signature", "")):
    abort(403)
payload = request.get_json()</code></pre>

    <p class="mb-1"><strong>Node.js:</strong></p>
    <pre class="bg-dark text-light p-3 rounded"><code>const crypto = require('crypto');
const expected = 'sha256=' + crypto.createHmac('sha256', SECRET).update(rawBody).digest('hex');
if (!crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(req.headers['x-clubdesk-signature']))) {
  return res.status(403).end();
}</code></pre>

    <h3 class="h5 mt-3">Retry policy</h3>
    <p>Jezeli Twoj endpoint zwroci kod inny niz 2xx (lub timeout 5s), ClubDesk ponowi z exponential backoff:
       <strong>1m, 5m, 30m, 2h, 12h</strong>. Po 5 nieudanych probach delivery ma status <code>failed</code> i nie jest dalej retry.</p>
    <p>Ostatnie 30 dostaw widoczne jest w UI <a href="<?= url('club/integrations') ?>">/club/integrations</a>.</p>
</div>
