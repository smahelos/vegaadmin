<?php

return [
    // Core Laravel providers
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    App\Providers\BladeServiceProvider::class,

    // Domain Events provider (Global)
    App\Providers\DomainEventsServiceProvider::class,

    // Consolidated architectural layer providers
    App\Providers\DomainServiceProvider::class,           // All Domain services & business logic
    App\Infrastructure\InfrastructureServiceProvider::class, // All Infrastructure implementations
    App\Application\ApplicationServiceProvider::class,      // All Application layer use-cases
    App\Providers\UIServiceProvider::class,               // Presentation layer (UI, Blade, Locale)
    App\Providers\ThirdPartyServiceProvider::class,      // External dependencies
    App\Providers\PaymentDomainServiceProvider::class,   // Payment-specific domain bindings (gateways)
];
