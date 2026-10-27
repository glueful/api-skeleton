# Scaffold Command Guide

Quick reference for generating application code with the Glueful CLI (framework 1.91.3). Every scaffold command accepts `--force` (`-f`) to overwrite, and most accept `--path` (`-p`) for a custom output directory. Run any command with `--help` for its full options, or `php glueful list scaffold` to see them all.

Scaffolds write files only — they don't register services or add routes. Where a step is needed, it's listed below.

| Command | Default output (this skeleton) | Namespace |
|---------|-------------------------------|-----------|
| `scaffold:controller` | `app/Controllers/` | `App\Controllers` |
| `scaffold:dto` | `app/DTOs/` | `App\DTOs` |
| `scaffold:request` | `app/Http/Requests/` | `App\Http\Requests` |
| `scaffold:resource` | `app/Http/Resources/` | `App\Http\Resources` |
| `scaffold:middleware` | `app/Http/Middleware/` | `App\Http\Middleware` |
| `scaffold:job` | `app/Jobs/` | `App\Jobs` |
| `scaffold:rule` | `app/Validation/Rules/` | `App\Validation\Rules` |
| `scaffold:filter` | `app/Filters/` | `App\Filters` |
| `scaffold:model` | `app/Models/` | `App\Models` |
| `scaffold:test` | `tests/Unit/` or `tests/Feature/` | `App\Tests\Unit` / `App\Tests\Feature` |
| `scaffold:factory` / `scaffold:seeder` | `app/Database/Factories/` / `app/Database/Seeders/` | `App\Database\Factories` / `App\Database\Seeders` |
| `event:create` / `event:listener` | `app/Events/` / `app/Events/Listeners/` | `App\Events` / `App\Events\Listeners` |

## Controllers

```bash
php glueful scaffold:controller PostController             # index, show
php glueful scaffold:controller PostController --resource  # index, create, store, show, edit, update, destroy
php glueful scaffold:controller PostController --api       # index, show, store, update, destroy
```

The class extends `Glueful\Controllers\BaseController` and lands in `app/Controllers` under `App\Controllers`. The command prints its next steps, including the registration line. **Add it to `App\Providers\AppServiceProvider::services()`** — the router resolves controllers from the container and has no autowire fallback, so an unregistered controller fails with "Service not found":

```php
// app/Providers/AppServiceProvider.php
public static function services(): array
{
    return [
        \App\Controllers\WelcomeController::class => ['autowire' => true, 'shared' => true],
        \App\Controllers\PostController::class => ['autowire' => true, 'shared' => true],
    ];
}
```

Then add routes in `routes/api.php` (every `routes/*.php` file is loaded automatically):

```php
$router->group(['prefix' => 'v1'], function (Router $router) {
    $router->get('/posts', [PostController::class, 'index']);
    $router->post('/posts', [PostController::class, 'store'])->middleware('auth');
});
```

### Attribute-Based Routing

Instead of route files, put attributes on the controller. Controllers in `app/Controllers/` are **scanned automatically at boot** — no loader to register — but they still need the `services()` entry above. Attribute routes are not API-prefixed, so put the full prefix in `#[Controller]`.

```php
<?php

namespace App\Controllers;

use App\DTOs\CreatePostData;
use App\Models\Post;
use Glueful\Controllers\BaseController;
use Glueful\Http\Response;
use Glueful\Routing\Attributes\{Controller, Delete, Get, Middleware, Post as PostRoute};

#[Controller(prefix: '/v1/posts', middleware: ['auth'])]
class PostController extends BaseController
{
    #[Get('/')]
    public function index(): Response
    {
        $page = Post::query($this->context)->latest()->paginate(1, 20);

        return $this->success($page['data']->toArray());   // 'data' is an ORM Collection of models
    }

    #[Get('/{id}', where: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        return $this->success(Post::findOrFail($this->context, $id)->toArray());   // 404 when missing
    }

    #[PostRoute('/')]
    public function store(CreatePostData $data): Response   // validated before this runs (422 on failure)
    {
        $post = Post::create($this->context, [
            'title' => $data->title,
            'slug' => $data->slug,
            'body' => $data->body,
        ]);

        return $this->created($post->toArray());
    }

    #[Delete('/{id}', where: ['id' => '\d+'])]
    #[Middleware('admin')]   // method-level middleware (an alias you have registered)
    public function destroy(int $id): Response
    {
        Post::findOrFail($this->context, $id)->delete();

        return $this->success(null, 'Deleted');
    }
}
```

