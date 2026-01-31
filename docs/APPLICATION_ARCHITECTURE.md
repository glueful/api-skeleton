# Application Architecture Guide

This guide describes the recommended application structure for Glueful projects, from simple APIs to complex enterprise applications.

## Philosophy

1. **Start minimal** — Add complexity only when needed
2. **No framework in domain** — Keep business logic portable
3. **Context-aware DI** — Use `ApplicationContext` injection, no statics
4. **Single responsibility** — Each class does one thing well

---

## Starter Structure (< 10 endpoints)

For small APIs, microservices, or prototypes:

```
app/
├── Controllers/          # HTTP request handling
├── Models/               # ORM models with business logic
└── Providers/            # Service registration
    └── AppServiceProvider.php
```

**When to use:** MVPs, internal tools, simple CRUD APIs, webhooks.

**Model philosophy (Starter):** In small apps, it's practical to keep business logic in models. Methods like `$user->activate()` or `$order->calculateTotal()` are fine here. As your app grows, you'll extract this logic to Actions/Services.

### Example: Simple User API

```php
// app/Controllers/UserController.php
class UserController extends BaseController
{
    public function index(): Response
    {
        $users = User::query()->paginate();
        return $this->success($users);
    }

    public function store(Request $request): Response
    {
        // Use controller's validate() method for simple cases
        $validated = $this->validate($request, [
            'email' => 'required|email|unique:users',
            'name' => 'required|string|max:255',
        ]);

        $user = User::create($validated);
        return $this->success($user, 201);
    }
}

// app/Models/User.php
class User extends Model
{
    protected array $fillable = ['email', 'name', 'password'];
    protected array $hidden = ['password'];

    // In Starter tier, simple business logic can live in models
    public function activate(): void
    {
        $this->update(['status' => 'active', 'activated_at' => now()]);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
```

---

## Standard Structure (10-50 endpoints)

For production APIs with moderate complexity:

```
app/
├── Actions/              # Single-purpose use cases (primary orchestrators)
├── Controllers/          # Thin HTTP layer
├── DTO/                  # Request validation + response shapes
├── Events/               # Domain events
├── Exceptions/           # Application exceptions
├── Jobs/                 # Async/queued work
├── Middleware/           # App-specific middleware
├── Models/               # ORM models (thinner than Starter)
├── Policies/             # Authorization rules
├── Providers/            # Service registration
└── Support/              # Helpers, value objects
```

**When to upgrade:**
- Controllers exceed ~100 lines
- Same logic appears in multiple places
- You need async processing (Jobs)
- Authorization becomes complex (Policies)
- You want typed request/response objects (DTO)

**Model philosophy (Standard):** Extract business logic from models to Actions. Models become thinner, focused on persistence concerns (relationships, scopes, casts). Actions orchestrate use cases.

### Layer Responsibilities

```
┌─────────────────────────────────────────────────────────────┐
│                     INTERFACE LAYER                          │
│   Controllers, Middleware, DTO (requests/responses)          │
│   - Receives HTTP requests                                   │
│   - Validates input                                          │
│   - Calls Actions                                            │
│   - Returns responses                                        │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    APPLICATION LAYER                         │
│   Actions, Jobs, Events, Policies                            │
│   - Orchestrates use cases                                   │
│   - Coordinates domain objects                               │
│   - Handles authorization                                    │
│   - Dispatches events/jobs                                   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      DOMAIN LAYER                            │
│   Models, Support (value objects)                            │
│   - Data persistence and relationships                       │
│   - No framework dependencies in Support/                    │
│   - Pure PHP objects where possible                          │
└─────────────────────────────────────────────────────────────┘
```

### Key Concepts

#### Actions vs Services

**Actions** are the primary pattern for use-case orchestration:
- One public method: `execute()`
- Named after what they do: `CreateUserAction`, `ProcessPaymentAction`
- Coordinate multiple domain objects and side effects

