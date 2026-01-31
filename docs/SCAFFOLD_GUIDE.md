# Scaffold Command Guide

Quick reference for generating application components using the Glueful CLI.

## Controllers

```bash
# Basic controller
php glueful scaffold:controller UserController

# Resource controller (index, show, store, update, destroy)
php glueful scaffold:controller UserController --resource

# API controller (resource without create/edit views)
php glueful scaffold:controller UserController --api
```

### Attribute-Based Routing

Instead of defining routes in `routes/api.php`, you can use PHP attributes directly on controller classes and methods:

```php
<?php

namespace App\Controllers;

use Glueful\Controllers\BaseController;
use Glueful\Routing\Attributes\{Controller, Get, Post, Put, Delete, Middleware};
use Glueful\Http\Response;

#[Controller(prefix: '/users', middleware: ['auth'])]
class UserController extends BaseController
{
    #[Get('/')]
    public function index(): Response
    {
        return $this->success(User::all());
    }

    #[Get('/{id}', where: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        return $this->success(User::find($id));
    }

    #[Post('/')]
    public function store(Request $request): Response
    {
        $user = User::create($request->request->all());
        return $this->success($user, 201);
    }

    #[Put('/{id}')]
    public function update(Request $request, int $id): Response
    {
        $user = User::find($id);
        $user->update($request->request->all());
        return $this->success($user);
    }

    #[Delete('/{id}')]
    #[Middleware(['admin'])]  // Method-level middleware
    public function destroy(int $id): Response
    {
        User::find($id)->delete();
        return $this->success(null, 204);
    }
}
```

**Available Attributes:**

| Attribute | Target | Parameters | Example |
|-----------|--------|------------|---------|
| `#[Controller]` | Class | `prefix`, `middleware[]` | `#[Controller(prefix: '/api/users')]` |
| `#[Get]` | Method | `path`, `name`, `where[]` | `#[Get('/{id}', where: ['id' => '\d+'])]` |
| `#[Post]` | Method | `path`, `name`, `where[]` | `#[Post('/')]` |
| `#[Put]` | Method | `path`, `name`, `where[]` | `#[Put('/{id}')]` |
| `#[Delete]` | Method | `path`, `name`, `where[]` | `#[Delete('/{id}')]` |
| `#[Middleware]` | Class/Method | `middleware[]` | `#[Middleware(['auth', 'throttle'])]` |
| `#[Fields]` | Method | `allowed[]`, `strict` | `#[Fields(allowed: ['id', 'name'])]` |

**Enabling Attribute Scanning:**

Register the `AttributeRouteLoader` in your service provider to scan controllers:

```php
// app/Providers/AppServiceProvider.php
use Glueful\Routing\AttributeRouteLoader;
use Glueful\Routing\Router;

public function boot(ApplicationContext $context): void
{
    $router = $this->app->get(Router::class);
    $loader = new AttributeRouteLoader($router);

    // Scan your controllers directory
    $loader->scanDirectory(base_path($context, 'app/Controllers'));
}
```

> **Note:** File-based routing in `routes/api.php` and attribute-based routing can be used together. Use whichever approach fits your preference.

## Models

```bash
# Basic model
php glueful scaffold:model User

# Model with migration
php glueful scaffold:model User --migration

# Model with fillable fields
php glueful scaffold:model User --fillable=name,email,password

# Model with soft deletes
php glueful scaffold:model User --soft-deletes

# Full example
php glueful scaffold:model User --migration --fillable=name,email --soft-deletes
```

## Requests (FormRequest Validation)

Generate `FormRequest` classes for declarative request validation:

```bash
# Basic form request
php glueful scaffold:request CreateUserRequest

# Update request
php glueful scaffold:request UpdateUserRequest
```

