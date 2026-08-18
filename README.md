# MiniRank

Keyword position tracker — plain PHP 8 (no framework), PDO + SQLite, vanilla
HTML/CSS/JS. Single user, multiple websites (projects), each with its own
keywords and position history.

## Quick start (5 minutes, Docker)

```
docker compose up -d
docker compose run --rm app php scripts/seed.php
```

Then open http://localhost:8000 — sign up (anyone can create an account) or sign
in, and you'll see the **Default** project with 8 demo keywords, each with 30 days
of position history, current rank, and a 7-day trend. Click **Refresh positions**
to generate today's ranks via AJAX (no page reload). Use **Switch project** to
move between websites and **+ New project** to track another site (name + URL).

That's it. The SQLite file lives in `data/` (volume-mounted, gitignored), and
the schema is created automatically on first request.

## Run without Docker

```
php -S localhost:8000 -t public
```

Requires PHP 8.1+ with `pdo_sqlite` (bundled by default). Seed with
`php scripts/seed.php`. (Reminder: after any code change, rebuild the Docker
image with `docker compose up -d --build`.)

## Seed demo data

```
docker compose run --rm app php scripts/seed.php
```

Locally: `php scripts/seed.php`. Idempotent — inserts the demo keywords only if
the Default project has none, then fills in any missing daily positions for every
keyword in that project (last 30 days, ranks 1–100). Safe to re-run.

## Run the tests

```
docker compose run --rm test
```

PHPUnit tests for the core `lib/` logic — seeding, trend calculation, chart SVG,
CSV encoding, escaping, CSRF tokens, users + lockout, DB CRUD and the
legacy-schema migration — against an in-memory SQLite, so no real data is
touched. The suite also runs at image build time (composer stage), so
`docker compose up -d --build` fails if any test breaks.

## What it does

- **M1** Keywords CRUD (add / edit / delete)
- **M2** Seed script — 8 demo keywords, ~30 days of daily positions (1–100)
- **M3** "Refresh positions" button — server generates today's ranks, page
  updates via AJAX (fetch), no reload
- **M4** Keyword list — current position, 7-day trend (▲ improved / ▼ declined /
  ─ stable), text search
- **M5** Keyword detail page — full position history table
- **M6** Security — parameterized queries, escaped output, no secrets committed,
  nosniff + frame-options headers
- **M7** This README — one-command start + seed
- **M8** Responsive layout (usable at phone width)
- **S1** Line chart (hand-rolled SVG) of position history on the keyword detail page
- **S2** Multiple projects/websites — each with its own keywords, history, search
  and refresh; per-project site URL; automatic migration for existing databases
- **S3** Accounts + CSRF — open sign-up, sign in/out, session-based auth; every
  POST (including the AJAX refresh) requires a per-session CSRF token; passwords
  hashed with `password_hash()`
- **S4** Filter the keyword list by movement (improved / declined / stable) and
  by rank range (min–max), combined with each other and with search
- **S5** CSV export — download the current (search- and filter-aware) keyword list
  or a keyword's position history; UTF-8 BOM + proper quoting for Excel
- **S6** PHPUnit tests for the core logic — seeding and trend calculation, plus
  chart SVG, CSV encoding, escaping, CSRF tokens, user auth + lockout,
  project/keyword/position CRUD on in-memory SQLite, and the legacy-schema
  migration. Composer-managed; `docker compose run --rm test` (60 tests,
  2087 assertions)
- **S7** Docker polish — PHPUnit runs at image build (a broken build won't ship),
  container healthcheck, `restart: unless-stopped`
- **S8** Final QA — fresh-clone end-to-end proof (clone → build → seed →
  sign-up → full flow) and a full lint pass

## Layout

```
public/       web root — entry files (index.php, keyword.php, refresh.php)
controllers/  request/response handling (thin, may echo)
lib/          pure logic — no superglobals, no echo (PHPUnit-tested)
tests/        PHPUnit suites (composer dev dependency)
config/       config.php — DB path, site URL
scripts/      CLI scripts (seed)
data/         SQLite file (gitignored, volume-mounted)
schema.sql    idempotent schema, applied automatically on first run
composer.json / phpunit.xml   dev-only tooling (runtime is dependency-free)
```

See `process.html` for the build process log (plan, prompts & fixes,
retrospective).
