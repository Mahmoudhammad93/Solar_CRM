# REST API

## Enable

1. Settings → **API & system** → enable **REST API**.
2. Administration → **API tokens** → Create token (requires `settings.update`).
3. Copy the plaintext token **once**. Only a prefix is stored afterward.

## Authentication

```http
Authorization: Bearer rf_…
```

Disabled API returns `503`. Missing/invalid token returns `401`.

## Endpoints

### `GET /api/v1/leads`

Lists leads scoped by the token user’s permissions (`leads.view` + record scoping for executives).

Query params:

| Param | Description |
|-------|-------------|
| `per_page` | 1–100 (default 25) |
| `stage` | Filter by stage |
| `source` | Filter by source |

Example:

```bash
curl -s -H "Authorization: Bearer $TOKEN" \
  "http://127.0.0.1:8000/api/v1/leads?per_page=10"
```

## Abilities

Tokens are issued with `leads:read` by default. Responses are JSON:API-style Laravel resources with pagination meta.

## Security notes

- Rate limit: `60/minute` (`throttle:api`).
- Secrets never appear in Settings UI or after creation.
- Revoke tokens from the API tokens page (confirmation required).
- Key rotation: revoke old + create new (no in-place rotate of the same secret).
