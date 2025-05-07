<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

class LocaleServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
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
            Log::error('Error initializing LocaleServiceProvider: ' . $e->getMessage());
        }

        // Create custom directive for language switcher
        Blade::directive('languageSwitch', function () {
            return '<?php echo view("components.language-switcher")->render(); ?>';
        });
    }
}