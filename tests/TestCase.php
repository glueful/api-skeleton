<?php

declare(strict_types=1);

namespace App\Tests;

use Glueful\Framework;
use Glueful\Testing\TestCase as FrameworkTestCase;

abstract class TestCase extends FrameworkTestCase
{
    protected function createApplication(): \Glueful\Application
    {
        $framework = Framework::create(__DIR__ . '/..')
            ->withEnvironment('testing');
        return $framework->boot();
    }
}
