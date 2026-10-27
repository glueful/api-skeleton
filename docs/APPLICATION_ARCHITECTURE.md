# Application Architecture Guide

This guide describes how to grow a Glueful app from a handful of endpoints to a large codebase. Every example targets framework **1.91** and this skeleton's layout (`App\` → `app/`, routes in `routes/*.php`, controllers registered in `App\Providers\AppServiceProvider::services()`).

> Glueful is **not** Laravel. There are no facades and no context-less statics: models, helpers and the container all take an `ApplicationContext` first — `Post::query($context)`, `Post::find($context, $id)`, `Post::create($context, [...])`, `db($context)`, `app($context, X::class)`, `config($context, 'key')`. In a controller the context is `$this->context`; in a job it is `$this->context`; everywhere else, inject `ApplicationContext`.

## Philosophy

1. **Start minimal** — add folders only when you feel the pain of not having them
2. **Context-first, no statics** — pass or inject `ApplicationContext`; prefer constructor injection
3. **Typed input** — request DTOs validate before your code runs
4. **Single responsibility** — each class does one thing

---

## Starter Structure (< 10 endpoints)

For small APIs, microservices and prototypes:

```
app/
├── Controllers/          # HTTP handlers (extend Glueful\Controllers\BaseController)
├── DTOs/                 # Typed request DTOs (php glueful scaffold:dto)
├── Models/               # ORM models
└── Providers/
    └── AppServiceProvider.php   # registers controllers/services
routes/
└── api.php               # every routes/*.php file is loaded automatically
```

**Model philosophy (Starter):** small business methods on models (`$post->publish()`) are fine. Extract them to Actions as the app grows.

### Example: a Post API

```php
// app/Models/Post.php
namespace App\Models;

use Glueful\Database\ORM\Model;

class Post extends Model
{
    protected string $table = 'posts';
    protected array $fillable = ['title', 'slug', 'body', 'status', 'published_at'];
    protected array $casts = ['published_at' => 'datetime'];

    public function publish(): void
    {
        // update() = fill + save; DateTimeInterface values are formatted on save
        $this->update(['status' => 'published', 'published_at' => new \DateTimeImmutable()]);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
```

```php
// app/DTOs/CreatePostData.php — the router validates and hydrates this before store() runs
namespace App\DTOs;

use Glueful\Validation\Attributes\Rule;
use Glueful\Validation\Contracts\RequestData;

final class CreatePostData implements RequestData
{
    public function __construct(
        #[Rule('required|string|max:255')]
        public readonly string $title,

        #[Rule('required|string|max:255|unique:posts,slug')]
        public readonly string $slug,

        #[Rule('required|string')]
        public readonly string $body,
    ) {}
}
```

```php
// app/Controllers/PostController.php
namespace App\Controllers;

use App\DTOs\CreatePostData;
use App\Models\Post;
use Glueful\Controllers\BaseController;
use Glueful\Http\Response;
use Symfony\Component\HttpFoundation\Request;

class PostController extends BaseController
{
    public function index(Request $request): Response
    {
        $page = Post::query($this->context)
            ->where('status', 'published')
            ->latest()
            ->paginate((int) $request->query->get('page', 1), 20);

        // $page['data'] is a Glueful\Database\ORM\Collection of Post models (since 1.91)
        return $this->success([
            'items' => $page['data']->toArray(),
            'total' => $page['total'],
        ]);
    }

    public function store(CreatePostData $data): Response
    {
        $post = Post::create($this->context, [
            'title' => $data->title,
            'slug' => $data->slug,
            'body' => $data->body,
            'status' => 'draft',
        ]);

        return $this->created($post->toArray());
    }
}
```

```php
// app/Providers/AppServiceProvider.php — the router has no autowire fallback
\App\Controllers\PostController::class => ['autowire' => true, 'shared' => true],
```

```php
// routes/api.php
$router->group(['prefix' => 'v1'], function (Router $router) {
    $router->get('/posts', [PostController::class, 'index']);
    $router->post('/posts', [PostController::class, 'store'])->middleware('auth');
});
```

A failed DTO validation returns **422** and the controller never runs. `unique:` / `exists:` are enforced since 1.91 and need the validator's database connection — the DTO path (and `#[Validate]` / `FormRequest`) supplies it. Still back uniqueness with a unique index.

> `BaseController::validateRequest($data, $rules)` is a **legacy** helper that only understands `required`, `email` and `max:N` and ignores everything else (including `unique:`). Don't use it for new code.

---

## Standard Structure (10–50 endpoints)

