# ClubDesk Mobile API v1

REST API consumed by the ClubDesk mobile application (Flutter, iOS/Android).
Distinct from the admin/integration `/api/v1/*` API:

| | Admin API (`/api/v1`) | **Mobile API (`/api/mobile/v1`)** |
|---|---|---|
| Audience | Admins, integrations | **End-member / player** |
| Auth | API key (`ks_…`) per club | **Bearer access token issued per device** |
| Format | JSON | **JSON envelope `{ok, data, error}`** |
| CSRF | n/a | n/a (token-based) |
| Rate limit | per API key | **5 failed logins / hour / IP** |

Base URL: `https://<your-domain>/api/mobile/v1`

All responses are `application/json; charset=utf-8`.

---

## Envelope

Success:

```json
{ "ok": true, "data": { ... } }
```

Error:

```json
{
  "ok": false,
  "error": {
    "code": "validation",
    "message": "Email is required",
    "fields": { "email": "required" }
  }
}
```

HTTP status codes follow REST conventions (`200` ok, `401` unauthorized,
`403` forbidden, `404` not found, `409` conflict, `422` validation,
`429` rate limited, `5xx` server).

---

## Authentication flow

1. `POST /auth/login` with `{email, password}`.
   - If the account is linked to one club → returns `{token, refresh_token, expires_at, member, club}`.
   - If the account is linked to multiple clubs (cross-club identity) →
     returns `{multiple_clubs: true, clubs: [...]}` without a token.
2. (multi-club only) `POST /auth/select-club` with `{email, password, club_id}`
   → returns the same token payload as single-club login.
3. Use `Authorization: Bearer <token>` on every subsequent request.
4. When the access token nears expiry call `POST /auth/refresh`
   with `{refresh_token}` to rotate.
5. `POST /auth/logout` invalidates the current bearer token server-side.

Access TTL: **30 days**. Refresh TTL: **90 days**.
Raw tokens are never stored in DB — only SHA-256 hashes in `member_api_tokens`.

### Example: login (curl)

```bash
curl -X POST https://example.com/api/mobile/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"jan@example.com","password":"secret","device_info":"Pixel 8"}'
```

```json
{
  "ok": true,
  "data": {
    "token": "5e7c…<64 hex chars>",
    "refresh_token": "9af1…",
    "expires_at": "2026-06-14 12:34:00",
    "refresh_expires_at": "2026-08-13 12:34:00",
    "member": { "id": 42, "club_id": 1, "first_name": "Jan", "last_name": "Kowalski", ... },
    "club":   { "id": 1, "name": "AKS Warszawa", "city": "Warszawa", ... }
  }
}
```

### Example: authenticated call

```bash
curl https://example.com/api/mobile/v1/me \
  -H 'Authorization: Bearer 5e7c…'
```

---

## Endpoints

### Auth

| Method | Path | Body | Notes |
|---|---|---|---|
| POST | `/auth/login` | `{email, password, device_info?, app_version?}` | Public. 5/h/IP rate limit on failures |
| POST | `/auth/select-club` | `{email, password, club_id}` | Multi-club resolution |
| POST | `/auth/logout` | — | Revokes the bearer token |
| POST | `/auth/refresh` | `{refresh_token}` | Rotates both tokens |
| POST | `/auth/forgot-password` | `{email}` | Always 200 (no enumeration) |

### Profile

| Method | Path | Body | Notes |
|---|---|---|---|
| GET   | `/me` | — | Current member |
| POST  | `/me` | `{phone?, address_street?, address_city?, address_postal?}` | Update editable fields |
| POST  | `/me/avatar` | multipart `avatar` | JPEG/PNG/WebP, max 5 MB |

### Dashboard

`GET /dashboard` — aggregate:

```json
{
  "today_trainings":      [{ "id":1, "name":"Wtorek 18:00", "start_time":"…", "my_status":"zapisany" }],
  "upcoming_trainings":   [...],
  "fees": { "total_outstanding": 120.00, "total_overdue": 60.00, "overdue_count": 1 },
  "notifications": { "unread": 3, "recent": [...] }
}
```

