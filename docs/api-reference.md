# ClubDesk — REST API v1 Reference

Base URL: `https://<your-domain>/api/v1`

All requests and responses use JSON (`Content-Type: application/json`).

---

## Authentication

Every request must include a Bearer token in the `Authorization` header:

```
Authorization: Bearer <your_api_key>
```

API keys are generated per club in **Settings > API Keys**. Each key is bound to a specific club context and a set of scopes.

### Scopes

| Scope | Description |
|---|---|
| `members:read` | List and view member profiles |
| `members:write` | Create, update, and archive members |
| `events:read` | List and view events |
| `events:write` | Create, update, and delete events |
| `payments:read` | List payments and fee summaries |
| `payments:write` | Record payments |
| `sports:read` | List club sport sections |

If a request requires a scope the key does not have, the API returns `403 Forbidden`.

---

## Common Headers

| Header | Required | Description |
|---|---|---|
| `Authorization` | Yes | `Bearer <api_key>` |
| `Content-Type` | Yes (for POST/PUT) | `application/json` |
| `Accept` | Recommended | `application/json` |

---

## Pagination

List endpoints support cursor-based pagination:

| Parameter | Type | Default | Description |
|---|---|---|---|
| `page` | int | 1 | Page number |
| `per_page` | int | 25 | Items per page (max 100) |

Response includes pagination metadata:

```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 25,
    "total": 142,
    "last_page": 6
  }
}
```

---

## Endpoints

### Members

#### List members

```
GET /api/v1/members
```

Query parameters: `page`, `per_page`, `status` (active|suspended|archived), `search` (name or email).

```bash
curl -X GET "https://example.com/api/v1/members?status=active&per_page=10" \
  -H "Authorization: Bearer sk_live_abc123"
```

Response `200 OK`:

```json
{
  "data": [
    {
      "id": 1,
      "first_name": "Jan",
      "last_name": "Kowalski",
      "email": "jan@example.com",
      "birth_date": "2005-03-15",
      "status": "active",
      "sport_section": "football",
      "created_at": "2025-09-01T10:00:00Z"
    }
  ],
  "meta": { "current_page": 1, "per_page": 10, "total": 87, "last_page": 9 }
}
```

#### Get single member

```
GET /api/v1/members/{id}
```

```bash
curl -X GET "https://example.com/api/v1/members/1" \
  -H "Authorization: Bearer sk_live_abc123"
```

Response `200 OK`: single member object (same shape as list item).

#### Create member

```
POST /api/v1/members
```

Scope required: `members:write`

```bash
curl -X POST "https://example.com/api/v1/members" \
  -H "Authorization: Bearer sk_live_abc123" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Anna",
    "last_name": "Nowak",
    "email": "anna@example.com",
    "birth_date": "2008-07-22",
    "sport_section_id": 3
  }'
```

Response `201 Created`: the created member object.

#### Update member

```
PUT /api/v1/members/{id}
```

Scope required: `members:write`. Send only the fields to update.

Response `200 OK`: the updated member object.

---

### Events

#### List events

```
GET /api/v1/events
```

Query parameters: `page`, `per_page`, `type` (match|tournament|camp|other), `from` (ISO date), `to` (ISO date).

```bash
curl -X GET "https://example.com/api/v1/events?from=2026-01-01&to=2026-12-31" \
  -H "Authorization: Bearer sk_live_abc123"
```

Response `200 OK`:

```json
{
  "data": [
    {
      "id": 10,
      "title": "Turniej Zimowy",
      "type": "tournament",
      "date_start": "2026-02-15T09:00:00Z",
      "date_end": "2026-02-15T18:00:00Z",
      "location": "Hala Sportowa, Kraków",
      "participants_count": 24
    }
  ],
  "meta": { "current_page": 1, "per_page": 25, "total": 5, "last_page": 1 }
}
```

#### Create event

```
POST /api/v1/events
```

Scope required: `events:write`

```bash
curl -X POST "https://example.com/api/v1/events" \
  -H "Authorization: Bearer sk_live_abc123" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Sparring wiosenny",
    "type": "match",
    "date_start": "2026-04-20T10:00:00Z",
    "date_end": "2026-04-20T12:00:00Z",
    "location": "Boisko Orlik, Warszawa"
  }'
```

Response `201 Created`.

---

### Payments

#### List payments

```
GET /api/v1/payments
```

Query parameters: `page`, `per_page`, `status` (pending|paid|overdue), `member_id`.

```bash
curl -X GET "https://example.com/api/v1/payments?status=overdue" \
  -H "Authorization: Bearer sk_live_abc123"
```

Response `200 OK`:

```json
{
  "data": [
    {
      "id": 55,
      "member_id": 1,
      "member_name": "Jan Kowalski",
      "fee_type": "Składka miesięczna",
      "amount": 150.00,
      "currency": "PLN",
      "status": "overdue",
      "due_date": "2026-03-01",
      "paid_at": null
    }
  ],
  "meta": { "current_page": 1, "per_page": 25, "total": 12, "last_page": 1 }
}
```

#### Record payment

```
POST /api/v1/payments/{id}/pay
```

Scope required: `payments:write`

```bash
curl -X POST "https://example.com/api/v1/payments/55/pay" \
  -H "Authorization: Bearer sk_live_abc123" \
  -H "Content-Type: application/json" \
  -d '{ "paid_at": "2026-04-10", "amount": 150.00 }'
```

Response `200 OK`: updated payment object with `status: "paid"`.

---

### Sports

#### List club sport sections

```
GET /api/v1/sports
```

Scope required: `sports:read`

```bash
curl -X GET "https://example.com/api/v1/sports" \
  -H "Authorization: Bearer sk_live_abc123"
```

Response `200 OK`:

```json
{
  "data": [
    { "id": 1, "sport_key": "football", "name": "Piłka nożna", "active": true },
    { "id": 2, "sport_key": "judo", "name": "Judo", "active": true }
  ]
}
```

---

## Error Codes

All error responses follow a consistent format:

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The birth_date field is required.",
    "details": { "birth_date": ["required"] }
  }
}
```

| HTTP Status | Code | Description |
|---|---|---|
| 400 | `VALIDATION_ERROR` | Request body failed validation |
| 401 | `UNAUTHORIZED` | Missing or invalid API key |
| 403 | `FORBIDDEN` | Key lacks the required scope |
| 404 | `NOT_FOUND` | Resource does not exist |
| 409 | `CONFLICT` | Duplicate resource (e.g., same email) |
| 422 | `UNPROCESSABLE_ENTITY` | Semantic error (e.g., paying an already paid fee) |
| 429 | `RATE_LIMIT_EXCEEDED` | Too many requests |
| 500 | `INTERNAL_ERROR` | Unexpected server error |

---

## Rate Limiting

- Default limit: **100 requests per minute** per API key.
- Rate-limit headers are included in every response:

| Header | Description |
|---|---|
| `X-RateLimit-Limit` | Max requests per window |
| `X-RateLimit-Remaining` | Remaining requests in current window |
| `X-RateLimit-Reset` | Unix timestamp when the window resets |

When the limit is exceeded, the API returns `429 Too Many Requests`. Wait until `X-RateLimit-Reset` before retrying.

Higher limits are available on Pro and Enterprise subscription plans.