```
app/
├── Actions/              # Single-purpose use cases (primary orchestrators)
├── Controllers/          # Thin HTTP layer
├── DTOs/                 # Request DTOs (RequestData) and response DTOs (ResponseData)
├── Events/               # Domain events (extend Glueful\Events\Contracts\BaseEvent)
│   └── Listeners/        # #[AsListener] classes (php glueful event:listener)
├── Exceptions/           # Application exceptions
├── Http/
│   ├── Middleware/       # RouteMiddleware implementations (scaffold:middleware)
│   └── Resources/        # JsonResource / ModelResource transformers (scaffold:resource)
├── Jobs/                 # Queued work (extend Glueful\Queue\Job)
├── Models/               # ORM models (thinner than Starter)
├── Policies/             # Authorization rules (Glueful\Permissions\PolicyInterface)
├── Providers/            # Service registration
└── Support/              # Helpers, value objects
```

**When to upgrade:** controllers pass ~100 lines, the same logic appears in several places, you need async work, or authorization gets complex.

**Model philosophy (Standard):** move business logic into Actions. Models keep persistence concerns: relationships, scopes, casts.

### Layer Responsibilities

```
┌─────────────────────────────────────────────────────────────┐
│ INTERFACE    Controllers, Middleware, DTOs, Resources        │
│              receive HTTP, validate (DTOs), call Actions,    │
│              shape responses                                 │
└─────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│ APPLICATION  Actions, Jobs, Events/Listeners, Policies       │
│              orchestrate use cases, dispatch events/jobs     │
└─────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│ DOMAIN       Models, Support (value objects)                 │
│              persistence; Support/ stays framework-free      │
└─────────────────────────────────────────────────────────────┘
```

**Actions** have one public method (`execute()`), are named after what they do (`CreatePostAction`), and coordinate models and side effects. **Services** (Enterprise tier) hold framework-free logic shared by several Actions.

### Example: Post creation flow

```php
// app/Events/PostCreatedEvent.php
namespace App\Events;

use Glueful\Events\Contracts\BaseEvent;

class PostCreatedEvent extends BaseEvent
{
    public function __construct(public readonly int $postId)
    {
        parent::__construct();
    }
}
```

```php
// app/Actions/CreatePostAction.php
namespace App\Actions;

use App\DTOs\CreatePostData;
use App\Events\PostCreatedEvent;
use App\Jobs\IndexPostJob;
use App\Models\Post;
use Glueful\Bootstrap\ApplicationContext;
use Glueful\Events\EventService;
use Glueful\Queue\QueueManager;

final class CreatePostAction
{
    public function __construct(
        private readonly ApplicationContext $context,
        private readonly EventService $events,
        private readonly QueueManager $queue,
    ) {}

    public function execute(CreatePostData $data): Post
    {
        $post = Post::create($this->context, [
            'title' => $data->title,
            'slug' => $data->slug,
            'body' => $data->body,
            'status' => 'draft',
        ]);

        $this->events->dispatch(new PostCreatedEvent($post->id));

        // push(jobClass, data, ?queue, ?connection) — only the class name + array are stored
        $this->queue->push(IndexPostJob::class, ['post_id' => $post->id]);

        return $post;
    }
}
```

```php
// app/Controllers/PostController.php
namespace App\Controllers;

use App\Actions\CreatePostAction;
use App\DTOs\CreatePostData;
use App\Http\Resources\PostResource;
use Glueful\Bootstrap\ApplicationContext;
use Glueful\Controllers\BaseController;
use Glueful\Http\Response;

class PostController extends BaseController
{
    // Overriding the constructor? Take the context and call the parent.
    public function __construct(
        ApplicationContext $context,
        private readonly CreatePostAction $createPost,
    ) {
        parent::__construct($context);
    }

    public function store(CreatePostData $data): Response
    {
        $post = $this->createPost->execute($data);

        return PostResource::make($post)->toResponse(201);
    }
}
```

```php
// app/Http/Resources/PostResource.php (php glueful scaffold:resource PostResource --model)
namespace App\Http\Resources;

use Glueful\Http\Resources\ModelResource;

class PostResource extends ModelResource
{
    public function toArray(): array
    {
        return [
            'id' => $this->attribute('id'),
            'title' => $this->attribute('title'),
            'slug' => $this->attribute('slug'),
            'status' => $this->attribute('status'),
        ];
    }
}
// List endpoints: PostResource::collection($page['data'])->withPaginationFrom($page)->toResponse()
```

