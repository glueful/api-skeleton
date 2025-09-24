<?php

declare(strict_types=1);

use Glueful\Framework;
use Dotenv\Dotenv;

// Load composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load .env early so environment is accurate
if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
}

// Create and configure the framework instance using project root
$framework = Framework::create(dirname(__DIR__))
    ->withConfigDir(dirname(__DIR__) . '/config')
    ->withEnvironment($_ENV['APP_ENV'] ?? 'development');

// Boot the framework and get application instance
$app = $framework->boot();

return $app;
