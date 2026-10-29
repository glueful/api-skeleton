<?php

declare(strict_types=1);

namespace App\DTOs\Responses;

use Glueful\Http\Contracts\ResponseData;

/**
 * Body of GET /v1/status.
 */
final class StatusData implements ResponseData
{
    public function __construct(
        /** healthy */
        public string $status,
        /** ISO 8601 */
        public string $timestamp,
    ) {
    }
}
