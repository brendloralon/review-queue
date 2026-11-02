# Decisions

Running log of choices made while building Review Queue. The README summarizes these by category.

### Persistence: SQLite
- Decision: Store data in SQLite via Eloquent migrations and seeders.
- Why: Zero setup for anyone running the project, real SQL and migrations, and a configuration-only swap to Postgres.
- Alternatives considered: In-memory (lost on restart, no realistic querying); JSON file (no indexing, concurrency issues); Postgres (extra setup for no benefit at this size).
- Would change if: Multiple app servers or concurrent writers were needed — then Postgres.
- Category: persistence

### API versioned under /api/v1
- Decision: All endpoints live in one `/api/v1` route group, using Laravel API Resources for responses.
- Why: Lets the response shape evolve behind a new version without breaking the frontend; Resources keep the shape in one place.
- Alternatives considered: Unversioned `/api` routes.
- Would change if: The API were strictly internal and deployed in lockstep with the frontend forever.
- Category: api

### Review as an action endpoint
- Decision: Reviews happen through `POST /api/v1/items/{id}/review`, not a generic `PATCH /items/{id}`.
- Why: A review is a state transition with its own rules (allowed transitions, note, timestamp); a dedicated endpoint makes it explicit and lets invalid transitions return 409.
- Alternatives considered: `PATCH` with a `status` field, which would mix free-form edits with workflow changes.
- Would change if: Items needed general editing alongside reviews.
- Category: api

### Statuses and transitions in one enum
- Decision: `pending`, `approved`, `rejected` as a backed enum (`ItemStatus`) that owns `canTransitionTo()`.
- Why: One place to read and change the review flow; no status strings compared across the codebase.
- Alternatives considered: A status column with checks in controllers; a state-machine package (too heavy for three states).
- Would change if: The flow grew to many states with side effects per transition.
- Category: data-model

### Moderation heuristics computed on submit and stored
- Decision: Rules run once when an item is submitted; risk score, flags and suggested action are stored on the item. They never block submission or change status.
- Why: The queue can filter and sort by stored values cheaply, and the reviewer sees exactly what was computed at submission time. Keeping a human in the loop avoids false positives silently rejecting content.
- Alternatives considered: Computing on every read (always current, but cannot sort or filter in SQL); auto-rejecting high scores.
- Would change if: Rules changed often enough that stale scores matter — then add a rescore command.
- Category: data-model

### Queue filters and sort in the URL
- Decision: Status filter, search, and sort live in the query string, synced by one composable.
- Why: Views are shareable and bookmarkable, and back/forward navigation works without extra state management.
- Alternatives considered: Component state or a global store.
- Would change if: Filters became too complex to express as query parameters.
- Category: frontend

### No Pinia, Axios, or Docker
- Decision: Use composables and the URL for state, native `fetch` for HTTP, and run locally without containers.
- Why: The app has no truly shared client state, `fetch` covers the needs with a small wrapper, and both apps run locally with standard tooling. Fewer dependencies means less to explain and maintain.
- Alternatives considered: Pinia store, Axios client, Docker Compose.
- Would change if: Shared state grew across many views, or the environment needed to be reproducible beyond PHP and Node.
- Category: tradeoff

### One stretch goal, chosen after the core is complete
- Decision: Build the core requirements first; pick at most one stretch goal at the end based on remaining time.
- Why: The core working end-to-end matters most; choosing late avoids designing around a feature that may not fit the timebox. List responses are wrapped in `data` so pagination could be added without breaking clients.
- Alternatives considered: Committing to pagination up front.
- Would change if: N/A — revisited when the core is done.
- Category: tradeoff

### Laravel Boost for AI-assisted development
- Decision: Install `laravel/boost` as a dev dependency; project rules live in `backend/.ai/guidelines/` and are merged into the generated `backend/CLAUDE.md`/`AGENTS.md`.
- Why: Gives AI tools guidelines that match the installed Laravel 13 version and a docs-search MCP server. Dev-only, no runtime impact.
- Alternatives considered: Hand-written backend rules only.
- Would change if: The generated guidance conflicted with project rules more than it helped.
- Category: tooling

