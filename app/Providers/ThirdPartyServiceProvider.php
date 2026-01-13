<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Third Party Service Provider.
 * 
 * Registers all third-party library service providers and external dependencies.
 * This keeps external dependencies isolated from our own application providers.
 */
class ThirdPartyServiceProvider extends ServiceProvider
{
    /**
     * All third-party service providers to register.
     */
    protected array $providers = [
        \Barryvdh\DomPDF\ServiceProvider::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register all third-party providers
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Third-party configuration and bootstrapping can be done here
        $this->configureDomPDF();
    }

    /**
     * Configure DomPDF settings.
     */
    private function configureDomPDF(): void
    {
        // Any DomPDF-specific configuration can be added here
        // Currently using default configuration from config/dompdf.php
    }
}
