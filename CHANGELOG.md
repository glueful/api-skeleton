# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.

## [1.36.0] - 2026-06-07 — Framework 1.52.0 Mizar

### Added

- **Bundles `glueful/media`** so image processing, thumbnails on upload, on-demand image variants, and media metadata work out of the box — added to `require` (`^1.0.0`) and enabled in `config/extensions.php`, with the key `IMAGE_*` settings documented in `.env.example`. The other three extracted subsystems (`archive`, `cdn`, `queue-ops`) remain opt-in.

### Changed

- Bumped **`glueful/framework` → `^1.52.0`**. The 1.52.0 release extracts four subsystems out of core into standalone, opt-in extensions: **Archive** (`glueful/archive`), **CDN / edge cache** (`glueful/cdn`), **queue ops / autoscaling** (`glueful/queue-ops`), and **image processing / media** (`glueful/media`). Core keeps only the seams (e.g. `EdgeCacheInterface` + `NullEdgeCache`, the per-queue runtime tuning, `ImageSecurityValidator` with built-in defaults). See the framework `UPGRADE.md` for the full per-subsystem "what moved / what stays" notes.

### Removed

- **Aligned the skeleton's published config to framework 1.52.0 — the now-extension-owned config/env it previously shipped was removed:**
  - `config/image.php` — **deleted**. The bundled `glueful/media` provides the `image` config defaults (via `mergeConfig`); publish a `config/image.php` only to override them.
  - `config/cache.php` — removed the `edge` block (the `EDGE_CACHE_*` settings). Edge/CDN config moves to `glueful/cdn`.
  - `config/queue.php` — removed the `workers` ops blocks (`process`, `auto_scaling`, `resource_limits`, `resource_thresholds`, `supervisor`) and the per-queue ops keys (`workers`, `max_workers`, `auto_scale`). `workers` now holds exactly `queues` (per-queue `priority`/`memory_limit`/`timeout`/`max_jobs`, read by the core runtime) + `performance`. Worker-count / autoscaling tuning moves to `glueful/queue-ops` (`queue_ops.*`).
  - `config/capabilities.php` — removed the `archive` key. Archiving's schema gate moved to the `glueful/archive` extension (`ARCHIVE_DATABASE_SCHEMA` now backs the extension's own `archive.enabled`); the core switchboard no longer reads it.
  - `.env.example` — removed the queue-ops vars: `QUEUE_PROCESS_ENABLED`, `QUEUE_AUTO_SCALING`, the `*_QUEUE_AUTO_SCALE` autoscaling toggles, and the `*_QUEUE_WORKERS` / `*_QUEUE_MAX_WORKERS` presets. The per-queue `*_QUEUE_MEMORY` / `*_QUEUE_TIMEOUT` / `*_QUEUE_MAX_JOBS` vars are **kept** (still read by the core runtime).

### Upgrade Notes

- **`glueful/media` ships bundled and enabled** (image processing out of the box).
  Each extension auto-discovers via its `extra.glueful` manifest; run `php glueful migrate:run` for the ones that ship schema (archive). See the framework `UPGRADE.md` for the per-subsystem migration / command-manifest notes.
- **Refresh the command manifest on deploy.** This release removes the core `archive:manage`, `cache:purge`, and `queue:autoscale` commands; a cached `storage/cache/glueful_commands_manifest.php` generated before the upgrade still references them and breaks CLI boot. Run `php glueful commands:cache --clear` as part of the deploy (note: `cache:clear` does **not** clear the command manifest).

---

## [1.35.1] - 2026-06-06 — Email Notification 1.8.0

### Changed

- Bumped **`glueful/email-notification` → `^1.8.0`**. The 1.8.0 release migrates the extension to the framework 1.51.0 notification APIs: `EmailChannel` now implements `RichNotificationChannel` (structured `NotificationResult` — provider message id, latency, retryability), registration moves to the framework's `registerNotificationChannel()` / `registerNotificationExtension()` `boot()` helpers (required now that the framework no longer hardcodes the provider), and notification retry config reads from the channel-agnostic `notifications.retry` key.

### Upgrade Notes

- **No action required.** `composer update glueful/email-notification` picks up 1.8.0. No new env vars (existing `MAIL_RETRY_*` tuning still applies, now surfaced under `notifications.retry`), no migrations. The active email-delivery path is unchanged.

---

## [1.35.0] - 2026-06-06 — Framework 1.51.0

### Changed

- Bumped **`glueful/framework` → `^1.51.0`** (codename *Larawag*). The 1.51.0 release is a five-part refinement of the core notification subsystem: a real in-app **`database`** channel (the default `['database']` channel now resolves end-to-end instead of failing as `channel_not_found`), channel validation moved from construction to **dispatch**, **optional/safe persistence** (`NOTIFICATIONS_DATABASE_STORE=false`), an injectable **async-queue** seam, structured channel results (`NotificationResult`), and **extension-driven** channel registration.

### Upgrade Notes

- **No skeleton action required** for typical apps — `composer update glueful/framework` picks up 1.51.0. No new env vars and no migrations (the `notifications` capability default stays `true`; set `NOTIFICATIONS_DATABASE_STORE=false` to run without a database store).
- **Custom notification code only.** Two breaking changes apply if you touch notification internals directly: `ChannelManager::getAvailableChannels()` was renamed to `getRegisteredChannelNames()` (no aliases; use `getActiveChannelNames()` for available-only names), and the notification jobs/commands (`DispatchNotificationChannels`, `SendNotification`, `ProcessRetriesCommand`, `NotificationRetryTask`) now require an `ApplicationContext` — the queue worker and console kernel already provide one. Custom channel packages must register from `ServiceProvider::boot()` via `registerNotificationChannel()` / `registerNotificationExtension()`. See the framework changelog's Upgrade Notes.

---

## [1.34.1] - 2026-06-05 — Framework 1.50.2

### Changed

- Bumped **`glueful/framework` → `^1.50.2`**. The 1.50.2 patch adds the editor-clean **`@queryParam name:type="…"`** route-doc tag (parsed into the OpenAPI spec, avoiding the reserved-`@param` IDE/Intelephense false positives) and fixes a doc-gen bug that dropped path parameters from routes which also declared a query parameter. Framework-only — no env vars, no migrations, no API breaks.

### Upgrade Notes

- **No action required.** `composer update glueful/framework` picks up 1.50.2; the previous `^1.50.1` constraint already permitted it. The new route-doc tag is opt-in and the legacy `@param` form still works.

---

## [1.34.0] - 2026-06-05 — RBAC Opt-In & Mail Transport Notes

### Changed

- **RBAC (`glueful/aegis`) is now fully opt-in.** It's no longer a bundled `require-dev` dependency, and the `repositories` path-repo block was dropped entirely — the skeleton ships **no** `repositories`. Enable RBAC only when you need permission-gated features: `composer require glueful/aegis` (see README → "Identity, Accounts & RBAC").
- Bumped **`glueful/email-notification` → `^1.7.0`** (Framework 1.50 compatibility: drops a dead event listener that referenced a removed framework interface; raises its minimum framework to `>=1.50.1`).

### Documentation

- **Mail transport guidance** in `.env.example`: SMTP works out of the box (the framework ships `symfony/mailer`); for an API transport (Postmark, Mailgun, Amazon SES, SendGrid, Brevo) install the matching `symfony/<provider>-mailer` package and set `MAIL_MAILER` / the DSN. Points to `glueful/email-notification`'s README for per-provider setup.

### Upgrade Notes

- **No action required.** RBAC was already disabled by default; this only removes the bundled (dev-only) dependency and the local path repo from the template. To add roles/permissions: `composer require glueful/aegis`, enable `AegisServiceProvider` in `config/extensions.php`, run migrations, then `php glueful aegis:bootstrap-admin --user=<uuid-or-email>`.

---

## [1.33.0] - 2026-06-05 — Slim Skeleton & First-Party Identity

### Added

- **`glueful/users ^1.0.0`** (published) as a first-class dependency — first-party identity store + account lifecycle (email verification, password recovery), email-PIN 2FA, and the account read endpoints `GET /me`, `GET /users/{uuid}`, and the paginated `GET /users`.
- **`USERS_*` env flags** in `.env.example` (all default `false`): `USERS_USER_LOOKUP_ENABLED`, `USERS_USER_LIST_ENABLED`, `USERS_USER_LIST_ALLOW_EMAIL_FILTER`.
- **Opt-in RBAC documentation** (`README.md` + `config/extensions.php`): enable `glueful/aegis`, run migrations, then `php glueful aegis:bootstrap-admin --user=<uuid-or-email>` to grant `users.read` and unlock the lookup/list endpoints.
- **Identity-seam test coverage**: `tests/Feature/AuthEndToEndTest.php` (login through the seam; role-gated routes fail closed when no RBAC provider is enabled), test support infra (`tests/Support/`, `tests/Unit/`), and a project `phpunit.xml`.
- **`config/capabilities.php`** — the Core Capability Schema Switchboard: per-capability flags (`scheduler`, `notifications`, `metrics`, `archive`) gating which framework-core platform-capability migrations install. Complements the now framework/extension-owned schema by giving the app one place to opt core schema in/out (auth schema is always installed; `locks`/`queue`/`uploads` are derived from their own config).

### Changed