### Single items table; review stored on the item
- Decision: One `items` table holds content, the stored moderation result (`risk_score`, `flags`, `suggested_action`) and the review (`status`, `review_note`, `reviewed_at`).
- Why: Reviews are final, so an item has at most one review; a separate table would add a join with no benefit.
- Alternatives considered: A `reviews` table (one-to-many) for history.
- Would change if: Re-review, multiple reviewers, or an audit history were required.
- Category: data-model

### Status stored as a string, restricted by the enum cast
- Decision: `status` and `suggested_action` are string columns cast to PHP backed enums.
- Why: SQLite has no enum type, and adding a status later needs no migration — only an enum case.
- Alternatives considered: A DB enum or check constraint.
- Would change if: Other systems wrote to the database directly and needed DB-level enforcement.
- Category: data-model

### Flags stored as a JSON array
- Decision: Moderation flags are a JSON column (e.g. `["contains_links","shouting"]`).
- Why: They are displayed, not queried; a pivot table would be overhead.
- Alternatives considered: A `flags` table with a pivot; a comma-separated string.
- Would change if: The queue needed to filter by flag (then `whereJsonContains`, or normalize).
- Category: data-model

### Review fields are not mass-assignable
- Decision: `status`, `review_note` and `reviewed_at` are excluded from `$fillable`.
- Why: They may only change through the review action, which enforces allowed transitions; a stray `fill()` cannot bypass the flow.
- Alternatives considered: Guarding in the controller only.
- Would change if: N/A.
- Category: data-model

### Submitter identified by a required name
- Decision: Items carry a required `submitter_name` (max 80 chars).
- Why: There is no auth, but reviewers still need to know who submitted an item.
- Alternatives considered: Anonymous submissions; email address.
- Would change if: Auth were added — then a `user_id` foreign key.
- Category: assumption

### Reviews are final
- Decision: Only `pending → approved | rejected` is allowed; reviewing an already-reviewed item returns 409.
- Why: The simplest flow that matches "approve/reject"; avoids silent overwrites between reviewers.
- Alternatives considered: Allowing a reviewer to change their decision.
- Would change if: Re-review were required — a one-line change in `ItemStatus::allowedTransitions()`.
- Category: assumption

### Removed Sanctum and the backend web scaffolding
- Decision: Removed `laravel/sanctum`, its migration and sample route, plus the Blade welcome page, Tailwind/Vite assets and web routes.
- Why: Auth is out of scope and the backend is API-only; unused scaffolding is dead code to explain and maintain.
- Alternatives considered: Leaving the installer defaults in place.
- Would change if: Auth became a requirement (Sanctum reinstalls with `php artisan install:api`).
- Category: tooling

### Planned API details (decided at plan approval)
- Decision: `status=reviewed` filters approved + rejected together; reviewer note max 1,000 chars; the item drawer is a nested route `/items/:id`; stored moderation results are not recomputed when rules change.
- Why: Matches the brief's "pending vs reviewed" wording; bounds input; makes the drawer linkable and closable with Back; keeps scores as a record of what was computed at submission.
- Alternatives considered: Separate approved/rejected filters only; a modal state outside the URL; recomputing scores on read or via a rescore command.
- Would change if: Rules changed frequently — then add an `items:rescore` command.
- Category: api

### Moderation: three additive rules and two thresholds
- Decision: `BlocklistedTermsRule` (+40 per distinct phrase), `ShoutingRule` (+20 when >60% of ≥20 letters are uppercase), `LinksRule` (+15 for 1–2 URLs, +35 for 3+). Scores are summed and clamped to 0–100; below 30 suggests approve, 30–69 review, 70+ reject.
- Why: Each rule is easy to explain and test in isolation; additive scores make the combined result predictable; thresholds as named constants make tuning a one-line change.
- Alternatives considered: Weighted or multiplicative scoring; ML/third-party moderation APIs (overkill and opaque for this scope).
- Would change if: False positives became a problem (then per-rule weights in config, or word-boundary matching).
- Category: data-model

