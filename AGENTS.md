# AGENTS.md

## Stack
- Backend: PHP 8.2+, no framework
- DB: SQLite via PDO
- Frontend: vanilla HTML/CSS/JS
- Runtime: Docker Compose (`docker compose up --build`) or `php -S localhost:8000 -t public`

## Structure
- `public/` — HTTP entry points only (`index.php`, `keyword.php`, `refresh.php`, ...)
- `controllers/` — one class per entry point; reads `$_GET`/`$_POST`/session, renders HTML/JSON. No SQL here.
- `lib/` — pure functions only. No superglobals, no `echo`. Takes `PDO` + data as arguments, returns data.
- `scripts/` — one-off CLI scripts (`seed.php`)
- `tests/` — PHPUnit suites, run via `docker compose run --rm test`
- `data/` — SQLite file, gitignored

## Security rules
- All queries: PDO prepared statements. No string-concatenated SQL, ever.
- All output: pass through `e()` before rendering.
- LIKE search: escape `%`, `_`, `\` before building the pattern.
- New tables with a parent FK: add `ON DELETE CASCADE` (enforced — `PRAGMA foreign_keys=ON` is set in `lib/db.php`).
- New protected page → call `require_auth()` at the top of the controller's `handle()`.
- New JSON/API endpoint → check `current_user_id() === null` inline and return 401 JSON; don't call `require_auth()` (it redirects, wrong for JSON).
- Every POST is CSRF-checked globally in `bootstrap.php` — forms need `<?= csrf_field() ?>`; JS fetches need the `csrf-token` meta tag value in the body.

## Data model
- `positions` has `UNIQUE(keyword_id, date)`, refresh is an upsert (`ON CONFLICT ... DO UPDATE`), never a plain insert.
- `keywords.project_id` scopes everything, any keyword query must filter by the active project.

## Testing
- PHPUnit is a dev-only Composer dependency (`phpunit/phpunit ^12`, `composer.lock` committed); `vendor/` is gitignored and never shipped in the image.
- Add a test as `tests/<Name>Test.php` extending `PHPUnit\Framework\TestCase`; use `memory_pdo()` (defined in `tests/bootstrap.php`) for an in-memory SQLite.
- Run with `docker compose run --rm test` (compose `test` service) or `composer test` with a local PHP + composer.
- The Dockerfile's composer stage runs PHPUnit during `docker compose up -d --build`, so a broken build never ships.

## Process notes
- One opencode session per feature/milestone.
- Plan mode before Build mode for anything touching schema or architecture.
- Small, scoped prompts — one milestone or one bug at a time.