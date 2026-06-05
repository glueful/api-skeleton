<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Exercises the skeleton's example routes by dispatching real requests through the application
 * kernel (Application::handle), since the framework's TestCase provides container access rather
 * than HTTP sugar.
 */
class WelcomeTest extends TestCase
{
    public function testWelcomeEndpoint(): void
    {
        $response = $this->app()->handle(Request::create('/welcome', 'GET'));

        self::assertSame(200, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true);
        self::assertIsArray($payload);
        self::assertTrue($payload['success'] ?? false);
        self::assertSame('Welcome to your Glueful API!', $payload['data']['message'] ?? null);
        self::assertArrayHasKey('version', $payload['data']);
        self::assertArrayHasKey('timestamp', $payload['data']);
    }

    public function testStatusEndpoint(): void
    {
        $response = $this->app()->handle(Request::create('/v1/status', 'GET'));

        self::assertSame(200, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true);
        self::assertIsArray($payload);
        self::assertTrue($payload['success'] ?? false);
        self::assertSame('healthy', $payload['data']['status'] ?? null);
        self::assertArrayHasKey('timestamp', $payload['data']);
    }
}
