# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.

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

#### Base64 Upload File Extensions
- Base64 uploads now produce files with the correct extension (e.g., `.png`, `.jpg`) derived from the MIME type, instead of always using `.bin`.

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
