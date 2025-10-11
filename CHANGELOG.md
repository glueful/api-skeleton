# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.

## [1.0.1] - 2025-10-11 — Maintenance

Small developer‑experience improvements and alignment with framework 1.4.2 install flow. No breaking changes.

### Changed
- Composer (dev): point `glueful/framework` to a local path repository (`../framework`) for iterative development and set constraint to `*@dev`.
- Install UX (via framework): `php glueful install --quiet` now runs non‑interactive and forces migrations; install is SQLite‑first and skips DB connection probing.

### Notes
- For production or published templates, revert to a tagged framework dependency and remove the path repository:
  - `"glueful/framework": "^1.4.0"` (or newer)
  - Remove the `repositories` path entry


## [1.0.0] - 2025-10-06 — Altair

Altair release — initial public API skeleton built on the Glueful framework, focused on fast startup, clarity, and a clean baseline for new services.

### Added
- Initial public release of Glueful API Skeleton.
- Glueful framework integration (`glueful/framework` ^1.3.0).
- Default routes: `GET /` (welcome payload) and `GET /health` (lightweight health check).
- `App\Controllers\WelcomeController` returning version and timestamp.
- Bootstrap and entrypoint: `bootstrap/app.php`, `public/index.php`.
- Configuration suite under `config/` (app, database, logging, queue, session, events, schedule, security, serviceproviders, extensions, image).
- SQLite default storage at `storage/database/glueful.sqlite` with convenience setup in post-create script.
- Database migrations for core tables (initial schema, queue, schedules, archive, locks, audit logs, notifications).
- Composer scripts for local workflow: `serve`, `migrate`, `key:generate`, `glueful install`, and PHP_CodeSniffer tooling.
- Basic test scaffolding (`tests/TestCase.php`, `tests/Feature/WelcomeTest.php`).
- MIT license.