> **Note:** For simpler cases, you can also use plain DTO classes with static `rules()` methods
> as shown in [APPLICATION_ARCHITECTURE.md](APPLICATION_ARCHITECTURE.md#validation-dto-pattern).

## Resources (API Transformers)

```bash
# Basic JSON resource
php glueful scaffold:resource UserResource

# Model-aware resource
php glueful scaffold:resource UserResource --model

# Resource collection
php glueful scaffold:resource UserCollection --collection
```

## Jobs (Queue)

```bash
# Basic job
php glueful scaffold:job ProcessPayment

# Job with queue name
php glueful scaffold:job SendNewsletter --queue=emails

# Job with retry settings
php glueful scaffold:job GenerateReport --tries=3 --backoff=60

# Unique job (prevents duplicates)
php glueful scaffold:job SyncInventory --unique
```

## Events & Listeners

```bash
# Create event
php glueful event:create UserRegistered

# Create event in subdirectory
php glueful event:create Auth/LoginFailed

# Create event with category
php glueful event:create SecurityAlert --type=security

# Create listener
php glueful event:listener SendWelcomeEmail

# Listener for specific event
php glueful event:listener SendWelcomeEmail --event=App\\Events\\UserRegisteredEvent

# Listener with custom method
php glueful event:listener AuditLogger --method=onUserAction
```

## Middleware

```bash
# Basic middleware
php glueful scaffold:middleware RateLimitMiddleware

# Middleware in subdirectory
php glueful scaffold:middleware Admin/AuthMiddleware
```

## Validation Rules

```bash
# Basic rule
php glueful scaffold:rule UniqueEmail

# Rule with parameters
php glueful scaffold:rule PasswordStrength --params=minLength,requireNumbers

# Implicit rule (runs even when field is empty)
php glueful scaffold:rule RequiredWithoutField --implicit
```

## Tests

```bash
# Unit test
php glueful scaffold:test UserServiceTest

# Feature/integration test
php glueful scaffold:test UserApiTest --feature

# Test with specific methods
php glueful scaffold:test PaymentTest --methods=testCharge,testRefund

# Test for specific class
php glueful scaffold:test UserServiceTest --class=App\\Services\\UserService
```

## Factories & Seeders

```bash
# Factory for model
php glueful scaffold:factory UserFactory

# Database seeder
php glueful scaffold:seeder UserSeeder
```

## Migrations

```bash
# Create migration
php glueful migrate:create create_users_table

# Run migrations
php glueful migrate:run

# Rollback last migration
php glueful migrate:rollback

# Check status
php glueful migrate:status
```

---

## Growing Your Application

### From Starter to Standard

When your controllers start getting large, add these directories:

```bash
# Create standard structure directories
mkdir -p app/{Actions,DTO,Events,Exceptions,Jobs,Middleware,Policies,Support}
```

Then generate components:

```bash
# Extract controller logic to actions
# (create manually or use this pattern)
cat > app/Actions/CreateUserAction.php << 'EOF'
<?php

namespace App\Actions;

use App\Models\User;
use Glueful\Bootstrap\ApplicationContext;

class CreateUserAction
{
    public function __construct(
        private readonly ApplicationContext $context
    ) {}

    public function execute(array $data): User
    {
        return User::create($data);
    }
}
EOF
```

### From Standard to Enterprise

```bash
# Create enterprise structure
mkdir -p app/DTO/{Requests,Responses}
mkdir -p app/Events/{Dispatchers,Listeners}
mkdir -p app/Exceptions/{Domain,Application}
mkdir -p app/Support/{Constants,Helpers,ValueObjects}
mkdir -p app/{Repositories,Services,Validators}
```

---

## Common Patterns

### CRUD Endpoint Setup

```bash
# 1. Create model with migration
php glueful scaffold:model Post --migration --fillable=title,body,user_id

# 2. Create resource controller
php glueful scaffold:controller PostController --api

# 3. Create form requests
php glueful scaffold:request CreatePostRequest
php glueful scaffold:request UpdatePostRequest

# 4. Create API resource
php glueful scaffold:resource PostResource --model

# 5. Run migration
php glueful migrate:run
```

### Background Job Setup

```bash
# 1. Create the job
php glueful scaffold:job SendWelcomeEmail --queue=emails --tries=3

# 2. Dispatch from controller or action:
# app($this->context, QueueManager::class)->push(SendWelcomeEmail::class, ['user_id' => $userId]);

# 3. Run queue worker
php glueful queue:work --queue=emails
```

### Event-Driven Feature

```bash
# 1. Create event
php glueful event:create UserCreated

# Create event in subdirectory (Auth/LoginFailedEvent)
php glueful event:create Auth/LoginFailed

# Create event with category type
php glueful event:create SecurityAlert --type=security

# 2. Create listener
php glueful event:listener SendWelcomeEmail

# Create listener for specific event
php glueful event:listener SendWelcomeEmail --event=App\\Events\\UserCreatedEvent

# Create listener with custom method name
php glueful event:listener AuditLogger --method=onUserCreated
```

**3. Register in `config/events.php`:**

```php
// config/events.php
return [
    'listeners' => [
        \App\Events\UserCreatedEvent::class => [
            \App\Events\Listeners\SendWelcomeEmailListener::class,
        ],
    ],
];
```

> Events are auto-wired at boot time based on this config. Listeners receive the event via their handler method (default: `handle()`).

---

## Tips

1. **Use `--help`** on any command to see all options:
   ```bash
   php glueful scaffold:model --help
   ```

2. **Check available commands**:
   ```bash
   php glueful list scaffold   # Scaffold commands
   php glueful list event      # Event commands
   ```

3. **Dry run** isn't available, but you can preview by examining the command's template logic or checking generated files before committing.

4. **Naming conventions**:
   - Controllers: `UserController` (singular + Controller)
   - Models: `User` (singular, PascalCase)
   - Migrations: `create_users_table` (snake_case, plural table)
   - Jobs: `SendWelcomeEmail` (verb + noun)
   - Events: `UserCreated` (noun + past tense verb)
   - Listeners: `SendWelcomeEmailListener` (action + Listener)
