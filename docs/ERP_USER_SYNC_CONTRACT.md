# ERP user synchronization contract (schema version 1)

## Request

- Base URL: `https://crm.ariyajanebi.ir`
- Endpoint: `GET /api/integrations/erp/users`
- Headers: `Authorization: Bearer {EXTERNAL_SYNC_TOKEN}` and `Accept: application/json`

| Parameter | Type | Default | Rules |
| --- | --- | --- | --- |
| `cursor` | integer | `0` | Minimum 0; CRM user ID after which results begin |
| `limit` | integer | `100` | 1–500 |
| `updated_since` | ISO-8601 date/time | none | Also filters on `users.updated_at` |
| `include_inactive` | boolean | `true` | When false, currently blocked users are omitted |

Results are ordered by `users.id`. Continue while `has_more` is true by sending the returned `next_cursor`.

## Response fields

- `id`: immutable CRM `users.id`; ERP must persist it as `crm_user_id` (phone is not the sync key).
- `name`, `phone`, `email`: CRM contact projection; email may be null.
- `manager_id`: direct manager's CRM user ID, or null.
- `roles`: Spatie role names.
- `is_active`: false while `blocked_until` is in the future; otherwise true.
- `can_access_erp`: true only when ERP is enabled, the user is active, allowed roles are configured, and a role matches.
- `is_seller`: true for the centrally configured real CRM seller role (`Marketer`). CRM customers refer to that user through `customers.user_id`.
- `created_at`, `updated_at`: UTC ISO-8601 timestamps.
- `next_cursor`: last returned CRM user ID, or the input cursor for an empty page.
- `has_more`: whether another page exists.
- `meta.schema_version`: response schema version, currently `1`.

Passwords, password hashes, remember tokens, authentication tokens, and secrets are never projected by either user sync endpoint.

## Errors and limits

Missing/invalid credentials return `401` with `error.code=unauthenticated`; absent server configuration returns `500` with `error.code=sync_token_not_configured`; validation errors return `422`. Both current and legacy endpoints use the dedicated `erp.sync` per-minute limiter configured by `ERP_SYNC_RATE_LIMIT`.

`GET /api/external/users` remains deprecated and retains its legacy `message` plus paginated `users` envelope. Remove it only after ERP has switched to the current endpoint and production monitoring confirms a safe transition.