- **User store extracted into `glueful/users`.** The `users` and `profiles` tables (and the 2FA flag) — previously created by the skeleton's `001_CreateInitialSchema` / `010_AddTwoFactorEnabledToUsers` — are now owned and migrated by the extension. The skeleton depends on it instead of bundling its schema.
- `glueful/users` now resolves from **Packagist (`^1.0.0`)** instead of the local path repository; the `../extensions/users` path-repo entry was removed.

### Removed

- **All bundled core migrations.** The skeleton no longer ships database schema — each table is now owned and migrated by the package that uses it:
  - `001_CreateInitialSchema` (created `users`, `profiles`, `auth_sessions`, `blobs`) and `010_AddTwoFactorEnabledToUsers` → **`glueful/users`** (`users`, `profiles`, 2FA) and **framework core** (`auth_sessions`, blob storage).
  - `008_CreateAuthRefreshTokensTable`, `009_CreateApiKeysTable` → **framework core** (auth/session spine: `auth_refresh_tokens`, `api_keys`).
  - `003_CreateScheduledJobsTables`, `004_CreateNotificationSystemTables`, `005_CreateArchiveSystemTables`, `006_CreateQueueSystemTables`, `007_CreateLocksTable` → **framework core** subsystems (scheduler, notifications, archive, queue, locks).
- `database/migrations/` is now empty (kept via `.gitkeep`) for *your* application migrations.

### Upgrade Notes

- **This is a starter template** — these changes affect newly-scaffolded projects, not a live `composer update` of an existing app.
- **Fresh installs:** `php glueful migrate:run` now applies the framework + enabled-extension migrations only. The identity/auth tables (`users`, `profiles`, `auth_sessions`, `auth_refresh_tokens`, `api_keys`, 2FA) come from the framework core + `glueful/users` (enabled by default) — do not re-add the removed skeleton migrations.
- **Permission-gated endpoints** (`GET /users`, `GET /users/{uuid}`) require an RBAC provider. They are off by default; see the README "Identity, Accounts & RBAC" section to enable `glueful/aegis` and bootstrap an admin.

---

## [1.32.0] - 2026-06-01 — Dependency Hardening

### Changed

- Bump framework dependency to `glueful/framework ^1.49.0` (the previous `^1.48.0` already permitted 1.49.0; bumped for clarity).

### Framework Changes Included

- **`Http\Client` forwards `auth_basic`** for per-request HTTP Basic auth (previously dropped).
- **`whatsapp` is a supported `SendNotification` queue type** for async phone-messaging channel delivery.
- **Intervention Image v4** (`^4.1`); the framework's `ImageProcessor`/`image()` API is unchanged.
- **Security**: all known dependency advisories patched within `^7.4`/`^10.5` (Symfony 7.4.x incl. HIGH-severity mailer/mime CVEs, PHPUnit 10.5.63); `composer audit` clean.

### Upgrade Notes

