<?php

use Glueful\Routing\Router;
use App\Controllers\WelcomeController;

/**
 * Application API Routes
 *
 * @var Router $router Router instance injected by RouteManifest::load()
 *
 * Route Prefix Options:
 *   - 'v1'                    → /v1/welcome (simple versioning)
 *   - '/api/v1'               → /api/v1/welcome (with api prefix)
 *   - api_prefix($context)    → uses config/api.php versioning settings
 *   - (no group)              → /welcome (no prefix)
 */

$router->group(['prefix' => 'v1'], function (Router $router) {
    /**
     * @route GET /status
     * @summary Status (Lightweight)
     * @description Lightweight status check for the application skeleton
     * @tag Status
     * @response 200 application/json "Service status" {
     *   success:boolean="true",
     *   message:string="Success message",
     *   data:{
     *     status:string="healthy",
     *     timestamp:string="ISO 8601 timestamp"
     *   }
     * }
     */
    $router->get('/status', [WelcomeController::class, 'status']);
});

 /**
 * @route GET /welcome
 * @summary Welcome Endpoint
 * @description Returns a welcome payload with version and timestamp
 * @tag Example
 * @response 200 application/json "Welcome payload" {
 *   success:boolean="true",
 *   message:string="Success message",
 *   data:{
 *     message:string="Welcome text",
 *     version:string="Application version",
 *     timestamp:string="ISO 8601 timestamp"
 *   }
 * }
 */
$router->get('/welcome', [WelcomeController::class, 'index']);
