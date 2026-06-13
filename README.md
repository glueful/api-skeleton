# Glueful API Skeleton

A minimal API application starter powered by the Glueful framework.

## Quick Start

```bash
# Install dependencies
composer install

# Initialize application (runs migrations, generates key)
php glueful install --quiet

# Start development server
php glueful serve

# Visit the API
curl http://127.0.0.1:8080/v1/welcome
curl http://127.0.0.1:8080/health
```

## Default Routes

| Route | Method | Description |
|-------|--------|-------------|
| `/v1/welcome` | GET | Welcome JSON payload |
| `/v1/status` | GET | Lightweight status check |
| `/health` | GET | Framework health endpoint |

## Identity, Accounts & RBAC

The skeleton enables two extensions by default — **`glueful/users`** (identity store + account lifecycle) and **`glueful/email-notification`** (the `email` channel). Authorization (RBAC) is **opt-in**.

**1. Default skeleton — no RBAC required**

- Login / token refresh / logout via the core auth seam (backed by `glueful/users`).
- `GET /me` — the authenticated user's account + nested profile (authentication only).
- Account lifecycle (`/auth/verify-email`, `/auth/forgot-password`, `/auth/reset-password`).
- Email-PIN 2FA (`/2fa/*`, when `TWO_FACTOR_ENABLED=true`).

**2. Optional user lookup / list — needs RBAC**

These are **off by default** and **permission-gated** (`users.read`):

```env
USERS_USER_LOOKUP_ENABLED=true   # GET /users/{uuid}
USERS_USER_LIST_ENABLED=true     # GET /users  (also requires the lookup flag)
```

Because they require `users.read`, they only work once an RBAC provider is enabled and the permission is granted — without one, the framework gate fails closed (`403`).

**3. Enabling RBAC (`glueful/aegis`)**

```bash
composer require glueful/aegis
php glueful extensions:enable aegis
php glueful migrate:run                                    # RBAC tables + seeds default roles
php glueful aegis:bootstrap-admin --user=<uuid-or-email>   # syncs the catalog, grants users.read, assigns the role
```

`aegis:bootstrap-admin` is the one-command first-admin path: it syncs the declared permission catalog, creates/reuses a role (default `admin`), grants it `users.read`, and assigns that role to your user — enough to unlock the lookup/list endpoints. Useful flags:

- `--role=administrator` — target a specific (e.g. seeded) role instead of `admin`.
- `--permission=posts.read` — repeatable; grant specific permissions instead of the `users.read` default.
- `--all-catalog` — grant **every** catalog permission (full admin).
- `--dry-run` — preview without writing.

> Manual equivalent (if you prefer): `php glueful permissions:sync`, then assign a seeded role (`superuser`/`administrator`) to the user via the roles API (`POST /{user_uuid}/roles`).

> Modern-framework norm: the user store is a sensible default; full RBAC stays opt-in so a fresh skeleton boots lean and secure (fail-closed) without imposing roles/permissions setup until you need it.

## Project Structure

```
api-skeleton/
├── app/                    # Application code
│   ├── Controllers/        # HTTP request handlers
│   ├── Models/             # ORM models
│   └── Providers/          # Service providers
├── bootstrap/app.php       # Framework initialization
├── config/                 # Configuration files
├── database/migrations/    # Database migrations
├── public/index.php        # HTTP entry point
├── routes/api.php          # API routes
├── storage/                # Runtime data (logs, cache, db)
└── tests/                  # PHPUnit tests
```

This skeleton uses a **minimal starter structure**. As your application grows, see [docs/APPLICATION_ARCHITECTURE.md](docs/APPLICATION_ARCHITECTURE.md) for guidance on scaling to standard and enterprise structures.

## Architecture Guide

The skeleton follows a progressive complexity model:

| Project Size | Structure |
|--------------|-----------|
| **Starter** (< 10 endpoints) | Controllers, Models, Providers |
| **Standard** (10-50 endpoints) | + Actions, DTO, Events, Jobs, Policies |
| **Enterprise** (50+ endpoints) | + Repositories, Services, Validators |

**Start minimal. Add complexity only when needed.**

See the full guide: [docs/APPLICATION_ARCHITECTURE.md](docs/APPLICATION_ARCHITECTURE.md)

## Controllers & Routing

Routes are defined explicitly in `routes/api.php`:

```php
// Simple version prefix (customize as needed)
$router->group(['prefix' => 'v1'], function (Router $router) {
    $router->get('/welcome', [WelcomeController::class, 'index']);
    $router->get('/status', [WelcomeController::class, 'status']);
});

// Other prefix options:
// - 'v1'                 → /v1/...
// - '/api/v1'            → /api/v1/...
// - api_prefix($context) → uses config/api.php settings
```

The framework also supports attribute-based routing:

```php
#[Controller(prefix: '/api/v1')]
class UserController extends BaseController
{
    #[Get('/users/{id}')]
    public function show(int $id): Response { }
}
```

## Configuration

Key configuration files in `config/`:

| File | Purpose |
|------|---------|
| `app.php` | Application settings, paths, URLs |
| `database.php` | Database connections (SQLite default) |
| `security.php` | CORS, CSRF, headers, rate limiting |
| `api.php` | API versioning, field selection |

Environment variables in `.env` override config values.

## CLI Commands

```bash
# Development
php glueful serve                    # Start dev server
php glueful serve --watch            # Auto-restart on changes

# Database
php glueful migrate:run              # Run migrations
php glueful migrate:status           # Check migration status

# Code Generation
php glueful scaffold:controller UserController
php glueful scaffold:model User --migration
php glueful scaffold:request CreateUserRequest

# Utilities
php glueful generate:key             # Generate APP_KEY
php glueful cache:clear              # Clear cache
php glueful generate:openapi         # Generate API docs
```

## Deploying To Production

Before deploying a generated app, review this checklist:

```env
APP_ENV=production
APP_DEBUG=false
FORCE_HTTPS=true
```

- Generate strong secrets with `php glueful generate:key`, then set `APP_KEY` and `JWT_KEY` in the production environment.
- Move from SQLite to your production database driver and run `php glueful migrate:run` during deployment.
- Move `QUEUE_CONNECTION` away from `sync` for background work, usually to `database`, `redis`, or your queue driver.
- Move `CACHE_DRIVER` away from local file storage when the app runs on more than one server.
- Generate the production command manifest with `php glueful commands:cache`.
- Check route-cache health with `php glueful route:cache:status`; clear stale routes with `php glueful route:cache:clear`.
- Clear application cache with `php glueful cache:clear` after config or deployment changes.
- Enable PHP opcache in production and deploy with Composer's optimized autoloader.
- Point logs at `storage/logs` or your platform log sink, never under `public/`.
- Keep `/docs` disabled unless API docs should be public in that environment.

## Testing

```bash
# Run all tests
composer test

# Run specific suites
composer test:unit
composer test:integration
```

Base test case at `tests/TestCase.php` provides framework integration.

## Notes

- **Database**: SQLite at `storage/database/glueful.sqlite` (zero config)
- **Queue**: `sync` driver for immediate execution (change to `redis` or `database` in `.env`)
- **Docs**: API documentation at `/docs` when `API_DOCS_ENABLED=true`