```php
// app/Jobs/IndexPostJob.php — the worker builds the job with your data and the app context
namespace App\Jobs;

use App\Models\Post;
use Glueful\Queue\Job;
use Psr\Log\LoggerInterface;

class IndexPostJob extends Job
{
    public function handle(): void
    {
        $post = Post::find($this->context, $this->getData()['post_id']);
        if ($post === null) {
            return;
        }

        // ... push to the search index
        app($this->context, LoggerInterface::class)->info('Post indexed', ['id' => $post->id]);
    }
}
```

```php
// app/Events/Listeners/PostAuditListener.php
namespace App\Events\Listeners;

use App\Events\PostCreatedEvent;
use Glueful\Events\Attributes\AsListener;
use Psr\Log\LoggerInterface;

final class PostAuditListener
{
    public function __construct(private readonly LoggerInterface $logger) {}

    #[AsListener(PostCreatedEvent::class)]
    public function onCreated(PostCreatedEvent $event): void
    {
        $this->logger->info('Post created', ['id' => $event->postId]);
    }
}
```

Wire it together. Controllers and the classes they depend on are registered in `services()`; listeners are subscribed at boot (there is no directory scanning and no listener map in `config/events.php`):

```php
// app/Providers/AppServiceProvider.php
public static function services(): array
{
    return [
        \App\Controllers\PostController::class => ['autowire' => true, 'shared' => true],
        \App\Actions\CreatePostAction::class => ['autowire' => true],
    ];
}
```

```php
// app/Providers/EventServiceProvider.php — add 'App\\Providers\\EventServiceProvider' to config/serviceproviders.php
namespace App\Providers;

use App\Events\Listeners\PostAuditListener;
use Glueful\Bootstrap\ApplicationContext;
use Glueful\Events\EventService;
use Glueful\Extensions\ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    public static function services(): array
    {
        return [];
    }

    public function boot(ApplicationContext $context): void
    {
        // subscribe() reads #[AsListener] (and/or EventSubscriberInterface); a class it can't build throws here
        app($context, EventService::class)->subscribe(PostAuditListener::class);
    }
}
```

Listeners run synchronously inside `dispatch()` and failures are isolated (logged, other listeners still run). Push slow work to a job.

---

## Enterprise Structure (50+ endpoints)

```
app/
├── Actions/
├── Controllers/
├── DTOs/
│   ├── Requests/         # RequestData classes
│   └── Responses/        # ResponseData classes
├── Events/
│   └── Listeners/
├── Exceptions/
│   ├── Domain/           # Business rule violations
│   └── Application/
├── Filters/              # QueryFilter classes (scaffold:filter)
├── Http/
│   ├── Middleware/
│   └── Resources/
├── Jobs/
├── Models/               # Thin — persistence only
├── Policies/
├── Providers/
├── Repositories/         # Data access abstraction
├── Services/             # Shared, framework-free domain logic
├── Support/
│   ├── Constants/
│   ├── Helpers/
│   └── ValueObjects/
└── Validation/
    └── Rules/            # Custom rules, usable by name in #[Rule('...')]
```

**When to upgrade:** multiple data sources, domain logic spanning many models, swappable persistence, several teams, strict testing needs.

**Model philosophy (Enterprise):** models are persistence only. Actions orchestrate, Services hold shared domain rules, Repositories abstract data access.

