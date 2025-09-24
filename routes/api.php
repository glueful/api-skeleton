<?php

use Glueful\Routing\Router;
use App\Controllers\WelcomeController;

/** @var Router $router Router instance injected by RouteManifest::load() */

//Routes
/**
 * @route GET /
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
$router->get('/', [WelcomeController::class, 'index']);

/**
 * @route GET /health
 * @summary Health (Lightweight)
 * @description Lightweight health check for the application skeleton
 * @tag Health
 * @response 200 application/json "Service is healthy" {
 *   success:boolean="true",
 *   message:string="Success message",
 *   data:{
 *     status:string="healthy",
 *     timestamp:string="ISO 8601 timestamp"
 *   }
 * }
 */
$router->get('/health', [WelcomeController::class, 'health']);
