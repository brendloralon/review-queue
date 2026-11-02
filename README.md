# Review Queue

## 1. How to run the project

**Prerequisites:** PHP 8.3+ with the `pdo_sqlite` extension, Composer 2, Node.js 22.18+ or 24.12+.

### Backend (Laravel API)

Commands work in PowerShell, cmd and bash:

```sh
cd backend
composer install
php -r "copy('.env.example', '.env');"
php artisan key:generate
php artisan migrate --seed --no-interaction   # creates database/database.sqlite and sample items
php artisan serve                             # http://localhost:8000
```

Run the tests with `php artisan test`.

### Frontend (Vue)

_To be completed._

## 2. Key decisions

### Data model choices

- **One `items` table** holds the content, the stored moderation result (`risk_score`, `flags`, `suggested_action`) and the review (`status`, `review_note`, `reviewed_at`). I made reviews final, so an item has at most one review and a separate `reviews` table would only add a join.
- **Status is a string column cast to a PHP enum** (`ItemStatus`). SQLite has no enum type, and adding a status needs only a new enum case, not a migration. The enum also owns the review flow (`pending → approved | rejected`), so changing the flow is a one-line edit.
- **Review fields are not mass-assignable.** They can change only through `Item::review()`, which enforces the transitions.
- **Moderation runs once on submission and the result is stored.** That keeps the queue sortable by risk in SQL and shows reviewers exactly what was computed when the item arrived. Heuristics only inform; they never block or decide.
- **Moderation rules** (one class each, registered in `ModerationService::rules()`):

  | Rule | Triggers when | Score | Flag |
  |---|---|---|---|
  | Blocklisted terms | a spam phrase such as "free money" or "click here" appears | +40 per distinct phrase | `blocklisted_terms` |
  | Shouting | more than 60% of at least 20 letters are uppercase | +20 | `shouting` |
  | Links | 1–2 URLs / 3 or more URLs | +15 / +35 | `contains_links` / `many_links` |

  Scores are summed and clamped to 0–100. Below 30 suggests **approve**, 30–69 **review**, 70+ **reject**. Rules are pure functions of the text, so each is unit-tested with a string.

### API design choices

All endpoints are under `/api/v1` and return Laravel API Resources, so the response shape is defined in one place and can evolve behind a new version.

| Endpoint | Purpose |
|---|---|
| `GET /items?status=&search=&sort=` | List. `status`: `pending`, `approved`, `rejected` or `reviewed` (approved + rejected). `sort`: `newest` (default), `oldest`, `highest_risk`, `lowest_risk`. |
| `GET /items/{id}` | One item |
| `POST /items` | Submit `{title, body, submitter_name}`; returns 201 with the moderation result |
| `POST /items/{id}/review` | Review `{status, note?}` |

- **Reviewing is an action endpoint**, not `PATCH /items/{id}`, because it is a state transition with its own rules rather than a field edit.
- **Errors:** 422 for invalid input (Laravel's default shape), 404 for unknown items, 409 with a clear message when the transition is not allowed (e.g. the item is already reviewed), and a generic 500 when `APP_DEBUG=false`.
- **The review request accepts any known status** and the enum decides whether the move is allowed. The flow lives in one place, and "malformed" (422) stays distinct from "wrong state" (409).
- **Concurrent reviews:** the update only applies if the status is still the one that was loaded, so a second reviewer gets a 409 instead of silently overwriting the first review.
- **List parameters are a whitelist** (`ItemQuery`). Unknown or invalid values fall back to defaults rather than returning 422, so an old or hand-edited queue URL still works. Sorting always adds `id` as a tie-breaker for a stable order.

### Persistence choice

I chose SQLite: it needs no setup to run the project, still gives real SQL with migrations and seeders, and moving to Postgres for production is a configuration change rather than a rewrite. For production I would switch to Postgres (concurrent writers, proper full-text search), run migrations in the deploy pipeline, and keep `APP_DEBUG=false`.

## 3. Assumptions I made

- There is no authentication, so anyone can submit and review; submitters identify themselves with a required name.
- Reviews are final: once approved or rejected, an item cannot be reviewed again.
- Whitespace-only input counts as empty: a blank note is stored as `null`, and blank required fields fail validation.
- Stored moderation results are a record of submission time; they are not recalculated when rules change.

## 4. Tradeoffs

### What I optimized for

- Making the likely changes local: a new heuristic is one class plus one line, the review flow is one enum method, and a new filter or sort is one whitelist entry.
- Clear, explicit code over abstractions, and a small number of meaningful tests.

### What I intentionally didn't build

- Authentication and per-reviewer audit history (out of scope; `reviewed_by` would follow auth).
- Full-text search: search is a simple `LIKE` over title, body and submitter, which is fine for a small queue (wildcard characters in the search term are not escaped).
- Recalculating scores when rules change (a rescore command would be the next step).
- Pagination: the list returns all matching items for now, wrapped in `data` so pagination metadata can be added without breaking clients.

## 5. Testing

### What I tested and why

- **Moderation (unit):** each rule's triggers and boundaries, plus how the service combines, clamps and maps scores to suggested actions. These are the rules most likely to be tuned.
- **Review flow (unit):** every status transition pair in `ItemStatus`.
- **API (feature, in-memory SQLite):** submitting (moderation stored, validation, ignored review fields), showing (404), listing (each filter, search, sort, tie-breaker, invalid and unknown params) and reviewing (approve, reject with note, blank note, 409 on double review, 409 on invalid transition, 422, 404, and a simulated race between two reviewers).
- **Seeder:** sample data triggers every rule, suggested action and status, so the demo stays meaningful as rules change.

### What I didn't test and why

- The generic 500 response: it is Laravel's built-in behaviour when `APP_DEBUG=false`.