| Attribute | Target | Parameters |
|-----------|--------|------------|
| `#[Controller]` | Class | `prefix`, `middleware` |
| `#[Get]`, `#[Post]`, `#[Put]`, `#[Patch]`, `#[Delete]`, `#[Options]` | Method | `path`, `name`, `where` |
| `#[Route]` | Method | `path`, `methods`, `name`, `middleware`, `where` |
| `#[Middleware]` | Class/Method | a string or array of middleware |
| `#[Fields]` | Method | `allowed`, `strict`, … (attaches `field_selection`) |
| `#[RequireScope]` | Method | scope string (attaches `require_scope`) |
| `#[ResponseStatus]` | Method | 2xx status for a returned `ResponseData` |
| `#[RateLimit]` (`Glueful\Api\RateLimiting\Attributes`) | Class/Method | `attempts`, `perMinutes`/`perHours`/`perDays`, … (attaches `rate_limit`) |

All are in `Glueful\Routing\Attributes` unless noted. File routes and attribute routes can be mixed.

## Models

```bash
php glueful scaffold:model Post
php glueful scaffold:model Post --fillable=title,slug,body --soft-deletes
php glueful scaffold:model Post --table=blog_posts
```

Options: `--fillable`, `--table`, `--soft-deletes` (`-s`), `--timestamps` (`-t`, on by default), `--migration` (`-m`), `--force`.

`--migration` also writes `database/migrations/NNN_Create<Table>Table.php`, a `hasTable`-guarded `createTable()` with a `dropTableIfExists()` rollback that `migrate:run` picks up.

Models are context-first everywhere: `Post::query($context)`, `Post::find($context, $id)`, `Post::create($context, [...])`, `new Post([...], $context)`.

## Request DTOs (recommended validation)

```bash
php glueful scaffold:dto CreatePostData             # RequestData + #[Rule] → app/DTOs
php glueful scaffold:dto PostData --response        # ResponseData
```

A controller method that type-hints a `RequestData` class gets it hydrated and validated by the router; on failure the client gets a 422 and the method never runs:

```php
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

`unique:` / `exists:` are enforced since 1.91 and need the validator's database connection; the DTO path supplies it. (A hand-built `Validator` needs `new Validator($rules, $context)` — without a context or connection these rules throw `LogicException`.)

## Requests (FormRequest)

```bash
php glueful scaffold:request CreatePostRequest      # extends Glueful\Validation\FormRequest
```

A `FormRequest` has `rules()`, `authorize()`, `messages()` and `prepareForValidation()`. Prefer request DTOs for new endpoints; `FormRequest` / `#[Validate]` suit array-style input. `BaseController::validateRequest()` is legacy (only `required`, `email`, `max:N`).

## Resources (API Transformers)

```bash
php glueful scaffold:resource PostResource                # JsonResource
php glueful scaffold:resource PostResource --model        # ModelResource (relation/attribute helpers)
php glueful scaffold:resource PostCollection --collection # ResourceCollection
```

```php
return PostResource::make($post)->toResponse(201);

$page = Post::query($this->context)->paginate(1, 20);
return PostResource::collection($page['data'])->withPaginationFrom($page)->toResponse();
```

## Jobs (Queue)

```bash
php glueful scaffold:job IndexPost
php glueful scaffold:job SendNewsletter --queue=emails
php glueful scaffold:job GenerateReport --tries=5
```

`--tries` sets the job's `getMaxAttempts()`; a `'maxAttempts' => N` pushed with the data overrides it, and `queue:work --tries` caps both. Retry delays come from `queue.workers.performance.backoff_*`, and the queue is the third argument to `push()`.

Jobs extend `Glueful\Queue\Job`. Read input with `$this->getData()` and resolve services through `$this->context`:

```php
public function handle(): void
{
    $post = Post::find($this->context, $this->getData()['post_id']);
    // ...
}
```

Dispatch with the class name and a plain array:

```php
app($this->context, \Glueful\Queue\QueueManager::class)->push(IndexPost::class, ['post_id' => $post->id]);
```

## Events & Listeners

```bash
php glueful event:create PostCreated                 # → App\Events\PostCreatedEvent
php glueful event:create Auth/LoginFailed            # → App\Events\Auth\LoginFailedEvent
php glueful event:create SecurityAlert --type=security
php glueful event:listener PostAudit --event='App\Events\PostCreatedEvent'   # → App\Events\Listeners\PostAuditListener
php glueful event:listener PostAudit --method=onCreated
```

The generated event uses the `EventHelpers` trait; the convention is to extend `Glueful\Events\Contracts\BaseEvent` (and call `parent::__construct()`). The generated handler carries `#[AsListener]`; subscribe the class at boot, as the command's hint shows (there is no listener map in `config/events.php`):

```php
use Glueful\Events\Attributes\AsListener;

final class PostAuditListener
{
    #[AsListener(PostCreatedEvent::class)]
    public function onCreated(PostCreatedEvent $event): void { /* ... */ }
}

// in a provider extending Glueful\Extensions\ServiceProvider, listed in config/serviceproviders.php
public function boot(ApplicationContext $context): void
{
    app($context, EventService::class)->subscribe(PostAuditListener::class);
}

// dispatch
app($this->context, EventService::class)->dispatch(new PostCreatedEvent($post->id));
```

