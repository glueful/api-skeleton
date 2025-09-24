<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;

// Bootstrap the framework application
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Create request from globals
$request = Request::createFromGlobals();

// Handle request through Application
$response = $app->handle($request);

// Send the response
$response->send();

// Terminate lifecycle
$app->terminate($request, $response);
