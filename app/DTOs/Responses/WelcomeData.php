<?php

declare(strict_types=1);

namespace App\DTOs\Responses;

use Glueful\Http\Contracts\ResponseData;

/**
 * Body of GET /welcome. Response DTOs give `#[ApiResponse]` a schema to reflect; they are the
 * documented contract even when the controller still returns a plain array.
 */
final class WelcomeData implements ResponseData
{
    public function __construct(
        public string $message,
        public string $version,
        /** ISO 8601 */
        public string $timestamp,
    ) {
    }
}