| Component | Add when… |
|-----------|-----------|
| **Repositories/** | You need to swap implementations or test complex queries in isolation |
| **Services/** | Framework-free logic is shared by several Actions (`PricingService`) |
| **Validation/Rules/** | A rule is reused across DTOs. A class `App\Validation\Rules\Slug` implementing `Glueful\Validation\Contracts\Rule` is usable as `#[Rule('required\|slug')]` |
| **DTOs/Responses/** | You want a typed response contract (`ResponseData`, `CollectionResponse`, `PaginatedResponse`) that also drives the OpenAPI schema |
| **Support/ValueObjects/** | A domain concept deserves its own type (Money, Email, DateRange) |

### Repository Example

```php
// app/Repositories/Contracts/PostRepositoryInterface.php
interface PostRepositoryInterface
{
    public function findBySlug(string $slug): ?Post;
    public function published(int $page, int $perPage): array;
    public function save(Post $post): void;
}

// app/Repositories/OrmPostRepository.php
use Glueful\Bootstrap\ApplicationContext;

final class OrmPostRepository implements PostRepositoryInterface
{
    public function __construct(private readonly ApplicationContext $context) {}

    public function findBySlug(string $slug): ?Post
    {
        return Post::query($this->context)->where('slug', $slug)->first();
    }

    public function published(int $page, int $perPage): array
    {
        return Post::query($this->context)
            ->where('status', 'published')
            ->latest()
            ->paginate($page, $perPage);   // ['data' => Collection<Post>, 'total' => ..., ...]
    }

    public function save(Post $post): void
    {
        $post->save();
    }
}

// app/Providers/AppServiceProvider.php — bind the interface via alias
\App\Repositories\OrmPostRepository::class => [
    'autowire' => true,
    'alias' => \App\Repositories\Contracts\PostRepositoryInterface::class,
],
```

For table-oriented (non-ORM) repositories the framework also ships `Glueful\Repository\BaseRepository` — see the framework docs: Advanced → Repositories.

### Service Example (Domain Logic)

```php
// app/Services/PricingService.php — plain PHP, no framework types
final class PricingService
{
    public function orderTotal(array $items, ?string $couponCode = null): Money
    {
        $subtotal = array_reduce(
            $items,
            fn (Money $total, array $item) => $total->add(Money::of($item['price'])->multiply($item['quantity'])),
            Money::zero(),
        );

        return $subtotal->subtract($this->discount($subtotal, $couponCode));
    }

    // ...
}
```

---

## Model Philosophy Progression

| Tier | Model responsibility | Business logic lives in |
|------|---------------------|------------------------|
| **Starter** | Persistence + simple behaviour | Models (`$post->publish()`) |
| **Standard** | Persistence + relationships + scopes | Actions |
| **Enterprise** | Persistence only | Actions + Services, data access via Repositories |

---

## Naming Conventions

| Kind | Convention | Examples |
|------|-----------|----------|
| Actions | verb + noun + `Action` | `CreatePostAction`, `ProcessPaymentAction` |
| Jobs | the work being done | `IndexPostJob`, `SendWelcomeEmail` |
| Events | past tense + `Event` (`event:create` adds the suffix) | `PostCreatedEvent`, `PaymentFailedEvent` |
| Listeners | action + `Listener` (`event:listener` adds the suffix) | `PostAuditListener` |
| Request DTOs | intent + `Data` | `CreatePostData`, `UpdateProfileData` |
| Response DTOs | resource + `Data` | `PostData`, `AuthorData` |
| Services | domain capability | `PricingService`, `InventoryService` |
| Policies | protected model + `Policy` | `PostPolicy`, `OrderPolicy` |

---

## Directory Creation Commands

```bash
# Starter → Standard
mkdir -p app/{Actions,DTOs,Events/Listeners,Exceptions,Http/Middleware,Http/Resources,Jobs,Policies,Support}

# Standard → Enterprise
mkdir -p app/DTOs/{Requests,Responses}
mkdir -p app/Exceptions/{Domain,Application}
mkdir -p app/Support/{Constants,Helpers,ValueObjects}
mkdir -p app/{Filters,Repositories,Services,Validation/Rules}
```

Most scaffold commands create their directory on first use; see [SCAFFOLD_GUIDE.md](SCAFFOLD_GUIDE.md).

---

## Anti-Patterns to Avoid

### 1. Fat controllers

```php
// BAD: validation, business logic, email, events — 200 lines in store()

// GOOD: a typed DTO validates, an Action does the work
public function store(CreatePostData $data): Response
{
    return PostResource::make($this->createPost->execute($data))->toResponse(201);
}
```

### 2. Laravel-style statics

```php
// BAD — none of these exist in Glueful
User::find($id);
Post::create($data);
DB::transaction(fn () => ...);
config('app.name');
$this->validate($request, ['email' => 'required|email|unique:users']);

// GOOD
Post::find($this->context, $id);
Post::create($this->context, $data);
db($this->context)->transaction(fn () => ...);
config($this->context, 'app.name');
public function store(CreatePostData $data): Response   // validation via a request DTO
```

### 3. Framework types in the domain

```php
// BAD
class PricingService { public function total(Request $request): Response { /* ... */ } }

// GOOD
class PricingService { public function total(array $items): Money { /* ... */ } }
```

### 4. Premature abstraction

A repository interface plus implementation for simple CRUD in a small app is overhead. In the Starter tier, `Post::find($this->context, $id)` and `Post::create($this->context, $data)` are enough.

---

## Quick Reference

| Size | Key patterns |
|------|--------------|
| **Starter** | Controllers + request DTOs + models with light behaviour |
| **Standard** | Actions for use cases, events/listeners and jobs for side effects, resources for output |
| **Enterprise** | Repositories, Services, response DTOs, custom rules |

**Rule of thumb:** if you're unsure whether to add a folder, don't.

Further reading in the framework docs: Essentials → Controllers, ORM, Validation, Typed Requests & Responses; Features → Events & Listeners, Queues & Jobs; Advanced → Dependency Injection, Service Providers, Repositories, Testing.
