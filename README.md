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
