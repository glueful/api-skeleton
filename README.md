# Glueful API Skeleton

A minimal API application starter powered by the Glueful framework.

## Quick Start

- Install dependencies: `composer install`
- Copy env (if not created): `.env` is auto-copied from `.env.example` by post-create scripts
- Initialize (quiet): `php glueful install --quiet`
- Start server: `php glueful serve`
- Visit: `http://127.0.0.1:8080/` and `http://127.0.0.1:8080/health`

CLI alternatives
- `vendor/bin/glueful` (from the framework package)
- Make `glueful` executable to run `./glueful` (run `chmod +x glueful`)

## Default Routes

- `GET /` — Welcome JSON payload
- `GET /health` — Lightweight health check (framework also mounts rich `/health/*`)

## Project Structure

- `bootstrap/app.php` — Boots the framework (loads env, builds container)
- `public/index.php` — Minimal HTTP entrypoint
- `routes/api.php` — Application routes (uses `Glueful\Routing\Router`)
- `app/` — Application code (Controllers/Services/Models)
- `config/` — App configuration loaded by the framework
- `storage/` — Cache, logs, database (SQLite default)

## Controllers & Routing

- Routes are defined in `routes/api.php` and point to controllers (e.g., `App\Controllers\WelcomeController`).
- The framework auto-scans `app/Controllers` for attribute-based routes. This skeleton uses explicit routes in `routes/api.php`. You can switch to `app/Controllers` if you prefer attribute discovery.

## Testing

- Base test case at `tests/TestCase.php`
- Run tests: `composer test`

## Notes

- Default DB is SQLite at `storage/database/glueful.sqlite`
- Default queue: `sync` for zero-migration startup (change in `.env` if needed)
