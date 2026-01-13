<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

/**
 * UI Service Provider for Presentation Layer.
 * 
 * Handles all UI-related concerns including:
 * - Locale and language switching
 * - Blade directives
 * - View composers and shared data
 * - Frontend presentation layer services
 */
class UIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // No services to register at this time
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureLocaleHandling();
        $this->registerBladeDirectives();
    }

    /**
     * Configure locale handling and URL defaults.
     */
    private function configureLocaleHandling(): void
    {
        try {
            // Set default language for all URLs
            $locale = Session::get('locale', config('app.locale'));
            
            // Verify that locale is valid
            if (!in_array($locale, config('app.available_locales', ['cs', 'en', 'de', 'sk']))) {
                $locale = config('app.fallback_locale', 'cs');
            }
            
            URL::defaults(['lang' => $locale]);
    
            // Share available languages with views
            View::share('availableLocales', config('app.available_locales', ['cs', 'en', 'de', 'sk']));
            View::share('currentLocale', $locale);
        } catch (\Exception $e) {
            // Log errors but continue execution
            Log::error('Error initializing locale handling in UIServiceProvider: ' . $e->getMessage());
        }
    }

    /**
     * Register custom Blade directives.
     */
    private function registerBladeDirectives(): void
    {
        // Create custom directive for language switcher
        Blade::directive('languageSwitch', function () {
            return '<?php echo view("components.language-switcher")->render(); ?>';
        });

        // Additional UI-related Blade directives can be added here
    }
}
