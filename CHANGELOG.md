# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.

## [1.5.0] - 2026-01-17 — Betelgeuse Alignment

Compatibility release aligning the skeleton with Glueful Framework 1.9.0.

### Breaking Changes
- **PHP 8.3 Required**: Minimum PHP version raised from 8.2 to 8.3, matching the framework requirement.

### Changed
- Bump PHP requirement to `^8.3`.
- Bump framework dependency to `glueful/framework ^1.9.0`.
  - Framework now requires PHP 8.3 minimum.
  - Console `Application::addCommand(string)` renamed to `Application::registerCommandClass(string)` for Symfony Console 7.3 compatibility.
  - Route double-loading prevention eliminates CLI warnings.
  - PHPStan and PSR-4 test fixes.

### Notes
- Before updating, ensure your environment runs PHP 8.3 or higher.
- After updating, run:

```bash
composer update
```

- If you extended the console Application and called `addCommand(MyCommand::class)`, update to `registerCommandClass(MyCommand::class)`.

## [1.4.1] - 2025-11-23 — Vega Alignment

Compatibility release aligning the skeleton with Glueful Framework 1.8.1.

### Changed
- Bump framework dependency to `glueful/framework ^1.8.1`.
  - Adds the `$requireLowercase` toggle to `Utils::validatePassword()` so apps can enforce mixed-case password policies declaratively.
  - Improves `async_stream()` so buffered helpers can accept existing async streams or raw resources without static-analysis noise.

### Notes
- After updating, run:

```bash
composer update glueful/framework
```

No other changes are required; opt into the lowercase flag where appropriate in your auth flows.

## [1.4.0] - 2025-11-13 — Spica

Compatibility release aligning the skeleton with Glueful Framework 1.8.0.

### Changed
- Bump framework dependency to `glueful/framework ^1.8.0`.
  - Picks up first-class session and login response events:
    - `SessionCachedEvent` to enrich cached session payloads post-write
    - `LoginResponseBuildingEvent`/`LoginResponseBuiltEvent` to shape pre-return login responses
  - No behavior change unless you register listeners; events are synchronous.

### Notes
- After updating, run:

```bash
composer update glueful/framework
```

- Optional: add listeners in your app service provider to enrich response context (e.g., `context.organization`) or warm caches.

## [1.3.4] - 2025-10-28 — Arcturus

Compatibility release aligning the skeleton with Glueful Framework 1.7.4.

### Changed
- Bump framework dependency to `glueful/framework ^1.7.4`.
  - Picks up a minimal, configurable account‑status gate in `AuthenticationService` and refresh‑token flow, governed by `security.auth.allowed_login_statuses` (default: `['active']`).
  - Adds new migration examples for creating database views/functions in the framework docs.

### Notes
- After updating, run:

```bash
composer update glueful/framework
```

- If you previously used `auth.allowed_login_statuses`, move it to `security.auth.allowed_login_statuses` in your app config.

## [1.3.3] - 2025-10-21 — Pollux

Compatibility release aligning the skeleton with Glueful Framework 1.7.3.

### Changed
- Bump framework dependency to `glueful/framework ^1.7.3`.
  - Picks up QueryBuilder fix for 2‑argument `where($column, $value)` / `orWhere($column, $value)` normalization, improving portability across Postgres/MySQL/SQLite and avoiding TypeError in edge cases.
  - Minor dev‑server log polish: built‑in PHP server access/startup lines no longer reported as errors in `php glueful serve`.

### Notes
- After updating, run:

```bash
composer update glueful/framework
```

## [1.3.2] - 2025-10-21 — Castor

Compatibility release aligning the skeleton with Glueful Framework 1.7.2 and improving Apache defaults.

### Changed
- Bump framework dependency to `glueful/framework ^1.7.2`.
  - Picks up extension route-loading resilience (`ServiceProvider::loadRoutesFrom()` idempotent + exception‑safe).
  - Dev server logs are less noisy (PHP built‑in server access/startup lines no longer printed as errors).
- Harden `public/.htaccess` for Apache deployments:
  - Disable `MultiViews` and directory indexing.
  - Preserve `Authorization` and `X-XSRF-Token` headers for PHP.
  - Canonicalize trailing slashes when not a real directory.

### Notes
- For Nginx, apply equivalent rewrites and header forwarding in the server block.
- After updating, run:

```bash
composer update glueful/framework
```

## [1.3.1] - 2025-10-21 — Canopus Alignment

Compatibility release aligning the skeleton with Glueful Framework 1.7.1.

### Changed
- Bump framework dependency to `glueful/framework ^1.7.1`.
  - Picks up the extension discovery/boot fix (framework now calls `ExtensionManager::discover()` before `::boot()`), ensuring enabled extensions load and their migrations are discovered by CLI commands.

### Notes
- No code changes required in the skeleton. After updating, run:

```bash
composer update glueful/framework
```

If you use extensions, `extensions:list`/`extensions:why` and `migrate:status` will now accurately reflect enabled providers and their migrations after boot.

# [1.3.0] - 2025-10-18 — Procyon Alignment

Compatibility release aligning the skeleton with Glueful Framework 1.7.0.

### Changed
- Bump framework dependency to `glueful/framework ^1.7.0`.
  - Gains fiber-based async scheduler (`FiberScheduler`) with `spawn`, `all`, `race`, `sleep`.
  - Async HTTP client with pooling and streaming (`CurlMultiHttpClient`, `HttpStreamingClient`).
  - Buffered async I/O streams, cooperative cancellation, metrics instrumentation.
  - Promise-style wrapper for ergonomic chaining.

### Notes
- No code changes required in the skeleton. After updating, run:

```bash
composer update glueful/framework
```

To opt-in per route, add the `async` middleware in your route config, or use helpers like `async()`, `await_all()`.

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
