<?php

return [
    // Core Laravel providers
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,             // Also Auth application context
    App\Providers\EventServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    App\Providers\BladeServiceProvider::class,

    // Domain Events provider (Global)
    App\Providers\DomainEventsServiceProvider::class,

    // Bounded context composition roots (ADR D-042)
    App\Providers\SharedServiceProvider::class,
    App\Providers\AnalyticsServiceProvider::class,
    App\Providers\ContentServiceProvider::class,
    App\Providers\InvoiceServiceProvider::class,
    App\Providers\PartyServiceProvider::class,
    App\Providers\PaymentServiceProvider::class,
    App\Providers\ProductServiceProvider::class,
    App\Providers\UserServiceProvider::class,

    App\Providers\UIServiceProvider::class,               // Presentation layer (UI, Blade, Locale)
    App\Providers\ThirdPartyServiceProvider::class,      // External dependencies
];