**Services** (added in Enterprise tier) are for pure domain logic shared across multiple Actions:
- Stateless, framework-free business rules
- Example: `PricingService::calculateDiscount()` used by multiple Actions
- Only add when you have shared logic; Actions are sufficient for most apps

#### Validation: DTO Pattern

Place validation in `DTO/` as request classes. This is the recommended default:

```php
// app/DTO/CreateUserData.php
class CreateUserData
{
    public function __construct(
        public readonly string $email,
        public readonly string $name,
        public readonly string $password,
    ) {}

    public static function rules(): array
    {
        return [
            'email' => 'required|email|unique:users',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            name: $data['name'],
            password: $data['password'],
        );
    }
}
```

### Example: User Registration Flow (Context-Aware)

```php
// app/DTO/CreateUserData.php
class CreateUserData
{
    public function __construct(
        public readonly string $email,
        public readonly string $name,
        public readonly string $password,
    ) {}

    public static function rules(): array
    {
        return [
            'email' => 'required|email|unique:users',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            name: $data['name'],
            password: $data['password'],
        );
    }
}

// app/Actions/CreateUserAction.php
use Glueful\Bootstrap\ApplicationContext;
use Glueful\Events\EventService;
use Glueful\Queue\QueueManager;

class CreateUserAction
{
    // Preferred: Inject services directly via constructor
    // Alternative: Use app($this->context, ServiceClass::class) for convenience
    public function __construct(
        private readonly ApplicationContext $context,
        private readonly EventService $events,
        private readonly QueueManager $queue,
    ) {}

    public function execute(CreateUserData $data): User
    {
        // Business logic extracted from controller
        $user = User::create([
            'email' => $data->email,
            'name' => $data->name,
            'password' => password_hash($data->password, PASSWORD_DEFAULT),
            'status' => 'pending',
        ]);

        // Dispatch event via injected EventService
        $this->events->dispatch(new UserCreatedEvent($user));

        // Queue welcome email via injected QueueManager
        $this->queue->push(SendWelcomeEmailJob::class, ['user_id' => $user->id]);

        return $user;
    }
}

// app/Controllers/UserController.php
class UserController extends BaseController
{
    public function __construct(
        private readonly CreateUserAction $createUser
    ) {}

    public function store(Request $request): Response
    {
        // Validate using DTO rules
        $validated = $this->validate($request, CreateUserData::rules());

        // Create typed DTO from validated data (not raw request)
        $data = CreateUserData::fromArray($validated);

        // Execute action
        $user = $this->createUser->execute($data);

        return $this->success(UserResource::make($user), 201);
    }
}

// app/Events/UserCreatedEvent.php
use Glueful\Events\BaseEvent;

class UserCreatedEvent extends BaseEvent
{
    public function __construct(
        public readonly User $user
    ) {
        parent::__construct();
    }
}

// app/Jobs/SendWelcomeEmailJob.php
use Glueful\Queue\Job;
use Glueful\Bootstrap\ApplicationContext;

class SendWelcomeEmailJob extends Job
{
    protected ?ApplicationContext $context;

    public function __construct(array $data = [], ?ApplicationContext $context = null)
    {
        parent::__construct($data);
        $this->context = $context;
    }

    public function handle(): void
    {
        $data = $this->getData();
        $user = User::find($data['user_id']);

        // Use context for any framework services
        if ($this->context !== null) {
            $mailer = app($this->context, MailerInterface::class);
            $mailer->send($user->email, 'welcome', ['user' => $user]);
        }
    }
}
```

---

## Enterprise Structure (50+ endpoints)

For large applications with complex domains:

