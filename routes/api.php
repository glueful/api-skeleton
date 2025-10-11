<?php

use Glueful\Routing\Router;
use App\Controllers\WelcomeController;
use Symfony\Component\HttpFoundation\Request;

/** @var Router $router Router instance injected by RouteManifest::load() */

//Routes
/**
 * @route GET /status
 * @summary status (Lightweight)
 * @description Lightweight status check for the application skeleton
 * @tag status
 * @response 200 application/json "Service is statusy" {
 *   success:boolean="true",
 *   message:string="Success message",
 *   data:{
 *     status:string="healthy",
 *     timestamp:string="ISO 8601 timestamp"
 *   }
 * }
 */
$router->get('/status', function (Request $request) {
    $controller = new WelcomeController();
    return $controller->status($request);
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