### Fees

| Method | Path | Notes |
|---|---|---|
| GET   | `/fees?status=pending\|overdue\|paid&page=N` | Paginated list |
| GET   | `/fees/:id` | Detail |
| POST  | `/fees/:id/checkout` | Returns `{payment_id, redirect_url}` — open in WebView |

`redirect_url` re-uses the existing Stripe/Przelewy24 hosted checkout (same code path as `MemberPaymentController`).

### Trainings

| Method | Path | Body | Notes |
|---|---|---|---|
| GET   | `/trainings?from=YYYY-MM-DD&to=YYYY-MM-DD` | — | Defaults: −7d … +30d |
| GET   | `/trainings/:id` | — | Includes attendee list |
| POST  | `/trainings/:id/rsvp` | `{status: confirmed\|tentative\|declined}` | Upserts `training_attendees` |

### Events

| Method | Path | Notes |
|---|---|---|
| GET   | `/events?from=&to=` | Tournaments / club events |
| GET   | `/events/:id` | Detail |
| POST  | `/events/:id/register` | **Stub** (501) — pending event-type-specific schema |

### Results / Rankings

| Method | Path | Notes |
|---|---|---|
| GET   | `/results` | Last 20 tournament participations |
| GET   | `/rankings` | Per-sport ranking positions |

### Documents

| Method | Path | Notes |
|---|---|---|
| GET   | `/documents` | List available types |
| GET   | `/documents/:type` | `{url}` to PDF (rendered by existing portal flow) |

### Notifications

| Method | Path | Notes |
|---|---|---|
| GET   | `/notifications?page=N` | 25/page |
| POST  | `/notifications/:id/read` | Mark single |
| POST  | `/notifications/read-all` | Bulk mark |

### Push tokens

| Method | Path | Body |
|---|---|---|
| POST  | `/push/register`   | `{token, platform: ios\|android\|web, app_version?, device_model?}` |
| POST  | `/push/unregister` | `{token}` |

Stored in `device_tokens` (FCM/APNS), extended in migration 071 with `app_version` and `device_model`.

---

## Error codes

| Code | When |
|---|---|
| `validation` | Missing/invalid body fields |
| `invalid_credentials` | Bad email/password |
| `unauthorized` | Missing/expired/revoked bearer token |
| `invalid_refresh` | Refresh token expired or unknown |
| `no_membership` | Identity has no active member row in selected club |
| `rate_limited` | Too many failed login attempts |
| `not_found` | Resource doesn't exist or isn't yours |
| `already_paid` | Tried to checkout a settled due |
| `gateway_unavailable` | No active payment gateway in this club |
| `not_implemented` | Endpoint exists, feature pending |

---

## Security

- HTTPS only (production).
- Bearer tokens transmitted in `Authorization` header; never in URL.
- Tokens stored as SHA-256 hashes (`member_api_tokens.token_hash` /
  `refresh_token_hash`).
- Token rotation: every refresh creates a new pair and revokes the old.
- Soft revocation via `revoked_at` (logout / refresh / manual).
- No CSRF — token-based auth is immune.
- Rate limit on `/auth/login`: 5 failures / hour / IP → `429 rate_limited`.
- Multi-tenant isolation: `ClubContext::set()` is activated automatically
  inside `MobileApiAuth::authenticate()` so all `ClubScopedModel` reads
  are scoped to the token's `club_id`.

---

## Re-use of existing backend code

- `MemberIdentityModel` — cross-club lookups, password verification.
- `MemberAuth::verifyPassword()` — legacy direct-member login fallback.
- `RateLimiter` — login throttling (same helper as web portal).
- `MemberModel` — profile read/update.
- `PaymentDueModel` — fees listing.
- `OnlinePaymentModel` + `PaymentGateway::createCheckoutSession()` — fees/checkout.
- `MemberNotificationModel` — notification reads/marks.
- `device_tokens` table — push token storage.
