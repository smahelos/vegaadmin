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

        Blade::directive('getColumnSpan', function ($fieldName) {
                if(in_array($fieldName, ['payment_amount', 'account_number'])) {
                    return 'md:col-span-4';
                } elseif(in_array($fieldName, ['bank_code', 'bank_name'])) {
                    return 'md:col-span-3';
                } elseif(in_array($fieldName, ['city', 'zip', 'client_city', 'client_zip'])) {
                    return 'md:col-span-2';
                } else {
                    return 'md:col-span-1';
                }
        });
    }
}
