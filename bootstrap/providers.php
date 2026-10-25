<?php

use App\Providers\AnalyticsServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\BladeServiceProvider;
use App\Providers\ContentServiceProvider;
use App\Providers\DomainEventsServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\InvoiceServiceProvider;
use App\Providers\PartyServiceProvider;
use App\Providers\PaymentServiceProvider;
use App\Providers\ProductServiceProvider;
use App\Providers\RouteServiceProvider;
use App\Providers\SharedServiceProvider;
use App\Providers\ThirdPartyServiceProvider;
use App\Providers\UIServiceProvider;
use App\Providers\UserServiceProvider;

return [
    // Core Laravel providers
    AppServiceProvider::class,
    AuthServiceProvider::class,
    EventServiceProvider::class,
    RouteServiceProvider::class,
    BladeServiceProvider::class,

    // Domain Events provider (Global)
    DomainEventsServiceProvider::class,

    // Bounded context composition roots (ADR D-042)
    SharedServiceProvider::class,
    AnalyticsServiceProvider::class,
    ContentServiceProvider::class,
    InvoiceServiceProvider::class,
    PartyServiceProvider::class,
    PaymentServiceProvider::class,
    ProductServiceProvider::class,
    UserServiceProvider::class,

    UIServiceProvider::class,               // Presentation layer (UI, Blade, Locale)
    ThirdPartyServiceProvider::class,      // External dependencies
];