- **No action required.** Framework-only release — no migrations, no env vars, no skeleton-side code changes. `composer update` is sufficient. Apps using `intervention/image` *directly* (not via the framework's image helper) should move to its v4 API.

```bash
composer update glueful/framework
```

---

## [1.31.0] - 2026-05-31 — Router Verb Completeness

### Changed

- Bump framework dependency to `glueful/framework ^1.48.0` (the previous `^1.47.0` already permitted 1.48.0; bumped for clarity).

### Framework Changes Included

- **First-class `PATCH` and `OPTIONS` routing verbs**: New `$router->patch()` / `$router->options()` shortcuts and `#[Patch]` / `#[Options]` attributes; the `#[Route(methods: [...])]` array form now accepts `PATCH`, `OPTIONS` and `HEAD`. Both verbs were previously unreachable through the public routing API.
- **Explicit `OPTIONS` routes beat auto-CORS**: an explicitly registered `OPTIONS` route now runs its own handler; automatic CORS preflight (204 + `Allow`) remains the default when none is registered.
- **Route precedence documented and pinned** (static > dynamic; literal first segment > parameter first segment; registration order within a group).

### Upgrade Notes

- **No action required.** Framework-only release — no migrations, no env vars, no skeleton-side changes. `composer update` is sufficient.

```bash
composer update glueful/framework
```

---

## [1.30.0] - 2026-05-30 — Extension System Re-Architecture

### Changed

- Bump framework dependency to `glueful/framework ^1.47.0`.
- **Migrated `config/extensions.php` to the single-key model** — a single `enabled` array of plain string FQCNs (no `::class`, no `only` / `dev_only` / `disabled` / `local_path` / `scan_composer`). Ships empty (no extensions enabled by default).
- **Migrated `config/serviceproviders.php` to the single-key model** — `enabled` now holds the app's own providers as string FQCNs (`'App\\Providers\\AppServiceProvider'`).

### Framework Changes Included

- **Extension System Re-Architecture**: Composer-only discovery + a single `enabled` allow-list + a pure resolver that validates (missing provider/dependency, framework-version mismatch, cycle) and topologically orders providers. `extensions:enable|disable` validate before writing and recompile; `extensions:list` shows state (`✓` / `○` / `⚠`); `extensions:cache` is strict; `create:extension` scaffolds a Composer package + path repository. `ProviderLocator`, the local-folder scan, runtime PSR-4 registration, and `extensions:why` are removed. Adds `composer/semver`.

### Upgrade Notes

- **Breaking config change.** If you customized `config/extensions.php` / `config/serviceproviders.php`, convert them to the single `enabled` list of plain string FQCNs; map old keys per the framework's `docs/EXTENSIONS_UPGRADE.md`.
- **Run `composer update`** so the new `composer/semver` dependency installs (the resolver fatals at boot without it).
- **Enable extensions explicitly** (`php glueful extensions:enable <name>`) and **run `php glueful extensions:cache` in production** — installing a package no longer auto-loads it, and production boots only from the compiled manifest.

```bash
composer update glueful/framework
php glueful extensions:cache   # required in production
```

---

## [1.29.0] - 2026-05-28 — Fluent Query Caching

### Changed

- Bump framework dependency to `glueful/framework ^1.46.0`.

### Framework Changes Included

- **Fluent query result caching — `QueryBuilder::cache(?int $ttl = null, array $tags = [])`**: The fluent cache method now actually caches read queries (`get`/`first`/`count`/`max`) via `QueryCacheService`, with automatic per-table tags plus any caller-supplied `$tags` for targeted invalidation. Previously it was a silent no-op. Per-query (no global toggle), backward compatible, degrades to uncached if no cache backend is configured.
- **Framework-wide PHPStan level-8 hardening (initiative kickoff)**: Internal typing fixes in the query-binding path (behavior-preserving); full `~914`-error level-8 gap catalogued in the framework's `docs/LEVEL8_TYPING_DEBT.md`. CI gate remains level 6.

### Upgrade Notes

- **No action required.** Framework-only release — no migrations, no env vars, no skeleton-side changes. `composer update` is sufficient.
- **`->cache()` semantics changed from "no-op" to "actually caches"** on the framework side. If application code calls `->cache(ttl)` expecting nothing to happen, it'll now cache. The cache key is `query+params` (no auth/context scoping); make sure cached queries don't return user-scoped rows without a discriminator in the SQL.

```bash
composer update glueful/framework
```

---

## [1.28.0] - 2026-05-27 — The Second Factor

### Added

- **`database/migrations/010_AddTwoFactorEnabledToUsers.php`** — Adds the `users.two_factor_enabled` boolean column (default `false`) required by the framework's core email-PIN 2FA feature. Idempotent (`hasColumn()` guard). Run `php glueful migrate:run` after upgrading. Only needed if you intend to enable 2FA.
- **`.env.example` entries for the `TWO_FACTOR_*` env vars** — `TWO_FACTOR_ENABLED` (default `false`) plus optional tunables (`TWO_FACTOR_PIN_LENGTH`, `TWO_FACTOR_PIN_TTL`, `TWO_FACTOR_CHALLENGE_TTL`, `TWO_FACTOR_DISABLE_FRESHNESS`, `TWO_FACTOR_TEMPLATE`), mirroring the framework's `config/auth.php`.

### Changed

- Bump framework dependency to `glueful/framework ^1.45.0`.

### Framework Changes Included

- **Core email-PIN 2FA (opt-in, off by default)**: `POST /auth/login` for an enrolled user returns a `challenge_token` and emails a 6-digit PIN; the client completes login at `POST /2fa/verify`. New `/2fa/enable|verify|disable` routes (registered only when `TWO_FACTOR_ENABLED=true`), `2fa:enable|disable|status` CLI, and a `config/auth.php` `two_factor` block. `/2fa/verify` re-validates the account before issuing a session and writes a session-scoped freshness marker that gates `/2fa/disable`. Requires `glueful/email-notification` for the email channel + `two-factor-pin` template.
- **`selectRaw()` parameter bindings**: `QueryBuilder::selectRaw(string $expression, array $bindings = [])` binds positional `?` values; backward compatible. New `docs/SECURITY.md` documents the SQL-injection and XSS model.
- **`AdminPermissionMiddleware` MFA cleanup**: removed the dead `X-MFA-Token` header path; `require_mfa` reads only the session handshake.

### Upgrade Notes

- **2FA is opt-in.** If you do not set `TWO_FACTOR_ENABLED=true`, this release is behavior-identical to 1.27.0 — the migration is optional and the `/2fa/*` routes are not registered. To enable: run the `010` migration, install `glueful/email-notification`, set `TWO_FACTOR_ENABLED=true`, and enroll users.
- **New optional env vars.** See `.env.example`. All default to safe values that preserve current behavior.

```bash
composer update glueful/framework
php glueful migrate:run   # only if enabling 2FA
```

---

## [1.27.0] - 2026-05-22 — Closing the Trust Gaps

### Changed

- Bump framework dependency to `glueful/framework ^1.44.0`.

### Framework Changes Included

- **Real tag-aware cache invalidation on the Redis driver**: `RedisCacheDriver::addTags()` / `invalidateTags()` are now backed by Redis SETs (`_gf_tag:{tag}` → set of cache keys). `getCapabilities()['features']['tags']` is now `true`. Unblocks `QueryCacheService`, `DistributedCacheService`, `ResponseCachingTrait`, and `php glueful cache:clear --tags` — all of which previously called the methods only to receive a silent `false`. Memcached and File drivers remain no-ops with explicit documentation.
- **Real `ArchiveService::restoreFromArchive()`**: Replays archived rows into a target table inside a database transaction. Honors `ArchiveRestoreOptions`: `targetTable`, `offset`/`limit`, and `conflictResolution` (`skip` records conflicts; `overwrite` hard-deletes to bypass soft-delete then reinserts). Previously always returned a typed failure regardless of input.
- **`security:report` stripped to honest sections**: Removed all `rand()`-driven sections (authentication, audit_summary, vulnerabilities, metrics), the hardcoded `compliance` block, and the `sendReportByEmail()` stub. Removed `--include-vulnerabilities`, `--include-metrics`, `--email`, `--days` options and the PDF format. Command now exports HTML/JSON/text reports of the production readiness score, environment configuration, system info, and recommendations only. Use `security:vulnerabilities` for dependency CVE scanning.
- **`fields:whitelist-check` inspects real routes**: Replaced the hardcoded three-entry placeholder route list with real introspection via `Router::getStaticRoutes()`/`getDynamicRoutes()` and `Route::getFieldsConfig()`. Added a new low-severity `NON_STRICT_WHITELIST` finding for `/api/` routes with non-strict whitelists.
- **README cache claim narrowed to reality**: Documents Redis-only tag support instead of the previous unqualified "tagging" claim.

### Upgrade Notes

- **No new migrations.** The Errai release is purely framework-side — no schema changes. `composer update` is sufficient.
- **`security:report` output shape changed.** Consumers parsing the JSON output should expect `authentication`, `audit_summary`, `vulnerabilities`, `metrics`, and `compliance` keys to be absent. Scripts depending on the removed `--include-vulnerabilities`, `--include-metrics`, `--email`, or `--days` options will throw `InvalidOptionException`. PDF format is also gone; only `html`, `json`, and `text` accepted.
- **Cache tagging is Redis-only.** `addTags()` / `invalidateTags()` calls on Memcached and File drivers continue to return `false`. Branch on `$cache->getCapabilities()['features']['tags']` if you need driver-agnostic behavior, or switch to Redis for real invalidation.
- **`fields:whitelist-check` will report your real routes now.** Routes without `#[Fields]` or with non-strict whitelists may surface as findings — previously the command analyzed the same three placeholder entries every run.
- **`restoreFromArchive()` no longer always fails.** Code that called this method and treated the failure as expected (e.g., catch-and-log scaffolding) should be reviewed — it will actually restore rows now.

```bash
composer update glueful/framework
```

---

## [1.26.0] - 2026-05-21 — Production Hardening

### Added

- **`database/migrations/009_CreateApiKeysTable.php`** — Schema migration for the framework's hardened API key system. Creates `api_keys` with columns for `uuid`, `user_id`, `name`, `key_prefix` (indexed), `key_hash` (`UNIQUE`), `scopes` (JSON), `allowed_ips` (JSON), `expires_at`, `rotated_from_id`, `revoked_at`, and timestamps. Run `php glueful migrate:run` after upgrading.
- **`.env.example` entries for `DB_LAZY_LOADING_MODE`** — New env var mirroring the framework's N+1 detection feature.

### Changed

- Bump framework dependency to `glueful/framework ^1.43.0`.

### Framework Changes Included

- **ORM-aware N+1 query detection**: `PreventsLazyLoading` trait on `Model` flags lazy-loaded relations on members of a hydrated collection. Four modes (`off`/`warn`/`strict`/`auto`) configured via `DB_LAZY_LOADING_MODE`. See `docs/ORM/N_PLUS_ONE_DETECTION.md`.
- **Driver-aware `$query->explain()`**: SQLite uses `EXPLAIN QUERY PLAN`; MySQL/PostgreSQL use `EXPLAIN`. New `Builder::explain()` on the ORM applies global scopes.
- **Kubernetes-conventional health probes**: `GET /health/live`, `GET /health/ready`, `GET /health/startup`. Existing `/healthz` and `/ready` keep working.
- **Hardened API keys**: Dedicated `api_keys` table with per-key scopes, CIDR allowlists, expiration, rotation with grace period, and environment-prefixed plaintext (`gf_live_*` / `gf_test_*`). New `#[RequireScope]` route attribute auto-attaches the `require_scope` middleware. CLI: `apikey:create|list|rotate|revoke`. See `docs/API_KEYS.md`.
- **ORM bug fixes**: Property access routes to relations correctly; `??` triggers lazy-load instead of swallowing; child models inherit parent's `ApplicationContext`; eager loading no longer emits `WHERE x = NULL`.
- **`ApiKeyAuthenticationProvider` is single-track**: The legacy `users.api_key` fallback is removed. `UserRepository::findByApiKey()` is also removed (zero callers verified).

### Upgrade Notes

- **Run the migration** — `php glueful migrate:run` to create the `api_keys` table. The new auth provider and `apikey:*` CLI commands both require it.
- **No automatic data migration from `users.api_key`** — the canonical schema (`001_CreateInitialSchema.php`) never had that column. If you customized your schema to add one, use `php glueful apikey:create --user=<uuid> --name=<label>` or `ApiKeyService::create()` programmatically to migrate keys.
- **`UserRepository::findByApiKey()` is gone.** Remove any external references.
- **New env var (optional): `DB_LAZY_LOADING_MODE`.** Default `auto` (warn in dev, off in prod). Set `strict` in CI to fail tests on accidental N+1 patterns.

```bash
composer update glueful/framework
php glueful migrate:run
```

---

## [1.25.0] - 2026-05-20 — OpenAPI Spec Excellence

### Changed

- Bump framework dependency to `glueful/framework ^1.42.0`

### Framework Changes Included

- **OpenAPI spec quality overhaul**: Generated `openapi.json` now declares all configured security schemes via `SecuritySchemeRegistry` driven by `documentation.security_schemes` / `middleware_map` config. Per-operation `security` is derived from route middleware instead of being hardcoded.
- **Unified `ErrorResponse` schema**: New component describing the `{success, message, error: {code, error_code, timestamp, request_id}}` envelope with an `error_code` enum. All CRUD 4xx responses `$ref` it.
- **Deterministic operation IDs**: New `OperationIdGenerator` produces camelCase SDK method names and closes a gap where comment-driven generation emitted operations without any `operationId`.
- **Pagination + field-selection schemas**: `PaginationMeta`, `PaginationLinks`, per-resource envelope schemas matching `PaginatedResourceResponse`. New `addRouteWithFieldsAttribute()` helper surfaces `?fields=` / `?expand=` from `#[Fields]` attributes.
- **Auto-derived request examples**: `ExampleDeriver` populates JSON request bodies with realistic values inferred from Validator rules and schema properties; `@example` annotation overrides the derived value.
- **OpenAPI 3.1 webhooks**: New `WebhookDocsBuilder` emits a top-level `webhooks` object from `documentation.webhooks` config — documents `X-Glueful-Signature` / `X-Glueful-Timestamp` headers and the `WebhookEnvelope` payload shape.
- **`generate:client` CLI wrapper**: Thin command that shells out to `openapi-typescript` or `openapi-generator-cli` with safe defaults. Glueful does not own codegen logic.

### Upgrade Notes

- **Permission exception envelope (breaking)** — `PermissionUnauthorizedException` now returns the unified `{success, message, error: {code, error_code, timestamp, request_id}}` shape instead of the legacy top-level `{code, error_code}` form. Consumers reading `code`/`error_code` at the top level must read `error.code`/`error.error_code` instead.
- **Regenerate SDK clients** — Operation IDs are now deterministic camelCase; the new components (`ErrorResponse`, `PaginationMeta`, `PaginationLinks`, `WebhookEnvelope`) require regeneration to surface in client types.
- No new env vars. Configuration extensions live in `config/documentation.php`.

```bash
composer update glueful/framework
```

---

## [1.24.0] - 2026-03-03 — Profile-Driven Logging Bootstrap

### Changed

- Bump framework dependency to `glueful/framework ^1.41.0`
- Updated `.env.example` with `LOG_PROFILE`, retention env keys, and production baseline example

### Framework Changes Included

- **Profile-driven logging bootstrap**: `config/logging.php` now resolves deterministic defaults from `LOG_PROFILE` (or `APP_ENV`) with built-in `development`, `staging`, `production`, and `testing` profiles. Explicit env vars override profile defaults.
- **Production safety checks**: `system:check --production` now flags no durable log sink, disabled event/audit toggles, debug-level logging, and invalid retention values.
- **Upsert column fix**: `InsertBuilder::buildUpsertQuery()` now passes column names to driver `upsert()` SQL builders, fixing PostgreSQL crash on null values.
- **Audit toggle alignment**: Framework boot and `LogManager` now respect `EVENTS_ENABLED`, `EVENTS_AUDIT_LOGGING`, and `LOG_TO_FILE=false` correctly.

### Upgrade Notes

- **`LOG_TO_DB` default changed**: Previously defaulted to `true` when absent. Now defaults to `false` in all profiles. Add `LOG_TO_DB=true` to your `.env` if you require database logging.
- New env vars recognized: `LOG_PROFILE`, `LOG_RETENTION_*_DAYS`. No action needed if unset.

```bash
composer update glueful/framework
```

---

## [1.23.4] - 2026-02-21 — WhereClause null fixes

### Changed

- Bump framework dependency to `glueful/framework ^1.40.4`

### Framework Fixes Included

- **PHPCS line length in `WhereClause`**: Code style fix only — extracted long error message string in `getConditionsArray()` to comply with 120-character line limit. No runtime behavior changes.

### Notes

Patch release. No breaking changes.

```bash
composer update glueful/framework
```

---

## [1.23.3] - 2026-02-21 — Mutation WHERE + Queue Config + Async Notification

### Changed

- Bump framework dependency to `glueful/framework ^1.40.3`

### Framework Fixes Included

- **Mutation WHERE operator support**: `WhereClause`, `UpdateBuilder`, and `DeleteBuilder` now handle `<`, `<=`, `>`, `>=`, `!=`, `LIKE`, `IN`, `IS NULL`, `IS NOT NULL` in UPDATE/DELETE queries instead of crashing on non-equality conditions.
- **Queue Redis config string coercion**: `DriverRegistry` accepts numeric-string ints/ports and boolean-like strings from `.env`, fixing config rejection in production.
- **Async notification best-effort**: `NotificationService::queueAsyncDispatch()` wrapped in try/catch so side-effect failures don't crash primary API operations.

### Notes

Patch release. No breaking changes.

```bash
composer update glueful/framework
```

---

## [1.23.2] - 2026-02-21 — Config Merge Safe Dedup

### Changed

- Bump framework dependency to `glueful/framework ^1.40.2`

### Framework Fixes Included

- **Config merge safe dedup for nested lists**: `mergeConfig()` list dedup replaced `array_unique()` with hash-based dedup using `json_encode`/`serialize` for complex items. Fixes 500 errors on event-driven flows (e.g., comment creation) that dispatch through queue/webhook listeners loading merged config with nested array items like `queue.monitoring.alert_rules`.

### Notes

Patch release. No breaking changes.

```bash
composer update glueful/framework
```

---

## [1.23.1] - 2026-02-21 — Config Merge Fix

### Changed

- Bump framework dependency to `glueful/framework ^1.40.1`

### Framework Fixes Included

- **Config merge `array_unique()` on nested arrays**: `mergeConfig()` no longer applies `array_unique()` to nested associative arrays (e.g., `session.providers`), fixing "Array to string conversion" warnings. True lists are list-merged with dedup; associative arrays are deep-merged recursively.

### Notes

Patch release. No breaking changes.

```bash
composer update glueful/framework
```

---

## [1.23.0] - 2026-02-21 — Notification Delivery Orchestration

Release aligning the skeleton with Glueful Framework 1.40.0 (Alnair), which adds notification split delivery, per-channel delivery tracking, DB-indexed idempotency, and provisioning error semantics.

### Changed

- Bump framework dependency to `glueful/framework ^1.40.0`
- **`004_CreateNotificationSystemTables.php`**: Added `notification_deliveries` table for per-channel delivery state tracking (`notification_uuid`, `channel`, `status`, `attempt_count`, `last_error`, `last_attempt_at`, `sent_at`) with unique key on `(notification_uuid, channel)`. Added `idempotency_key` column with index to `notifications` table.

### Framework Features Now Available

This release includes features from Glueful Framework 1.40.0:

#### Split Delivery API
- `NotificationService::sendSplit()` provides first-class sync/async channel separation. `send()` supports `sync_channels`, `async_channels`, `channel_failure_policy` (`any_success`, `require_critical`, `all`), and `critical_channels`.

#### Per-Channel Delivery Tracking
- New `notification_deliveries` table and repository APIs track delivery lifecycle per channel. Async retries target only failed channels — already-sent channels are skipped.

#### DB-Indexed Idempotency
- Dedicated `notifications.idempotency_key` column with index replaces the previous `_meta` JSON scan. Channel-level idempotency via unique key on `(notification_uuid, channel)`.

#### Provisioning Exception
- `ProvisioningException` for account setup failures maps to HTTP 500 and `api` log channel, replacing misleading 401 responses.

### Notes

After updating, run:

```bash
composer update glueful/framework
php glueful migrate:run
```

Non-backward-compatible changes to notification sending flow and response structure.

---

## [1.22.0] - 2026-02-20 — Token/Session Reimplementation

Release aligning the skeleton with Glueful Framework 1.39.0 (Menkent), which replaces the legacy token/session model with a security-first architecture.

### Changed

- Bump framework dependency to `glueful/framework ^1.39.0`
- **`001_CreateInitialSchema.php`**: `auth_sessions` table updated for new auth model — added `session_version`, `expires_at`, `last_seen_at`, `revoked_at`, `provider`, `remember_me`; removed legacy token columns (`access_token`, `refresh_token`, `access_expires_at`, `refresh_expires_at`, `token_fingerprint`, `last_token_refresh`) and their associated indexes.

### Added

- **`008_CreateAuthRefreshTokensTable.php`**: New migration creating the `auth_refresh_tokens` table for hash-only refresh token storage with one-time rotation, replay detection, and token family lineage (`parent_uuid`, `replaced_by_uuid`). Includes foreign keys to `auth_sessions` and `users`, unique index on `token_hash`, and a safety `ALTER TABLE` to add `session_version` to `auth_sessions` for upgrades from older schemas.

### Framework Features Now Available

This release includes features from Glueful Framework 1.39.0:

#### Hash-Only Refresh Tokens
- Refresh tokens are stored as SHA-256 hashes only — no raw tokens at rest. One-time-use rotation in a single `SELECT ... FOR UPDATE` transaction prevents race conditions.

#### Session Versioning
- Access JWTs carry `sid` (session UUID) and `ver` (session version) claims. Validation checks server-side session state, enabling instant invalidation via version bump without a token blocklist.

#### Replay Detection
- Presenting a consumed/revoked refresh token triggers session-scope revocation of all active tokens for that session.

#### New Service Architecture
- `RefreshService`, `AccessTokenIssuer`, `ProviderTokenIssuer`, `SessionRepository`, `RefreshTokenRepository`, `RefreshTokenStore`, `SessionStateCache`, and `AuthenticatedUser` value object.

#### Session Cleanup Expansion
- `SessionCleanupTask` now cleans both `auth_sessions` and `auth_refresh_tokens` with configurable retention windows.

### Notes

After updating, run:

```bash
composer update glueful/framework
php glueful migrate:run
```

**Breaking change**: All existing sessions and tokens become invalid. Users must re-authenticate after deployment.

---

## [1.21.0] - 2026-02-17 — Auth Token-Refresh Optimization

Release aligning the skeleton with Glueful Framework 1.38.0 (Lesath), which optimizes auth token-refresh performance by eliminating redundant database lookups and adding request-level caching.

### Changed

- Bump framework dependency to `glueful/framework ^1.38.0`

### Added

- **`idx_auth_sessions_refresh_status` index**: B-tree composite index on `(refresh_token, status)` in `001_CreateInitialSchema` migration, aligned with the framework's optimized refresh-token lookup query pattern.
- **`idx_auth_sessions_access_status` index**: B-tree composite index on `(access_token, status)` in `001_CreateInitialSchema` migration, aligned with the framework's access-token session lookup query pattern.

### Framework Features Now Available

This release includes features from Glueful Framework 1.38.0:

#### Token-Refresh DB Lookup Reduction
- `TokenManager` now fetches `provider` and `remember_me` in the initial session query, eliminating two subsequent `auth_sessions` lookups during token refresh. Per-refresh DB round-trips reduced from 3 to 1.

#### AuthenticationService DI Cleanup
- `refreshTokens()` resolves the session via `SessionStore::getByRefreshToken()` up front. Removed direct `new Connection()` instantiation in favour of the injected `UserRepository`, improving testability and DI consistency.

#### Request-Level Refresh-Token Cache
- `SessionStore::getByRefreshToken()` now caches results in `$requestCache`, matching the existing access-token pattern. Repeated lookups within the same request hit memory instead of the database.

### Notes

After updating, run:

```bash
composer update glueful/framework
```

No breaking changes. The `auth_sessions` indexes are added to the initial schema migration — existing databases should add them manually:

```sql
CREATE INDEX IF NOT EXISTS idx_auth_sessions_refresh_status ON auth_sessions (refresh_token, status);
CREATE INDEX IF NOT EXISTS idx_auth_sessions_access_status ON auth_sessions (access_token, status);
ANALYZE auth_sessions;
```

---

## [1.20.0] - 2026-02-15 — Deferred Extension Commands

Release aligning the skeleton with Glueful Framework 1.37.0 (Kaus), which fixes extension CLI command registration, ORM Builder pagination, webhook DI wiring, and OpenAPI documentation generation.

### Changed

- Bump framework dependency to `glueful/framework ^1.37.0`

### Framework Features Now Available

This release includes features from Glueful Framework 1.37.0:

#### Deferred Extension Commands
- Extension CLI commands registered during `boot()` before the console application exists are now deferred and picked up automatically when the console app is created. Previously, these commands were silently dropped.

#### ORM Builder Pagination Fix
- `Builder::forPage()` now calls `limit()` before `offset()`, fixing "OFFSET requires LIMIT" errors from `QueryValidator` during ORM query pagination.

#### ExtendsBuilder Interface
- New `ExtendsBuilder` contract for scopes that add macros to the ORM Builder (e.g., `SoftDeletingScope`). Replaces duck-typed `method_exists()` checks with a proper interface.

#### WebhookDispatcher DI Fix
- The webhook dispatcher factory now correctly receives `ApplicationContext`, fixing "ApplicationContext is required for webhook dispatch" errors.

#### OpenAPI Documentation Hardening
- Documentation generation now works correctly in CLI mode with proper server URL fallback chain and valid JSON output for empty properties.

### Notes

After updating, run:

```bash
composer update glueful/framework
```

No breaking changes. Extension CLI commands that were previously lost now register correctly.

---

## [1.19.0] - 2026-02-14 — Model Event Isolation

Release aligning the skeleton with Glueful Framework 1.36.0 (Jabbah), which fixes cross-model event leaking and base64 upload file extensions.

### Changed

- Bump framework dependency to `glueful/framework ^1.36.0`

### Framework Features Now Available

This release includes features from Glueful Framework 1.36.0:

#### Model Event Isolation
- ORM model event callbacks are now scoped per class. Previously, a `creating` callback registered in one model (e.g., `EntityType`) would also fire when a different model (e.g., `Entity`) was created, causing `TypeError` exceptions. Each model now only fires its own registered listeners.

#### Boot-safe Event Registration
- `registerModelEvent()` no longer instantiates a model to validate event names, eliminating "No database connection available" errors when models boot without `ApplicationContext`.

### Notes

After updating, run:

```bash
composer update glueful/framework
```

No breaking changes. Existing model event listeners continue to work — they're now correctly isolated to their own model class.

---

## [1.18.0] - 2026-02-14 — Cloud Storage Compatibility

Release aligning the skeleton with Glueful Framework 1.35.0 (Izar), which fixes S3/R2 upload failures and blob retrieval issues.

### Changed

- Bump framework dependency to `glueful/framework ^1.35.0`
- Default `uploads.path_prefix` changed from `'uploads'` to `''` in `config/uploads.php` to prevent double `uploads/uploads/` path when the storage disk root is already `storage/uploads/`

### Framework Features Now Available

This release includes features from Glueful Framework 1.35.0:

#### Cloud Storage Direct Write
- `FlysystemStorage::store()` now writes directly via `writeStream()` for cloud disks (S3, R2, GCS, Azure) instead of the atomic temp+move pattern. The move step's CopyObject operation fails on Cloudflare R2 and some S3-compatible stores. Local disks retain the atomic pattern for crash safety.

#### Blob Retrieval Fix
- `BlobRepository::findByUuidWithDeleteFilter()` no longer returns false 404s. The method incorrectly passed operator arrays (`['!=', 'deleted']`) to the query builder's array-format `where()`, which always uses `=`. Rewritten to use explicit three-parameter format.

#### Storage Error Propagation
- Upload errors now include the underlying exception message (`Storage write failed: <details>`) instead of a generic message, making S3/R2 configuration issues diagnosable from the API response.

#### Base64 Upload File Extensions
- Base64 uploads now produce files with the correct extension (e.g., `.png`, `.jpg`) derived from the MIME type, instead of always using `.bin`.

### Notes

After updating, run:

```bash
composer update glueful/framework
```

No breaking changes. S3/R2 uploads that previously returned 500 `io_move_failed` now succeed. Blobs that returned 404 despite existing in the database now resolve correctly.

---

## [1.17.0] - 2026-02-14 — Hardened Auth Pipeline

Release aligning the skeleton with Glueful Framework 1.34.0 (Hamal), which hardens the authentication pipeline, DI wiring, and queue serialization.

### Changed

- Bump framework dependency to `glueful/framework ^1.34.0`

### Framework Features Now Available

This release includes features from Glueful Framework 1.34.0:

#### Auth Middleware Exception Isolation
- `AuthMiddleware::handle()` no longer swallows downstream controller/middleware exceptions as 401 "Authentication error occurred". The `$next($request)` call is now outside the auth try/catch block, so DI resolution errors, storage failures, and other non-auth exceptions propagate correctly to the framework exception handler.

#### Dual-Stack Token Extraction
- Both `AuthMiddleware` and `JwtAuthenticationProvider` now fall back to extracting the Bearer token from the Symfony `Request` object when PSR-7 `RequestContext`-based extraction returns null. Fixes authentication failures on Apache CGI/FastCGI configurations where multipart requests don't populate the PSR-7 Authorization header.

#### Relaxed JWT Claim Requirements
- `Utils::getUser()` now only requires the `uuid` claim (previously required `uuid`, `role`, and `info`). Missing `role` defaults to `null`, missing `info` defaults to `[]`. The method also checks request attributes set by auth middleware before attempting token extraction.

#### Queue Serialization Safety
- `DriverRegistry::getDriver()` cache key generation replaced `serialize($config)` with filtered `json_encode()`, preventing "Serialization of 'Closure' is not allowed" crashes when queue config contains connection factories.

#### UploadController DI Registration
- `StorageProvider` now registers `FileUploader` and `UploadController` as factory definitions with config-driven constructor parameters, fixing "Service not found" errors for blob uploads.

#### Login Tracking Cleanup
- `AuthenticationService` no longer attempts to UPDATE `ip_address`, `user_agent`, `x_forwarded_for_ip_address`, or `last_login_date` on the `users` table during login. This tracking data is stored in `auth_sessions`.

### Notes

After updating, run:

```bash
composer update glueful/framework
```

No breaking changes. Code relying on `Utils::getUser()` returning non-null `role` or `info` should use null-safe access (`$user['role'] ?? 'default'`).

---

## [1.16.0] - 2026-02-14 — Container-Enforced Request Resolution

Release aligning the skeleton with Glueful Framework 1.33.0 (Gacrux), which eliminates all `fromGlobals()` fallbacks from service code.

### Changed

- Bump framework dependency to `glueful/framework ^1.33.0`

### Framework Features Now Available

This release includes features from Glueful Framework 1.33.0:

#### Container-Enforced Request Resolution
- All auth services (`TokenManager`, `JwtAuthenticationProvider`, `SessionStore`, `EmailVerification`, `AuthenticationService`) now resolve `RequestContext` from the DI container's shared singleton instead of calling `RequestContext::fromGlobals()` as a fallback
- Utility services (`RequestHelper`, `Utils`, `Cors`, `SpaManager`, `UserRepository`, `SecurityManager`) similarly resolve `Request`/`RequestContext` from the container
- `CoreProvider`'s `'request'` alias now delegates to `RequestProvider`'s shared factory instead of independently calling `createFromGlobals()`

#### Memory Safety
- Fixes unbounded memory growth on high-header requests where multiple independent `fromGlobals()` calls each reconstructed PSR-7 request objects from `$_SERVER` superglobals (crash at Nyholm `MessageTrait.php` with 512MB exhaustion)

#### Long-Running Server Compatibility
- Services no longer read stale `$_SERVER` globals — all request data comes from the container-managed singleton that is reset between requests via `Container::reset()`
- Relevant for RoadRunner, Swoole, and FrankenPHP deployments (`APP_LONG_RUNNING=true`)

#### Silent Fallback Removal
- `SessionStoreResolver` and `TokenManager::getSessionStore()` no longer silently construct bare `SessionStore()` instances on container failure — errors surface immediately with clear `\RuntimeException` messages

#### Interface Addition
- `SessionStoreInterface::resetRequestCache()` added to the interface (previously only on the implementation)

### Notes

After updating, run:

```bash
composer update glueful/framework
```

No breaking changes. The skeleton already uses proper DI for all framework services — no direct instantiation patterns are affected.

---

## [1.15.0] - 2026-02-11 — Schema Builder Callback API

Release aligning the skeleton with Glueful Framework 1.32.0 (Fomalhaut), featuring the `alterTable` callback API.

### Changed

- Bump framework dependency to `glueful/framework ^1.32.0`
- **Users table schema**: Removed `user_agent`, `ip_address`, `x_forwarded_for_ip_address`, and `last_login_date` columns from the `users` table in `database/migrations/001_CreateInitialSchema.php`. These fields belong in `auth_sessions`, not the users table. Added `updated_at` timestamp.

### Framework Features Now Available

This release includes features from Glueful Framework 1.32.0:

#### Dual-Mode `alterTable` API
- `alterTable()` now accepts an optional callback parameter, mirroring the `createTable` dual-mode pattern
- Without a callback: returns a fluent `TableBuilder` for chaining (existing behavior unchanged)
- With a callback: passes the builder to the callback, auto-executes the ALTER statements, and returns `$this` for schema-level chaining

```php
// Fluent mode (unchanged)
$schema->alterTable('users')->addColumn('avatar', 'string')->execute();

// Callback mode (new)
$schema->alterTable('users', function ($table) {
    $table->string('avatar')->nullable();
    $table->index('email');
});
```

#### ColumnBuilder Finalization Safety
- Callback path calls `gc_collect_cycles()` before executing, ensuring `ColumnBuilder` destructors register columns via `finalizeColumn()` before the ALTER SQL is generated

### Notes

After updating, run:

```bash
composer update glueful/framework
```

No breaking changes. All existing schema builder usage continues to work unchanged.

---

## [1.14.0] - 2026-02-09 — Context Propagation

Release aligning the skeleton with Glueful Framework 1.31.0 (Enif), featuring centralized context propagation.

### Changed

- Bump framework dependency to `glueful/framework ^1.31.0`

### Framework Features Now Available

This release includes features from Glueful Framework 1.31.0:

#### ORM Default Context
- `Model::setDefaultContext()` enables static model calls like `User::find($id)` without passing `ApplicationContext` as the first argument
- Framework sets the default context automatically during boot
- Explicit context passing (`User::find($context, $id)`) continues to work and takes priority

#### Centralized Context Propagation
- Framework boot now sets `ApplicationContext` on core services: `Model`, `Utils`, `CacheHelper`, `SecureErrorResponse`, `RoutesManager`, `ImageProcessor`, `ConfigManager`, `Webhook`, `RequestUserContext`
- Eliminates the need for scattered manual `setContext()` calls in application code

### Notes

After updating, run:

```bash
composer update glueful/framework
```

No breaking changes. Default context is only available after `Framework::boot()` completes.

---

## [1.13.1] - 2026-02-09 — Auth Provider Fix

Patch release aligning with Glueful Framework 1.30.1.

### Changed

- Bump framework dependency to `glueful/framework ^1.30.1`

### Framework Fixes Included

- **JWTService context initialization**: `JWTService` context is now set before auth providers are initialized in `AuthBootstrap`, fixing potential null context errors during social and third-party auth provider registration

### Notes

After updating, run:

```bash
composer update glueful/framework
```

No breaking changes.

---

## [1.13.0] - 2026-02-09 — Exception Handler Consolidation

Release aligning the skeleton with Glueful Framework 1.30.0 (Diphda), featuring unified exception handling.

### Changed

- Bump framework dependency to `glueful/framework ^1.30.0`

### Framework Features Now Available

This release includes features from Glueful Framework 1.30.0:

#### Unified Exception Handling
- Modern `Handler` is now the single source of truth for exception rendering, reporting, and event dispatch
- Legacy `ExceptionHandler` reduced to a thin bootstrap shim (~250 lines) that delegates to the DI-managed Handler
- Channel-based log routing: exceptions automatically routed to named channels (`auth`, `database`, `security`, `http`, etc.)
- Optimized context building: lightweight context for high-frequency exceptions, full context for others

#### Boot Wiring Improvements
- Global error handlers registered earlier in the boot process (before Phase 1)
- Handler wired into the global shim after container build via `ExceptionHandler::setHandler()`

### Notes

After updating, run:

```bash
composer update glueful/framework
```

No breaking changes. The `ExceptionHandler` static API (`logError`, `setTestMode`, `getTestResponse`) continues to work unchanged.

---

## [1.12.0] - 2026-02-07 — Queue System Overhaul

Release aligning the skeleton with Glueful Framework 1.29.0 (Capella), featuring queue system improvements.

### Changed

- Bump framework dependency to `glueful/framework ^1.29.0`
- Queue config (`config/queue.php`) updated with seven env-driven queue presets and per-queue autoscale toggles
- Schedule config (`config/schedule.php`) uses env-backed queue names instead of hardcoded strings
- `.env.example` updated with queue process, autoscale, and per-queue env vars

### Framework Features Now Available

This release includes features from Glueful Framework 1.29.0:

#### Leaf Worker Mode
- New `queue:work process` action for direct in-process job execution
- Spawned workers no longer recursively invoke the queue manager
- Supports `--sleep`, `--max-jobs`, `--max-runtime`, `--stop-when-empty` and monitoring flags

#### Queue System Fixes
- ProcessManager config normalization and `stop()` API
- Worker status display now includes runtime
- Distributed lock is queue-scoped (not host-scoped) for correct multi-host coordination

### Notes

After updating, run:

```bash
composer update glueful/framework
```

Review your `.env` for new `QUEUE_*` and `SCHEDULE_QUEUE_*` variables.

---

## [1.11.3] - 2026-02-07 — CLI Fix

Release aligning the skeleton with Glueful Framework 1.28.3 (Bellatrix patch), fixing CLI option shortcut collision.

### Changed

- Bump framework dependency to `glueful/framework ^1.28.3`

### Framework Fixes Now Available

This release includes fixes from Glueful Framework 1.28.3:

#### CLI `-q` Shortcut Collision
- `queue:work`, `dev:server`, and `cache:maintenance` commands crashed with `LogicException: An option with shortcut "q" already exists`
- Caused by `--queue` option using `-q` shortcut, which conflicts with Symfony Console's built-in `--quiet`
- Fixed by removing the `-q` shortcut — use `--queue` instead

### Notes

After updating, run:

```bash
composer update glueful/framework
```

If your queue worker systemd service was crash-looping due to this error, it will now start correctly.

---

## [1.11.2] - 2026-02-07 — Extension Migrations

Release aligning the skeleton with Glueful Framework 1.28.2 (Bellatrix patch), fixing CLI migration discovery and PostgreSQL schema introspection.

### Changed

- Bump framework dependency to `glueful/framework ^1.28.2`

### Framework Fixes Now Available

This release includes fixes from Glueful Framework 1.28.2:

#### Container Self-Registration
- `ContainerFactory::create()` now registers the container under `ContainerInterface` for autowiring
- CLI commands receive the fully-configured container instead of creating a fresh one

#### Migration Command DI Wiring
- `migrate:run`, `migrate:status`, `migrate:rollback` now properly discover extension migrations
- Commands accept container/context via constructor DI, receiving migration paths registered by extensions

#### PostgreSQL Schema-Safe Introspection
- All `PostgreSQLSqlGenerator` introspection queries now use `current_schema()` instead of hardcoding `public`
- Covers table/column existence checks, schema queries, `getTableColumns()` with PK/unique/index/FK lookups
- Enables correct behavior in multi-tenant setups and non-`public` schema deployments

### Notes

After updating, run:

```bash
composer update glueful/framework
```

If your extensions register migrations via `loadMigrationsFrom()`, they will now appear in `migrate:status` and run with `migrate:run`.

---

## [1.11.1] - 2026-02-06 — Router Stability

Release aligning the skeleton with Glueful Framework 1.28.1 (Bellatrix patch), fixing route loading issues when using extensions with route caching.

### Changed

- Bump framework dependency to `glueful/framework ^1.28.1`

### Framework Fixes Now Available

This release includes stability fixes from Glueful Framework 1.28.1:

#### Router Group Stack Fix
- `Router::group()` now uses `try/finally` to always clean up route prefix stacks
- Prevents cascading route prefix leakage when exceptions occur inside group callbacks
- Eliminates incorrect path accumulation across extension route loading

#### Cache-Aware Route Registration
- Router allows extensions to overwrite routes pre-loaded from cache instead of throwing duplicate errors
- Dynamic routes replace cached entries instead of appending duplicates
- Ensures fresh extension route definitions always take priority

### Notes

After updating, run:

```bash
composer update glueful/framework
```

If you previously saw "Route already defined" errors in your error log with extensions enabled, this update resolves that issue. Consider clearing your route cache after updating:

```bash
./glueful route:cache:clear
```

---

## [1.11.0] - 2026-02-05 — Route Caching

Release aligning the skeleton with Glueful Framework 1.28.0 (Bellatrix), enabling route caching support.

### Changed

- Bump framework dependency to `glueful/framework ^1.28.0`

### Framework Features Now Available

This release enables access to features from Glueful Framework 1.28.0:

#### Route Caching Support (Bellatrix)
- **Cacheable routes**: Framework routes now use `[Controller::class, 'method']` syntax for cache compatibility
- **ResourceController refactoring**: Methods renamed to RESTful conventions (`index`, `show`, `store`, `update`, `destroy`)
- **Request-based parameters**: Controller methods accept `Request` directly instead of array parameters
- **Closure detection**: RouteCompiler validates handlers and warns about non-cacheable closures
- **Auto-invalidation**: RouteCache detects closures and invalidates cache automatically

#### Migration Considerations
If you extended `ResourceController` and overrode methods, update to new signatures:

```php
// Before
public function get(array $params, array $queryParams)

// After
public function index(Request $request): Response
{
    $table = $request->attributes->get('table', '');
    $queryParams = $request->query->all();
}
```

### Notes

After updating, run:

```bash
composer update glueful/framework
```

Use `./glueful route:debug` to identify any routes still using closure syntax.

---

## [1.10.1] - 2026-02-04 — Developer Experience

Release aligning the skeleton with Glueful Framework 1.27.0 (Avior), introducing new CLI commands, transaction callbacks, and route cache improvements.

### Changed

- Bump framework dependency to `glueful/framework ^1.27.0`

### Framework Features Now Available

This release enables access to features from Glueful Framework 1.27.0:

#### New CLI Commands (Avior)
- **`doctor`** — Quick health checks for local development (env, cache, database, routes, storage)
- **`env:sync`** — Sync `.env.example` from config `env()` usage with `--apply` option
- **`route:debug`** — Dump resolved routes with `--method`, `--path`, `--name` filters
- **`route:cache:clear`** / **`route:cache:status`** — Route cache management
- **`cache:inspect`** — Inspect cache driver and PHP extension status
- **`test:watch`** — Run tests on file changes with configurable polling
- **`dev:server`** — Development server alias

#### Database Transaction Callbacks
- **`Connection::afterCommit(callable)`** — Execute callback after transaction commits
- **`Connection::afterRollback(callable)`** — Execute callback after transaction rollback
- Shared `TransactionManager` ensures consistent state across QueryBuilders
- Use cases: search index updates, cache invalidation, event dispatching

#### Route Cache Improvements
- **Signature-based invalidation** replaces TTL-based caching
- SHA-256 hash of route file paths, mtimes, and sizes
- Cache invalidates automatically when any source file changes

#### Extensions Enable/Disable Commands
- Commands now edit `config/extensions.php` directly
- New `--dry-run` and `--backup` options
- DisableCommand comments out provider line instead of removing it

### Notes

After updating, run:

```bash
composer update glueful/framework
```

Try the new `doctor` command for quick health checks:

```bash
./glueful doctor
```

---

## [1.10.0] - 2026-01-31 — Extension Reliability

Release aligning the skeleton with Glueful Framework 1.26.0, improving extension discovery reliability for CLI tools and documentation generation.

### Changed

- Bump framework dependency to `glueful/framework ^1.26.0`

### Framework Features Now Available

This release enables access to fixes from Glueful Framework 1.26.0:

#### Extension Discovery Fixes (Atria)
- **Fallback discovery**: `PackageManifest` now falls back to `installed.json` when `installed.php` lacks provider metadata
- **Lazy auto-discovery**: CLI commands that create their own container automatically discover extensions
- **Discovery efficiency**: Added `$discovered` flag to ensure discovery runs exactly once

These fixes resolve edge cases where extension documentation wasn't being generated due to empty provider lists.

### Notes

After updating, run:

```bash
composer update glueful/framework
```

No migration required.

---

## [1.9.0] - 2026-01-31 — Route Domains

Release aligning the skeleton with Glueful Framework 1.25.0, enabling multi-file route organization for domain-driven development.

### Changed

- Bump framework dependency to `glueful/framework ^1.25.0`

### Framework Features Now Available

This release enables access to features from Glueful Framework 1.25.0:

#### Multi-File Route Discovery (Ankaa)
- **Automatic route discovery**: All `*.php` files in `routes/` are auto-discovered and loaded
- **Alphabetical loading**: Route files load in sorted order for deterministic behavior
- **Exclusion patterns**: Files starting with underscore (`_helpers.php`, `_shared.php`) excluded as partials
- **Double-load prevention**: Framework tracks loaded files to avoid duplicate registration

#### Domain-Driven Route Organization
Split large route files into domain-specific files:

```
routes/
├── api.php           # Main/shared routes
├── identity.php      # Auth, profile, preferences
├── social.php        # Follow, block
├── engagement.php    # Reactions, comments
└── _helpers.php      # Shared helpers (excluded)
```

Each file receives `$router` and `$context` in scope:

```php
// routes/identity.php
$router->group(['prefix' => api_prefix($context)], function (Router $router) {
    $router->post('/auth/login', [AuthController::class, 'login']);
    $router->get('/profile', [ProfileController::class, 'show']);
});
```

### Notes

After updating, run:

```bash
composer update glueful/framework
```

No migration required - single `routes/api.php` files continue to work as before.

---

## [1.8.0] - 2026-01-31 — Context Revolution

Major release aligning the skeleton with Glueful Framework 1.22.0, introducing ApplicationContext dependency injection, console command auto-discovery, and updated configuration patterns.

### Changed

- Bump framework dependency to `glueful/framework ^1.22.0`
- **config/app.php** — Updated to use `$basePath = dirname(__DIR__)` pattern instead of helper functions
- **config/documentation.php** — Updated to use `$root = dirname(__DIR__)` pattern
- **config/image.php** — Updated to use `$root` variable for all path configurations
- **config/logging.php** — Updated to use `$root` variable for path configurations
- **config/security.php** — Enhanced configuration:
  - Added `tokens.allow_query_param` option for legacy token support
  - Added `csrf.allow_missing_origin` option for non-browser clients
  - Added `csrf.skip_for_bearer_auth` option for API authentication
- **config/schedule.php** — Updated scheduled job configuration:
  - Handler classes updated to `Glueful\Queue\Jobs\*Job` namespace format
  - Replaced `persistence` property with `queue` property
  - Added `default_queue`, `queue_connection`, `use_queue_for_all_jobs` settings
  - Added `queue_mapping` section for queue-based job organization

### Framework Features Now Available

This release enables access to features from Glueful Framework 1.22.0:

#### ApplicationContext Dependency Injection
- **Explicit context parameter**: Helper functions now require `ApplicationContext` as first parameter
  - `config($context, $key, $default)` - Get configuration values
  - `app($context, $id)` - Resolve services from container
  - `base_path($context, $path)` - Get base path
  - `storage_path($context, $path)` - Get storage path
- **Improved testability**: No more global state, enabling proper unit testing
- **Multi-app support**: Multiple application instances can coexist

#### Console Command Auto-Discovery
- **Automatic registration**: Commands auto-discovered from `src/Console/Commands/`
- **Production caching**: Cached manifest for fast startup
- **New CLI command**: `php glueful commands:cache` for cache management
  - `--clear` to clear cache
  - `--status` to show cache info

#### PHP 8.3 Compatibility
- **QueueContextHolder**: New class replacing deprecated static trait properties
- Fixed static trait method/property deprecation warnings

### Migration

Update your application code to pass `ApplicationContext` to helper functions:

```php
// Before (no longer works)
$value = config('app.debug');

// After
$value = config($context, 'app.debug');

// Or if you have access to the container
$context = $container->get(ApplicationContext::class);
$value = config($context, 'app.debug');
```

After updating, run:

```bash
composer update glueful/framework
```

---

## [1.7.0] - 2026-01-24 — Flexible Infrastructure

Major release aligning the skeleton with Glueful Framework 1.21.0, introducing flexible URL routing patterns, comprehensive filesystem configuration, log retention settings, and enhanced application configuration with memory monitoring.

### Added

- **config/filesystem.php** — New comprehensive filesystem configuration:
  - Symfony Filesystem and Finder component settings
  - FileManager configuration (permissions, logging, path constraints)
  - FileFinder settings (depth, VCS ignore, symlink handling)
  - Security settings (path validation, upload scanning, path traversal prevention)
  - File uploader configuration (MIME types, thumbnail generation)
  - Extension discovery and route loading settings
  - Migration discovery and cache management

- **config/logging.php** — Log retention settings:
  - Per-channel retention configuration with environment variable support
  - Default: 90 days, debug: 7 days, API: 30 days
  - Long retention for compliance: auth/security/error: 365 days

- **config/app.php** — Enhanced application configuration:
  - `dev_mode` — Smart environment-aware development mode
  - `force_https` — Smart environment-aware HTTPS enforcement
  - `key` — Application encryption key reference
  - `versioning` — API versioning configuration block
  - `paths` — Comprehensive application paths (uploads, logs, cache, backups, migrations, etc.)
  - `performance.memory` — Memory monitoring with alerts, limits, and GC settings

- **config/api.php** — Flexible URL routing:
  - `apply_prefix_to_routes` — Toggle API prefix for subdomain deployments
  - `version_in_path` — Toggle version number in URL paths

- **config/documentation.php** — Documentation improvements:
  - Route file prefixes configuration for accurate path generation
  - `include_resource_routes` option to control CRUD endpoint generation
  - Additional excluded tables (notifications, notification_preferences, notification_templates)
  - `hide_powered_badge` Scalar UI option
  - Regeneration tips in comments

- **.env.example** — URL pattern documentation:
  - Pattern A: Dedicated subdomain (`api.example.com/v1/...`)
  - Pattern B: Path prefix (`example.com/api/v1/...`)
  - Pattern C: No versioning (`api.example.com/...`)
  - New variables: `API_USE_PREFIX`, `API_VERSION_IN_PATH`

### Changed

- Bump framework dependency to `glueful/framework ^1.21.0`
- Default queue connection changed from `sync` to `database`
- Documentation server URL simplified to use base URL directly
- **config/security.php** — Cleaned up and simplified:
  - Improved `health_ip_allowlist` handling (filters empty strings)
  - Removed legacy settings now handled by framework or config/api.php

### Removed

- **database/migrations/008_CreateAuditLogsTable.php** — Now handled by framework
- Legacy `rate_limiter` section from security.php (use config/api.php instead)
- Legacy `audit` section from security.php
- Legacy `enabled_permissions` setting
- Redundant rate limiting env variables from .env.example

### Framework Features Now Available

This release enables access to features from Glueful Framework 1.20.0 and 1.21.0:

#### v1.21.0 — File Uploader Refactoring
- **ThumbnailGenerator** — Dedicated thumbnail creation with Intervention Image
- **MediaMetadataExtractor** — Pure PHP metadata extraction using getID3
- **MediaMetadata** — Readonly value object for type-safe media metadata
- **FileUploader refactoring** — Cleaner separation of concerns
- Removed ffprobe dependency — No external binaries required

#### v1.20.0 — Filesystem Infrastructure
- **FileManager** — Symfony Filesystem wrapper with security checks
- **FileFinder** — Symfony Finder wrapper for file discovery
- Atomic file operations and path validation
- Extension and migration discovery improvements

### Migration

Update your `.env` file to use the new URL pattern variables:

```diff
+ # Choose your URL pattern
+ API_USE_PREFIX=true
+ API_PREFIX=/api
+ API_VERSION_IN_PATH=true
```

After updating, run:

```bash
composer update glueful/framework
```

---

## [1.6.1] - 2026-01-22 — Configuration Simplification

Patch release simplifying environment configuration by consolidating URL and version variables.

### Changed

- Bump framework dependency to `glueful/framework ^1.19.1`.
- **Simplified URL Configuration**: All URLs now derive from single `BASE_URL`
  - Removed `API_BASE_URL` from `.env.example`
- **Simplified Version Configuration**: Consolidated to single `API_VERSION`
  - Removed `API_VERSION_FULL` — docs version derived automatically
  - Removed `API_DEFAULT_VERSION` — use `API_VERSION` instead
  - Changed format from `API_VERSION=v1` to `API_VERSION=1` (integer)
- Updated `config/app.php`, `config/api.php`, `config/documentation.php` to use simplified variables

### Migration

Update your `.env` file:

```diff
- BASE_URL=http://localhost:8000
- API_BASE_URL=http://localhost:8000
- API_VERSION=v1
- API_VERSION_FULL=1.0.0
+ BASE_URL=http://localhost:8000
+ API_VERSION=1
```

---

## [1.6.0] - 2026-01-22 — API Essentials

Major release aligning the skeleton with Glueful Framework 1.19.0, bringing support for all Priority 3 API-specific features: API Versioning, Enhanced Rate Limiting, Webhooks System, and Search & Filtering DSL. This release completes the foundational API tooling needed for production-grade REST APIs.

### Added

- **config/api.php** — Comprehensive API configuration with four new sections:
  - `versioning` — API versioning with multiple strategies (URL prefix, header, query, Accept header)
  - `rate_limiting` — Tiered rate limiting with multiple algorithms (sliding, fixed, token bucket)
  - `webhooks` — Webhook delivery configuration with HMAC signatures and retry logic
  - `filtering` — Search & Filtering DSL configuration with operator controls

### Changed

- Bump framework dependency to `glueful/framework ^1.19.0`.

### Framework Features Now Available

This release enables access to all features from Glueful Framework 1.10.0 through 1.19.0:

#### v1.19.0 — Search & Filtering DSL (Canopus)
- **QueryFilter classes** for reusable filtering logic
- **14 filter operators**: eq, ne, gt, gte, lt, lte, contains, starts, ends, in, nin, between, null, not_null
- **Sorting**: Multi-column with direction (`sort=-created_at,name`)
- **Full-text search** with database LIKE or search engine integration
- **Search engine adapters**: DatabaseAdapter, ElasticsearchAdapter, MeilisearchAdapter
- **Searchable trait** for ORM models
- **scaffold:filter command** for generating filter classes

#### v1.18.0 — Webhooks System (Hadar)
- **Event-based subscriptions** with wildcard matching (`user.*`, `*`)
- **HMAC-SHA256 signatures** (Stripe-style format)
- **Reliable delivery** with exponential backoff retry (1m, 5m, 30m, 2h, 12h)
- **WebhookSubscription and WebhookDelivery** ORM models
- **REST API** for subscription management
- **CLI commands**: `webhook:list`, `webhook:test`, `webhook:retry`
- **Auto-migration** for database tables

#### v1.17.0 — Enhanced Rate Limiting (Alnitak)
- **Tiered rate limiting** (anonymous, free, pro, enterprise)
- **Multiple algorithms**: sliding window, fixed window, token bucket
- **Cost-based limiting** via `#[RateLimitCost]` attribute
- **IETF-compliant headers** (RateLimit-Limit, RateLimit-Remaining, RateLimit-Reset)
- **Route-level configuration** via `#[RateLimit]` attribute

#### v1.16.0 — API Versioning (Meissa)
- **Multiple strategies**: URL prefix (`/api/v1`), header, query param, Accept header
- **Deprecation system** with Sunset headers (RFC 8594)
- **Version negotiation** middleware
- **Route attributes**: `#[Version]`, `#[Deprecated]`, `#[Sunset]`

#### v1.15.0 — Real-Time Dev Server (Rigel)
- **File watching** with automatic reload
- **WebSocket support** for hot reloading
- **Enhanced serve command** with livereload

#### v1.14.0 — Interactive CLI Wizards (Bellatrix)
- **Interactive prompts** for scaffold commands
- **Model relationship detection**
- **Migration generation wizards**

#### v1.13.0 — Enhanced Scaffolding (Saiph)
- **scaffold:model** with fillable, migration, factory options
- **scaffold:controller** with resource actions
- **scaffold:job** with queue configuration
- **scaffold:rule** for validation rules
- **scaffold:test** for unit and feature tests
- **Database factories and seeders**

#### v1.12.0 — API Resource Transformers (Mintaka)
- **JsonResource and ModelResource** for JSON transformation
- **ResourceCollection** with pagination support
- **Conditional attributes**: `when()`, `whenLoaded()`, `mergeWhen()`

#### v1.11.0 — ORM / Active Record (Alnilam)
- **Model base class** with active record pattern
- **Relationships**: hasOne, hasMany, belongsTo, belongsToMany
- **Query scopes** and soft deletes
- **Attribute casting** and accessors/mutators

#### v1.10.0 — Exception Handler & Validation (Elnath)
- **Global exception handler** with JSON error responses
- **Request validation** with declarative rules
- **FormRequest classes** for complex validation

### Notes

After updating, run:

```bash
composer update glueful/framework
```

**New CLI commands available:**
```bash
# Webhooks
php glueful webhook:list
php glueful webhook:test https://example.com/webhook
php glueful webhook:retry --failed

# Scaffolding
php glueful scaffold:filter UserFilter --model=User
php glueful scaffold:model Post --fillable=title,body --migration
php glueful scaffold:resource UserResource --model
```

**Optional search engine packages:**
```bash
# For Elasticsearch support
composer require elasticsearch/elasticsearch:^8.0

# For Meilisearch support
composer require meilisearch/meilisearch-php:^1.0
```

---

## [1.5.2] - 2026-01-20 — Deneb Sync

Compatibility release aligning the skeleton with Glueful Framework 1.9.2.

### Changed
- Bump framework dependency to `glueful/framework ^1.9.2`.
  - OpenAPI 3.1.0 support with JSON Schema draft 2020-12 alignment
  - New `ResourceRouteExpander` automatically expands `{resource}` routes to table-specific endpoints
  - Output file renamed from `swagger.json` to `openapi.json`
  - Scalar UI improvements with `hideClientButton` and `showDeveloperTools` options
  - Tags sorted alphabetically in documentation sidebar
  - Fixed `SchemaBuilder::getTableColumns()` returning empty arrays
- Updated `config/documentation.php`:
  - Config key `paths.swagger` renamed to `paths.openapi`
  - Default OpenAPI version changed to 3.1.0
  - Added `hide_client_button` and `show_developer_tools` Scalar options
- Updated `config/app.php`:
  - Fixed docs URL to use `/docs/` instead of `/api/v1/docs/`

### Notes
- After updating, run:

```bash
composer update glueful/framework
```

- If you have custom scripts referencing `swagger.json`, update the path to `openapi.json`.

## [1.5.1] - 2026-01-19 — Castor Alignment

Compatibility release aligning the skeleton with Glueful Framework 1.9.1.

### Added
- `config/documentation.php` - Centralized OpenAPI/Swagger documentation settings including:
  - API info, servers, and security schemes
  - Output paths for generated documentation
  - UI configuration for Scalar, Swagger UI, and Redoc

### Changed
- Bump framework dependency to `glueful/framework ^1.9.1`.
  - New `--ui` option for `generate:openapi` command supporting Scalar, Swagger UI, and Redoc
  - Refactored documentation system with `OpenApiGenerator` and `DocumentationUIGenerator`
  - PHPDoc parsing now uses `phpDocumentor/ReflectionDocBlock` for robustness
  - New `Numeric` and `Regex` validation rules
  - Symfony packages updated to ^7.4
- Updated `.gitignore` to exclude `bootstrap/cache/` directory.

### Notes
- After updating, run:

```bash
composer update glueful/framework
```

- To generate API documentation with interactive UI:

```bash
php glueful generate:openapi --ui
php glueful generate:openapi --ui=swagger-ui
php glueful generate:openapi --ui=redoc
```

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