### Rules are pure functions of the text
- Decision: A rule receives `title + "\n" + body` and returns a score and flags; no database, HTTP or clock access.
- Why: Adding a rule is one class plus one line in `ModerationService::rules()`, and every rule is unit-testable with a string.
- Alternatives considered: Rules receiving the model; auto-discovery of rule classes (implicit, harder to follow).
- Would change if: A rule needed context such as the submitter's history — then pass a small value object, not the model.
- Category: data-model

### Invalid list params fall back to defaults instead of 422
- Decision: `ItemQuery` ignores unknown params and replaces invalid `status`/`sort`/`search` values with defaults.
- Why: The queue URL is user-editable and bookmarkable; an old or mistyped link should still show a sensible list rather than an error page.
- Alternatives considered: A Form Request returning 422 for bad params.
- Would change if: The API were public and clients needed strict feedback on mistakes.
- Category: api

### Named sort values
- Decision: `sort` accepts `newest`, `oldest`, `highest_risk`, `lowest_risk`, mapped to column + direction in one array, with `id` as a tie-breaker.
- Why: Column names never come from the request, and the names read well in the URL. The tie-breaker keeps ordering stable (and pagination-safe later).
- Alternatives considered: `sort=-created_at` style column sorting.
- Would change if: Many sortable columns were needed.
- Category: api

### Review accepts any status; the enum decides
- Decision: The review request validates `status` against all `ItemStatus` values; `canTransitionTo()` rejects forbidden moves with 409 (e.g. `pending → pending`, or reviewing a reviewed item).
- Why: The flow lives only in the enum, so changing it never requires touching validation. 422 means "malformed", 409 means "valid request, wrong state".
- Alternatives considered: Validating `status` as `in:approved,rejected`.
- Would change if: N/A.
- Category: api

### Conditional update to prevent lost reviews
- Decision: `Item::review()` updates `WHERE id = ? AND status = <loaded status>`; zero affected rows means someone else reviewed first, which returns 409.
- Why: Two reviewers opening the same item cannot silently overwrite each other, without locks or a version column.
- Alternatives considered: `lockForUpdate()` in a transaction; an optimistic-locking version column.
- Would change if: Items gained more editable fields that needed general optimistic locking.
- Category: api

### Blank notes become null
- Decision: Rely on Laravel's default `TrimStrings` and `ConvertEmptyStringsToNull` middleware for the review note and submitted fields.
- Why: Whitespace-only input is treated as missing with no custom code; a test proves the behaviour.
- Alternatives considered: Manual trimming in the Form Request.
- Would change if: Leading/trailing whitespace became meaningful.
- Category: assumption

### Simple LIKE search
- Decision: Search uses SQL `LIKE '%term%'` over title, body and submitter name; terms over 100 characters are ignored.
- Why: Enough for a small queue, zero setup. SQLite's LIKE is case-insensitive for ASCII.
- Alternatives considered: SQLite FTS5 or a search service.
- Would change if: The dataset grew large or users needed relevance ranking; `%` and `_` in a term are not escaped.
- Category: tradeoff

### Generic 500s rely on APP_DEBUG=false
- Decision: Unexpected errors use Laravel's default JSON rendering, which hides details when `APP_DEBUG=false`.
- Why: Laravel already guarantees a generic `{"message": "Server Error"}` in production; custom handling would duplicate it.
- Alternatives considered: A custom catch-all renderer.
- Would change if: Errors needed correlation IDs or a custom shape.
- Category: assumption

### Backend test strategy
- Decision: Unit tests for each rule, `ModerationService` (combining, clamping, thresholds) and `ItemStatus` (every transition); feature tests for each endpoint against in-memory SQLite, including 404/409/422 paths, the review race, and a seeder check.
- Why: These are the areas the app's correctness depends on and the ones most likely to change.
- Alternatives considered: Browser/E2E tests (slow, high setup cost for this scope).
- Would change if: The frontend grew complex enough to justify E2E coverage.
- Category: testing

### Frontend types mirror the API in snake_case
- Decision: `src/types/item.ts` uses the exact field names from `ItemResource` (`submitter_name`, `risk_score`, …).
- Why: No mapping layer to write, test or keep in sync; a field means the same thing on both sides.
- Alternatives considered: Converting to camelCase in the API client.
- Would change if: The frontend consumed several APIs with different conventions.
- Category: frontend

