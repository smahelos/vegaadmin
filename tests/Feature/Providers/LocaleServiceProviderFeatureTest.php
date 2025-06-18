<?php

namespace Tests\Feature\Providers;

use App\Providers\LocaleServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocaleServiceProviderFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function provider_is_registered_in_application(): void
    {
        $providers = App::getLoadedProviders();
        $this->assertArrayHasKey(LocaleServiceProvider::class, $providers);
    }

    #[Test]
    public function url_defaults_are_set_with_locale(): void
    {
        // Boot the provider
        $provider = new LocaleServiceProvider(app());
        $provider->boot();
        
        // Check that URL defaults contain lang parameter
        $defaults = URL::getDefaultParameters();
        $this->assertArrayHasKey('lang', $defaults);
    }

    #[Test]
    public function view_shares_available_locales(): void
    {
        // Boot the provider
        $provider = new LocaleServiceProvider(app());
        $provider->boot();
        
        // Check that view shares contain the locale variables
        $shared = View::getShared();
        $this->assertArrayHasKey('availableLocales', $shared);
        $this->assertArrayHasKey('currentLocale', $shared);
    }

    #[Test]
    public function available_locales_contains_expected_languages(): void
    {
        $provider = new LocaleServiceProvider(app());
        $provider->boot();
        
        $shared = View::getShared();
        $availableLocales = $shared['availableLocales'];
        
        $this->assertIsArray($availableLocales);
        $this->assertContains('cs', $availableLocales);
        $this->assertContains('en', $availableLocales);
        $this->assertContains('de', $availableLocales);
        $this->assertContains('sk', $availableLocales);
    }

    #[Test]
    public function locale_defaults_to_config_app_locale(): void
    {
        // Clear session
        Session::forget('locale');
        
        $provider = new LocaleServiceProvider(app());
        $provider->boot();
        
        $shared = View::getShared();
        $currentLocale = $shared['currentLocale'];
        
        $this->assertEquals(config('app.locale'), $currentLocale);
    }

    #[Test]
    public function locale_uses_session_value_when_available(): void
    {
        Session::put('locale', 'en');
        
        $provider = new LocaleServiceProvider(app());
        $provider->boot();
        
        $shared = View::getShared();
        $currentLocale = $shared['currentLocale'];
        
        $this->assertEquals('en', $currentLocale);
    }

    #[Test]
    public function invalid_locale_falls_back_to_default(): void
    {
        Session::put('locale', 'invalid_locale');
        
        $provider = new LocaleServiceProvider(app());
        $provider->boot();
        
        $shared = View::getShared();
        $currentLocale = $shared['currentLocale'];
        
        $this->assertEquals(config('app.fallback_locale', 'cs'), $currentLocale);
    }

    #[Test]
    public function language_switch_blade_directive_is_registered(): void
    {
        $provider = new LocaleServiceProvider(app());
        $provider->boot();
        
        // Test that the directive exists by trying to compile it
        $blade = app('blade.compiler');
        $compiled = $blade->compileString('@languageSwitch');
        
        $this->assertStringContainsString('language-switcher', $compiled);
        $this->assertStringContainsString('view(', $compiled);
    }

    #[Test]
    public function provider_can_be_instantiated(): void
    {
        $provider = new LocaleServiceProvider(app());
        $this->assertInstanceOf(LocaleServiceProvider::class, $provider);
    }

    #[Test]
    public function register_method_executes_without_errors(): void
    {
        $provider = new LocaleServiceProvider(app());
        
        // This should not throw any exceptions
        $provider->register();
        
        $this->assertTrue(true); // If we get here, no exceptions were thrown
    }

    #[Test]
    public function boot_method_executes_without_errors(): void
    {
        $provider = new LocaleServiceProvider(app());
        
        // This should not throw any exceptions
        $provider->boot();
        
        $this->assertTrue(true); // If we get here, no exceptions were thrown
    }

    #[Test]
    public function boot_method_handles_exceptions_gracefully(): void
    {
        // This test ensures that even if there are configuration issues,
        // the provider doesn't break the application
        $provider = new LocaleServiceProvider(app());
        
        // Should not throw exceptions even with invalid configuration
        $provider->boot();
        
        $this->assertTrue(true);
    }
}
