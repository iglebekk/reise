# API and MCP reference

## Local setup

With Laravel Valet, the local base URL is `http://reise.test`. Start the app with:

```bash
composer run setup
php artisan passport:keys --no-interaction
composer run dev
```

The setup script prepares the environment, installs dependencies, migrates the database, and builds assets. The dev script starts the configured development services.

## HTTP API

Base path: `/api/v1`. Routes use Passport API bearer authentication. Send JSON with `Accept: application/json` and authenticate with:

```http
Authorization: Bearer <access-token>
```

Obtain a bearer token through Passport's authorization-code flow with PKCE: sign in, authorize the client with scope `mcp:use`, exchange the returned code at `/oauth/token` using the original `code_verifier`, and use `access_token`. Never commit or share tokens.

### Endpoints

| Method | Path | Purpose |
| --- | --- | --- |
| `GET`, `POST` | `/trips` | List accessible trips; create a trip. |
| `GET`, `PUT`, `PATCH`, `DELETE` | `/trips/{trip}` | Read, update supplied fields, or delete a trip. |
| `GET`, `POST` | `/trips/{trip}/participants` | List or add participants. |
| `GET`, `PUT`, `PATCH`, `DELETE` | `/trips/{trip}/participants/{participant}` | Read, update, or remove a participant. |
| `GET`, `POST` | `/trips/{trip}/packing-items` | List or add packing items. |
| `GET`, `PUT`, `PATCH`, `DELETE` | `/trips/{trip}/packing-items/{packing_item}` | Read, update, or remove an item. |
| `POST` | `/trips/{trip}/invitations` | Invite by email with role `admin` or `member`. |
| `DELETE` | `/trips/{trip}/invitations/{invitation}` | Revoke an invitation. |
| `POST` | `/invitations/accept` | Accept an invitation with a verified account and token. |
| `PUT`, `PATCH`, `DELETE` | `/trips/{trip}/members/{user}` | Change a member's role or remove membership. |
| `GET` | `/trips/{trip}/similar` | Find similar trips and anonymous inspiration. |

All trip routes are scoped to trips the authenticated user can access. A trip or nested record outside that scope is not exposed. Role checks apply to writes.

### Fields and values

- **Trip:** `destination`, `start_date`, `end_date`, `type`, `transport`, `accommodation`, optional `activities`, and optional `description`. Dates use `YYYY-MM-DD`; `end_date` must be on or after `start_date`. Duration is returned as `duration_days` and counts both dates.
- **Allowed trip values:** `type`: `family`, `business`, `adventure`; `transport`: `car`, `train`, `plane`; `accommodation`: `hotel`, `cabin`, `camping`; `activities`: `beach`, `camping`, `cycling`, `hiking`, `museums`, `sightseeing`, `skiing`, `swimming`.
- **Participant:** `name`, optional nullable `age` from 0 through 120, and optional nullable `user_id` referring to a trip member. Responses contain `id`, `name`, and `age`.
- **Packing item:** `name`, optional `quantity` (positive integer), optional nullable `comment`, and optional nullable `participant_id`. A null participant is shared. Responses contain `id`, `participant_id`, `name`, `quantity`, and `comment`.
- **Invitation:** `email` and `role` (`admin` or `member`). Accept with a 64-character `token`; acceptance requires a verified account.
- **Membership update:** `role` is `admin` or `member`.

### Example: create a trip

```http
POST /api/v1/trips
Content-Type: application/json
Accept: application/json
Authorization: Bearer <access-token>
```

```json
{
  "destination": "Bergen",
  "start_date": "2026-08-10",
  "end_date": "2026-08-14",
  "type": "family",
  "transport": "train",
  "accommodation": "hotel",
  "activities": ["hiking", "museums"],
  "description": "A summer trip"
}
```

The response is a Laravel resource with a `data` object, including `id`, the supplied trip fields, and derived `duration_days`.

### Responses and errors

Successful creates return `201 Created`, reads and updates return `200 OK`, and deletes return `204 No Content`. Invalid request data returns `422` with Laravel's `message` and field-keyed `errors`. Missing or invalid credentials return `401`; failed authorization returns `403`; inaccessible or missing scoped resources return `404`.

## MCP server

The Streamable HTTP server is at `http://reise.test/mcp/trips` locally. OAuth routes are registered by Laravel MCP and protected by the `mcp` throttle; the server endpoint requires API authentication. Connect using the OAuth authorization-code flow with PKCE, approve access in the browser, and use the resulting access token. The configured MCP scope is `mcp:use`; do not log or share tokens.

The server exposes these nine tools:

| Tool | Purpose |
| --- | --- |
| `list_trips` | List the authenticated user's trips. |
| `show_trip` | Read one accessible trip. |
| `create_trip` | Create a trip and assign the caller as admin. |
| `update_trip` | Update supplied trip fields. |
| `show_packing_lists` | Read shared and participant packing lists. |
| `search_similar_trips` | Find similar trips and anonymous inspiration. |
| `create_packing_item` | Add a packing item. |
| `update_packing_item` | Update a packing item. |
| `delete_packing_item` | Delete a packing item. |

Tool arguments are validated and trip access is checked against the authenticated user. Anonymous inspiration returns approved canonical item names and coarse categories, without source trip or owner identifiers, comments, participants, email addresses, free-text descriptions, or exact destinations.