### Native `<dialog>` for the item drawer
- Decision: The drawer is a `<dialog>` opened with `showModal()`, styled as a side panel.
- Why: The browser provides modal semantics, makes the page behind inert (a real focus trap) and fires `cancel` on Esc — less custom code than a hand-written focus trap. Focus is returned to the opening row on close.
- Alternatives considered: A `div` with `role="dialog"` and a custom focus trap; a UI library.
- Would change if: Very old browsers had to be supported.
- Category: frontend

### Refetch after a review instead of optimistic updates
- Decision: After any review attempt, the drawer shows the server's response and the queue refetches.
- Why: The server decides transitions (and may answer 409), so the UI never shows a state the server rejected. The extra request is cheap at this size.
- Alternatives considered: Optimistic updates with rollback (listed as a stretch goal).
- Would change if: Latency became noticeable.
- Category: frontend

### Queue refetches only when the query string changes
- Decision: `useItems` watches the serialized query (`status=…&sort=…`), aborts superseded requests with `AbortController`, and search input is debounced by 300 ms and written with `router.replace`.
- Why: Opening or closing the drawer changes the route but not the filters, so it must not refetch; aborting prevents a slow old response from overwriting a newer one; debouncing and replace avoid a request and a history entry per keystroke.
- Alternatives considered: Deep-watching the params object (refetches on every route change).
- Would change if: N/A.
- Category: frontend

### Thresholds and flag rules live only on the server
- Decision: The risk badge colour follows the stored `suggested_action`, and unknown flags are displayed by humanizing their key.
- Why: Tuning thresholds or adding a rule needs no frontend change.
- Alternatives considered: Mirroring the 30/70 thresholds and a full flag list in the frontend.
- Would change if: The frontend needed rule-specific UI.
- Category: frontend

### After submitting, open the new item
- Decision: A successful submission navigates to the new item's drawer over the queue.
- Why: It shows immediately what moderation computed and confirms the item is in the queue.
- Alternatives considered: A success message on the form page.
- Would change if: Submitters and reviewers were separate audiences.
- Category: assumption

### Committed `.env.development`
- Decision: `frontend/.env.development` sets `VITE_API_BASE_URL=http://localhost:8000/api/v1`; overrides go in the git-ignored `.env.development.local`.
- Why: `npm run dev` works from a fresh clone with no copy step; the value is not a secret.
- Alternatives considered: `.env.example` that must be copied.
- Would change if: The URL differed per developer by default.
- Category: tooling

### Frontend test strategy
- Decision: Unit tests for the API client (409/422/500/network/abort handling) and the queue URL parsing; no component or E2E tests.
- Why: These hold the logic most likely to break silently; the views are thin and are covered by a manual end-to-end run-through.
- Alternatives considered: Component tests with mocked API; Playwright E2E.
- Would change if: Views gained more logic or the app grew.
- Category: testing

### AI rules split per folder
- Decision: Shared contracts in the root `CLAUDE.md`; Laravel rules in `backend/`; Vue rules in `frontend/AGENTS.md` imported by `frontend/CLAUDE.md`.
- Why: Each stack's rules load only when working in that folder, nothing is duplicated, and `AGENTS.md` keeps the frontend rules readable by other AI tools.
- Alternatives considered: One large root file.
- Would change if: The project became a single-stack app.
- Category: tooling

### Whole queue row opens the drawer
- Decision: The title link is stretched over its row with CSS (`::after` covering the `<tr>`), so clicking anywhere on the row opens the item; the title is no longer underlined.
- Why: A larger click target without adding a second interactive element; the row stays one real link, so keyboard focus, screen readers, middle-click and "open in new tab" keep working.
- Alternatives considered: A `@click` handler on `<tr>` calling `router.push`, which needs extra `tabindex`/key handling and loses native link behaviour.
- Would change if: Rows gained their own controls (e.g. bulk-select checkboxes), which would need to sit above the stretched link.
- Category: frontend