## Middleware

```bash
php glueful scaffold:middleware EnsureAdmin          # → App\Http\Middleware\EnsureAdminMiddleware
php glueful scaffold:middleware Admin/AuditTrail
```

The class implements `Glueful\Routing\RouteMiddleware`. Register it with an alias, then use the alias on routes:

```php
// AppServiceProvider::services()
\App\Http\Middleware\EnsureAdminMiddleware::class => ['autowire' => true, 'alias' => 'admin'],

// routes/api.php
$router->delete('/posts/{id}', [PostController::class, 'destroy'])->middleware(['auth', 'admin']);
```

## Validation Rules

```bash
php glueful scaffold:rule Slug                                  # → App\Validation\Rules\Slug
php glueful scaffold:rule PasswordStrength --params=minLength,requireNumbers
php glueful scaffold:rule RequiredWithoutField --implicit       # runs even when the field is empty
```

A rule in `App\Validation\Rules` is resolved by name, so `Slug` is usable as `#[Rule('required|slug')]`.

## Query Filters

```bash
php glueful scaffold:filter PostFilter --filterable=status,author_id --sortable=created_at --searchable=title
```

## Tests

```bash
php glueful scaffold:test PostServiceTest                       # unit (default) → tests/Unit
php glueful scaffold:test PostApiTest --feature                 # → tests/Feature
php glueful scaffold:test PaymentTest --methods=testCharge,testRefund
php glueful scaffold:test PostServiceTest --class='App\Services\PostService'
```

Unit tests extend `PHPUnit\Framework\TestCase`. Feature tests extend the skeleton's `App\Tests\TestCase`, which boots the app; dispatch requests the way `tests/Feature/WelcomeTest.php` does (`$this->app()->handle(Request::create('/v1/posts', 'GET'))`).

## Factories & Seeders

```bash
php glueful scaffold:factory PostFactory --model='App\Models\Post'
php glueful scaffold:seeder PostSeeder --model='App\Models\Post'
php glueful db:seed                 # runs App\Database\Seeders\DatabaseSeeder
php glueful db:seed PostSeeder      # short names resolve under App\Database\Seeders\
```

They live under `app/Database/`, so the skeleton's `App\` autoload already covers them. Factories
also need Faker (`composer require --dev fakerphp/faker`) and the `HasFactory` trait on the model:

```php
use Glueful\Database\ORM\Concerns\HasFactory;
use Glueful\Database\ORM\Model;

class Post extends Model
{
    use HasFactory;
    // ...
}

// in PostSeeder::run()
Post::factory()->count(10)->create();
```

Seeders and factories run with the app's context when invoked through `db:seed` or a booted test;
elsewhere pass one: `Post::factory(10, $context)->create()`.

## Migrations

```bash
php glueful migrate:create create_posts_table   # → database/migrations/NNN_CreatePostsTable.php
php glueful migrate:run
php glueful migrate:rollback
php glueful migrate:status
```

---

## Common Patterns

### CRUD endpoint

```bash
php glueful migrate:create create_posts_table               # 1. schema (edit, then migrate:run)
php glueful scaffold:model Post --fillable=title,slug,body  # 2. model → app/Models/Post.php
php glueful scaffold:dto CreatePostData                     # 3. request DTOs
php glueful scaffold:dto UpdatePostData
php glueful scaffold:resource PostResource --model          # 4. output shape
php glueful scaffold:controller PostController --api        # 5. controller
# 6. register PostController in AppServiceProvider::services()
# 7. add routes in routes/api.php (or attributes on the controller)
php glueful migrate:run
```

### Background job

```bash
php glueful scaffold:job SendWelcomeEmail --queue=emails --tries=3
# dispatch: app($this->context, QueueManager::class)->push(SendWelcomeEmail::class, ['user_uuid' => $uuid], 'emails');
php glueful queue:work --queue=emails
```

### Event-driven feature

```bash
php glueful event:create PostCreated
php glueful event:listener PostAudit --event='App\Events\PostCreatedEvent' --method=onCreated
# add #[AsListener(PostCreatedEvent::class)] to onCreated(), subscribe the class in a provider's boot()
```

---

## Naming Conventions

- Controllers: `PostController`
- Models: `Post` (singular PascalCase; table defaults to the snake_case plural, `posts`)
- Migrations: `create_posts_table`
- Request DTOs: `CreatePostData`; response DTOs: `PostData`
- Jobs: `SendWelcomeEmail` (no suffix required)
- Events: `PostCreated` → `PostCreatedEvent`; listeners: `PostAudit` → `PostAuditListener`
- Middleware: `EnsureAdmin` → `EnsureAdminMiddleware`

See [APPLICATION_ARCHITECTURE.md](APPLICATION_ARCHITECTURE.md) for where these pieces fit, and the framework docs (CLI Reference; Essentials → Controllers, Validation, ORM) for details.
