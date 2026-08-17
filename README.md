# MiniRank

Keyword position tracker — plain PHP 8 (no framework), PDO + SQLite, vanilla
HTML/CSS/JS. Single user, single website.

## Run

### Docker (recommended)

```
docker compose up -d
```

Open http://localhost:8000. The schema is created automatically on first
request; the SQLite file lives in `data/` (volume-mounted, gitignored).

### Local PHP

```
php -S localhost:8000 -t public
```

Requires PHP 8.1+ with `pdo_sqlite` (bundled by default).

## Seed demo data

```
docker compose run --rm app php scripts/seed.php
```

Locally: `php scripts/seed.php`. Idempotent — inserts the demo keywords only if
none exist, then fills in any missing daily positions for every keyword (last 30
days, ranks 1–100). Safe to re-run.

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

## Milestones

- [x] M1 Keywords CRUD (add / edit / delete)
- [x] M2 Seeded history (30 days of demo positions)
- [x] M3 Refresh simulation (AJAX, no reload)
- [ ] M4 Keyword list (position, 7-day trend, search)
- [ ] M5 Keyword detail page (position history table)
- [ ] M6 Security basics
- [ ] M7 Runs in 5 minutes (README + one-command start)
- [ ] M8 Responsive

See `process.html` for the build process log (plan, prompts & fixes,
retrospective).