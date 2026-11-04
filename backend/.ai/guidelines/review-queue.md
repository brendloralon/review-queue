# Review Queue — Backend

Project rules for the Laravel API. Shared contracts (API shape, error codes, one-home map, git policy) are in the repository root `CLAUDE.md`. Where these rules differ from the generated Laravel guidance in this file, these rules win.

## Language Baseline

- Target PHP 8.3, the `composer.json` minimum, even though the local PHP is newer. No 8.4+ features (property hooks, asymmetric visibility, `new` without parentheses) unless the constraint is raised and logged.
- `declare(strict_types=1);` in every hand-written PHP file.
- Typed parameters, return types, and properties. Promoted `readonly` constructor properties for services and value objects.
- Backed enums with `match` for status and suggested-action logic.

## Structure

- Routes: one `Route::prefix('v1')` group in `routes/api.php`; controllers in `App\Http\Controllers\Api\V1`.
- Controllers stay thin: Form Request validates → model method or service acts → API Resource responds.
- Read request bodies only through `$request->validated()`. List query parameters go through the `ItemQuery` whitelist instead, so invalid values fall back to defaults rather than returning 422.
- Models declare casts in the `casts()` method and use `$fillable`.
- Domain errors (invalid transition) are exceptions rendered as 409 in `bootstrap/app.php` `withExceptions()`, not caught in controllers.
- Configure the app in `bootstrap/app.php` and `config/`; don't add a Kernel or RouteServiceProvider.

## Moderation

- Each rule implements `App\Moderation\ModerationRule` and returns its score contribution and flags.
- Rules are pure functions of the submitted content: no database, HTTP, or clock access.
- `ModerationService` holds the single list of rules, sums and clamps the score to 0–100, merges flags, and derives `suggested_action` from threshold constants defined there.
- Moderation runs once on submission and its result is stored on the item. It never blocks submission or changes status.

## Data

- Schema changes only through migrations; sample data through factories and `DatabaseSeeder`, which includes at least one item triggering each rule.
- Request input never reaches `where`/`orderBy` column names directly; it goes through the `ItemQuery` whitelist.

## Testing and Checks

- PHPUnit (not Pest). Feature tests use `RefreshDatabase` on in-memory SQLite (set in `phpunit.xml`).
- Unit tests: each rule, `ModerationService`, `ItemStatus`. Feature tests: each endpoint, including 404/409/422 paths.
- Before committing: `php artisan test --compact` and `vendor/bin/pint --dirty --format agent`.

## Documentation

- Updating `README.md` at the repository root is part of every relevant change; it does not need a separate request.
