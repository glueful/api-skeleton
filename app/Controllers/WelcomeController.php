<?php

declare(strict_types=1);

namespace App\Controllers;

use Glueful\Controllers\BaseController;
use Glueful\Http\Response;
use Symfony\Component\HttpFoundation\Request;

class WelcomeController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->success([
            'message' => 'Welcome to your Glueful API!',
            'version' => config('app.version', '1.0.0'),
            'timestamp' => date('c'),
        ]);
    }

    public function health(Request $request): Response
    {
        return $this->success([
            'status' => 'healthy',
            'timestamp' => date('c'),
        ]);
    }
}

