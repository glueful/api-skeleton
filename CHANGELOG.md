# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.

# [1.2.1] - 2025-10-14 — Arcturus

Compatibility release aligning the skeleton with Glueful Framework 1.6.1.

### Changed
- Bump framework dependency to `glueful/framework ^1.6.1`.
  - Gains Auth/JWT: RS256 signing support via `JWTService::signRS256(array $claims, string $privateKey)`
    for generating JWTs using an RSA private key. Requires the `openssl` extension.

```bash
composer update glueful/framework
```

## [1.2.0] - 2025-10-13 — Sirius Alignment

Compatibility release aligning the skeleton with Glueful Framework 1.6.0.

### Changed
- Bump framework dependency to `glueful/framework ^1.6.0`.
  - Gains compiled DI artifacts (`services.json`) and faster container boot in production.
  - `di:container:map` prefers compiled manifest in prod (no reflection).
  - Conditional HTTP caching middleware (`conditional_cache`) + `Response::withLastModified(...)` helper.
  - DSN utilities and `config:dsn:validate` CLI for Database/Redis URLs.

### Notes
- No code changes required in the skeleton. After updating, run:

```bash
composer update glueful/framework
```

## [1.1.0] - 2025-10-13 — Orion Compatibility

Compatibility release aligning the skeleton with Glueful Framework 1.5.0.

### Changed
- Bump framework dependency to `glueful/framework ^1.5.0`.
  - Gains Notifications DI provider (shared ChannelManager + NotificationDispatcher).
  - Safer email verification/password reset flows via DI‑first wiring in the framework.

### Notes
- No code changes required in the skeleton. After updating, run:

```bash
composer update glueful/framework
```


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
