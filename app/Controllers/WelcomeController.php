<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DTOs\Responses\StatusData;
use App\DTOs\Responses\WelcomeData;
use Glueful\Controllers\BaseController;
use Glueful\Http\Response;
use Glueful\Routing\Attributes\ApiOperation;
use Glueful\Routing\Attributes\ApiResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * The two example routes, and the pattern for documenting any route: `#[ApiOperation]` for the
 * summary and description, `#[ApiResponse]` with a ResponseData class for the body schema.
 * `php glueful generate:openapi` reflects the live route table; route docblocks are not read.
 */
class WelcomeController extends BaseController
{
    #[ApiOperation(
        summary: 'Welcome',
        description: 'A welcome payload with the application version and the server time.',
        tags: ['Example']
    )]
    #[ApiResponse(200, WelcomeData::class, description: 'Welcome payload')]
    public function index(Request $request): Response
    {
        return $this->success([
            'message' => 'Welcome to your Glueful API!',
            'version' => config($this->getContext(), 'app.version', '1.0.0'),
            'timestamp' => date('c'),
        ]);
    }

    #[ApiOperation(
        summary: 'Status',
        description: 'Lightweight liveness check for the application; /health is the framework\'s full check.',
        tags: ['Status']
    )]
    #[ApiResponse(200, StatusData::class, description: 'Service status')]
    public function status(Request $request): Response
    {
        return $this->success([
            'status' => 'healthy',
            'timestamp' => date('c'),
        ]);
    }
}
