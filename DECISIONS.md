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

### AI rules split per folder
- Decision: Shared contracts in the root `CLAUDE.md`; Laravel rules in `backend/`; Vue rules in `frontend/AGENTS.md` imported by `frontend/CLAUDE.md`.
- Why: Each stack's rules load only when working in that folder, nothing is duplicated, and `AGENTS.md` keeps the frontend rules readable by other AI tools.
- Alternatives considered: One large root file.
- Would change if: The project became a single-stack app.
- Category: tooling
