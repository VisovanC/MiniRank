# MiniRank

Keyword position tracker — plain PHP 8 (no framework), PDO + SQLite, vanilla
HTML/CSS/JS. Single user, single website.

## Quick start (5 minutes, Docker)

```
docker compose up -d
docker compose run --rm app php scripts/seed.php
```

Then open http://localhost:8000 — you'll see 8 demo keywords, each with 30 days
of position history, current rank, and a 7-day trend. Click **Refresh positions**
to generate today's ranks via AJAX (no page reload).

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
none exist, then fills in any missing daily positions for every keyword (last 30
days, ranks 1–100). Safe to re-run.

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

## Layout

```
public/       web root — entry files (index.php, keyword.php, refresh.php)
controllers/  request/response handling (thin, may echo)
lib/          pure logic — no superglobals, no echo (unit-testable)
config/       config.php — DB path, site URL
scripts/      CLI scripts (seed)
data/         SQLite file (gitignored)
schema.sql    idempotent schema, applied automatically on first run
```

See `process.html` for the build process log (plan, prompts & fixes,
retrospective).