```
app/
├── Actions/              # Use case orchestration
├── Controllers/          # HTTP layer
├── DTO/
│   ├── Requests/         # Incoming data shapes
│   └── Responses/        # Outgoing data shapes (Resources)
├── Events/
│   └── Listeners/        # Event handlers
├── Exceptions/
│   ├── Domain/           # Business rule violations
│   └── Application/      # Application-level errors
├── Jobs/                 # Async processing
├── Middleware/           # Request/response middleware
├── Models/               # ORM models (thin - persistence only)
├── Policies/             # Authorization
├── Providers/            # DI configuration
├── Repositories/         # Data access abstraction
├── Services/             # Domain services (shared business logic)
├── Support/
│   ├── Constants/        # Application constants
│   ├── Helpers/          # Utility functions
│   └── ValueObjects/     # Immutable domain objects
└── Validators/           # Complex, reusable validation rules
```

**When to upgrade:**
- Multiple data sources (API + DB + cache)
- Complex domain logic that spans multiple models
- Need to swap persistence implementations
- Multiple teams working on same codebase
- Strict testing requirements (mock repositories)

**Model philosophy (Enterprise):** Models are thin — only persistence concerns (relationships, scopes, attribute casting). All business logic lives in Actions (orchestration) and Services (shared domain logic). Repositories abstract data access for testability.

### When to Add Each Component

