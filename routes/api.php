<?php

declare(strict_types=1);

use App\Controllers\WelcomeController;
use Glueful\Routing\Router;

/**
 * Application API routes.
 *
 * Every *.php file in this directory is loaded by RouteManifest. Document endpoints on the
 * controller with #[ApiOperation] and #[ApiResponse] (see WelcomeController) and with RequestData
 * DTOs for input; `php glueful generate:openapi` reflects the live route table. Route docblocks
 * are no longer read.
 *
 * Prefix options: 'v1' gives /v1/..., '/api/v1' gives /api/v1/..., api_prefix($context) follows
 * config/api.php, and no group gives a bare path.
 *
 * @var Router $router
 */
$router->group(['prefix' => 'v1'], function (Router $router) {
    $router->get('/status', [WelcomeController::class, 'status'])->name('status');
});

$router->get('/welcome', [WelcomeController::class, 'index'])->name('welcome');
