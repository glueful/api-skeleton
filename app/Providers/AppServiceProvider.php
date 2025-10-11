<?php

declare(strict_types=1);

namespace App\Providers;

final class AppServiceProvider
{
    /**
     * Runtime DSL provider path (used by ProviderLocator + ServicesLoader).
     * @return array<string, array<string, mixed>>
     */
    public static function services(): array
    {
        return [
            // Autowire application controllers so Router can resolve them via container
            \App\Controllers\WelcomeController::class => [
                'autowire' => true,
                'shared' => true,
            ],
        ];
    }
}