| Component | Add When... |
|-----------|-------------|
| **Repositories/** | You need to abstract data access, swap implementations, or have complex queries that should be testable in isolation |
| **Services/** | Business logic is shared by multiple Actions and is framework-free (e.g., `PricingService`, `TaxCalculator`) |
| **Validators/** | Validation rules are complex and reused across multiple DTOs (e.g., `UniqueEmailRule`, `PhoneNumberRule`) |
| **DTO/Responses/** | You need versioned or heavily transformed API responses |
| **ValueObjects/** | You have domain concepts that deserve their own type (Money, Email, DateRange, Address) |

### Repository Example

```php
// app/Repositories/Contracts/UserRepositoryInterface.php
interface UserRepositoryInterface
{
    public function findById(string $id): ?User;
    public function findByEmail(string $email): ?User;
    public function save(User $user): void;
    public function delete(User $user): void;
    public function activeUsers(): Collection;
}

// app/Repositories/EloquentUserRepository.php
class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(string $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function activeUsers(): Collection
    {
        return User::query()
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function save(User $user): void
    {
        $user->save();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}

// app/Providers/RepositoryServiceProvider.php
class RepositoryServiceProvider extends ServiceProvider
{
    public static function services(): array
    {
        return [
            UserRepositoryInterface::class => [
                'class' => EloquentUserRepository::class,
                'shared' => true,
            ],
        ];
    }
}
```

### Service Example (Domain Logic)

```php
// app/Services/PricingService.php
// Pure domain logic, no framework dependencies
class PricingService
{
    public function calculateOrderTotal(array $items, ?string $couponCode = null): Money
    {
        $subtotal = $this->calculateSubtotal($items);
        $discount = $this->calculateDiscount($subtotal, $couponCode);
        $tax = $this->calculateTax($subtotal->subtract($discount));

        return $subtotal->subtract($discount)->add($tax);
    }

    private function calculateSubtotal(array $items): Money
    {
        return array_reduce(
            $items,
            fn(Money $total, array $item) => $total->add(
                Money::of($item['price'])->multiply($item['quantity'])
            ),
            Money::zero()
        );
    }

    // ...
}

// Used by multiple Actions:
// - CreateOrderAction
// - UpdateOrderAction
// - PreviewOrderAction
```

---

## Model Philosophy Progression

This is an **intentional progression** as your app grows:

| Tier | Model Responsibility | Business Logic Location |
|------|---------------------|------------------------|
| **Starter** | Persistence + simple business logic | In models (`$user->activate()`) |
| **Standard** | Persistence + relationships + scopes | Actions orchestrate, models are thinner |
| **Enterprise** | Persistence only (thin) | Actions orchestrate, Services for shared logic |

**Why this progression?**
- Starter: Fast iteration, fewer files, acceptable coupling
- Standard: Better testability, clearer use cases, manageable complexity
- Enterprise: Maximum testability, team scalability, swappable implementations

---

## Naming Conventions

### Actions
Name after the use case in verb form:
```
CreateUserAction
UpdateUserProfileAction
ProcessPaymentAction
SendPasswordResetAction
```

### Jobs
Name after the work being done:
```
SendWelcomeEmailJob
ProcessOrderJob
GenerateReportJob
SyncInventoryJob
```

### Events
Name after what happened (past tense):
```
UserCreatedEvent
OrderShippedEvent
PaymentFailedEvent
PasswordResetRequestedEvent
```

### DTOs
Name after the data they represent:
```
CreateUserData
UpdateProfileData
OrderSummary
PaginatedUsers
```

### Services (Enterprise)
Name after the domain capability:
```
PricingService
TaxCalculationService
InventoryService
NotificationService
```

### Policies
Name after the model they protect:
```
UserPolicy
OrderPolicy
CommentPolicy
```

---

## Directory Creation Commands

### Upgrade from Starter to Standard
```bash
mkdir -p app/{Actions,DTO,Events,Exceptions,Jobs,Middleware,Policies,Support}
```

### Upgrade from Standard to Enterprise
```bash
mkdir -p app/DTO/{Requests,Responses}
mkdir -p app/Events/Listeners
mkdir -p app/Exceptions/{Domain,Application}
mkdir -p app/Support/{Constants,Helpers,ValueObjects}
mkdir -p app/{Repositories,Services,Validators}
```

---

## Anti-Patterns to Avoid

### 1. Fat Controllers
```php
// BAD: Controller doing too much
class UserController extends BaseController
{
    public function store(Request $request): Response
    {
        // Validation, business logic, email sending, event dispatch...
        // 200 lines of code
    }
}

// GOOD: Controller delegates to Action
class UserController extends BaseController
{
    public function __construct(
        private readonly CreateUserAction $createUser
    ) {}

    public function store(Request $request): Response
    {
        $validated = $this->validate($request, CreateUserData::rules());
        $user = $this->createUser->execute(CreateUserData::fromRequest($request));
        return $this->success(UserResource::make($user), 201);
    }
}
```

### 2. Framework Types in Domain

```php
// BAD: Domain depends on framework
class OrderService
{
    public function calculateTotal(Request $request): Response  // Framework types!
    {
        // ...
    }
}

// GOOD: Domain uses plain PHP
class OrderService
{
    public function calculateTotal(array $items): Money  // Plain PHP
    {
        // ...
    }
}
```

### 3. Missing Context in Actions

```php
// BAD: Global functions without context (hard to test)
class CreateUserAction
{
    public function execute(array $data): void
    {
        $setting = config('app.setting');           // Missing context!
        // Static facade that doesn't exist in Glueful
    }
}

// GOOD: Inject dependencies directly (preferred)
use Glueful\Bootstrap\ApplicationContext;
use Glueful\Events\EventService;
use Glueful\Queue\QueueManager;

class CreateUserAction
{
    public function __construct(
        private readonly ApplicationContext $context,
        private readonly EventService $events,
        private readonly QueueManager $queue,
    ) {}

    public function execute(array $data): void
    {
        // Config still needs context
        $setting = config($this->context, 'app.setting');

        // Injected services - clean and testable
        $this->events->dispatch(new UserCreated($user));
        $this->queue->push(SendEmailJob::class, ['user_id' => $user->id]);
    }
}
```

### 4. Premature Abstraction

```php
// BAD: Repository for simple CRUD in a small app
interface UserRepositoryInterface { /* 10 methods */ }
class EloquentUserRepository implements UserRepositoryInterface { /* 200 lines */ }

// When all you need (in Starter tier) is:
User::find($id);
User::create($data);
```

---

## Quick Reference

| Project Size | Folders | Key Patterns |
|--------------|---------|--------------|
| **Starter** | 3 | Models with business logic, controller validation |
| **Standard** | 11 | Actions for use cases, DTOs for validation, Events for side effects |
| **Enterprise** | 15+ | Repositories for data access, Services for shared domain logic |

**Rule of thumb:** If you're unsure whether to add a folder, don't. Add it when you feel the pain of not having it.
