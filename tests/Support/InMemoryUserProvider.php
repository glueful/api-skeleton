<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Glueful\Auth\Contracts\UserProviderInterface;
use Glueful\Auth\UserIdentity;

/**
 * In-memory {@see UserProviderInterface} for skeleton tests — the same pattern the framework uses
 * in its own seam tests. Lets the auth flow be exercised without standing up a database or the
 * concrete glueful/users store (whose real wiring is verified separately via migrate:run and its
 * own suite). Passwords are plain fixtures; this is a test double, not a credential store.
 */
final class InMemoryUserProvider implements UserProviderInterface
{
    /** @var array<string, UserIdentity> */
    private array $byUuid = [];
    /** @var array<string, array{password: string, identity: UserIdentity}> */
    private array $byLogin = [];

    public function add(UserIdentity $identity, string $password = '', string ...$logins): self
    {
        $this->byUuid[$identity->uuid()] = $identity;
        foreach ($logins as $login) {
            $this->byLogin[$login] = ['password' => $password, 'identity' => $identity];
        }
        return $this;
    }

    public function findByUuid(string $uuid): ?UserIdentity
    {
        return $this->byUuid[$uuid] ?? null;
    }

    public function findByLogin(string $identifier): ?UserIdentity
    {
        return $this->byLogin[$identifier]['identity'] ?? null;
    }

    public function verifyCredentials(string $identifier, string $password): ?UserIdentity
    {
        $entry = $this->byLogin[$identifier] ?? null;
        if ($entry === null) {
            return null;
        }
        return hash_equals($entry['password'], $password) ? $entry['identity'] : null;
    }
}
