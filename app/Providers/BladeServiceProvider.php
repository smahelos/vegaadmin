<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class BladeServiceProvider extends ServiceProvider
{
    private static bool $functionsRegistered = false;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('renderRequiredMark', function ($field) {
            if(isset($field['required']) && $field['required'] === true) {
                    return '<span class="text-red-500">*</span>';
                }
                return '';
            });
    }
}
