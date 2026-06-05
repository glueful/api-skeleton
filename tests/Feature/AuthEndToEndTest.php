<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\Support\InMemoryUserProvider;
use App\Tests\TestCase;
use Glueful\Auth\AuthenticationService;
use Glueful\Auth\IdentityResolver;
use Glueful\Auth\UserIdentity;

/**
 * Proves the auth seam works on the skeleton: AuthenticationService routes credential
 * verification through UserProviderInterface, and with no RBAC extension the resolved identity
 * carries no roles (authorization fails closed). Uses an in-memory provider — the same approach
 * the framework's seam tests use — so the skeleton suite needs no database. The REAL glueful/users
 * provider + its schema are verified by that extension's own suite and by `migrate:run`
 * (framework FOUNDATION → users IDENTITY → app DEFAULT, source-tracked).
 */
final class AuthEndToEndTest extends TestCase
{
    public function test_login_through_seam_succeeds_and_has_no_roles_without_rbac(): void
    {
        $provider = (new InMemoryUserProvider())->add(
            new UserIdentity('u-amy00000001', email: 'amy@example.test', username: 'amy', status: 'active'),
            'secret-123',
            'amy@example.test',
        );

        $auth = new AuthenticationService(
            context: $this->app()->getContext(),
            userProvider: $provider,
            identityResolver: new IdentityResolver([]),
        );

        $userData = $auth->verifyCredentials(['email' => 'amy@example.test', 'password' => 'secret-123']);
        self::assertNotNull($userData);
        self::assertSame('amy@example.test', $userData['email'] ?? null);
        // No RBAC extension enabled → no roles → role-gated routes fail closed.
        self::assertSame([], $userData['roles'] ?? null);

        // Wrong password is rejected.
        self::assertNull($auth->verifyCredentials(['email' => 'amy@example.test', 'password' => 'nope']));
    }
}
