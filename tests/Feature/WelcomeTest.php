<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

class WelcomeTest extends TestCase
{
    public function testWelcomeEndpoint(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertJson([
            'message' => 'Welcome to your Glueful API!'
        ]);
    }

    public function testHealthEndpoint(): void
    {
        $response = $this->get('/health');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'uptime',
            'memory_usage',
            'peak_memory'
        ]);
    }
}
