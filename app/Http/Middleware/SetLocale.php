<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class SetLocale
{
    /**
     * Handle an incoming request and set application locale
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $lang = $this->determineLocale($request);
            
            // Verify that language is supported
            $availableLocales = config('app.available_locales', ['cs', 'en', 'de', 'sk']);
            if (in_array($lang, $availableLocales)) {
                App::setLocale($lang);
                Session::put('locale', $lang);
                
                // Set cookie for 30 days
                cookie()->queue('locale', $lang, 60 * 24 * 30);
            } else {
                $fallbackLocale = config('app.fallback_locale', 'cs');
                App::setLocale($fallbackLocale);
                Session::put('locale', $fallbackLocale);
            }
    
            return $next($request);
        } catch (\Exception $e) {
            // Log errors but don't stop request processing
            Log::error('Error in SetLocale middleware: ' . $e->getMessage());
            return $next($request);
        }
    }

    /**
     * Determine the locale from various sources in order of priority
     */
    private function determineLocale(Request $request): string
    {
        // 0. From URL segment (highest priority)
        $locale = $request->segment(1);
        if (!empty($locale)) {
            $availableLocales = config('app.available_locales', ['cs', 'en', 'de', 'sk']);
            if (in_array($locale, $availableLocales)) {
                return $locale;
            }
        }

        // 1. From route parameter (second priority)
        if ($request->route() && $request->route()->parameter('locale')) {
            $routeLocale = $request->route()->parameter('locale');
            $availableLocales = config('app.available_locales', ['cs', 'en', 'de', 'sk']);
            if (in_array($routeLocale, $availableLocales)) {
                return $routeLocale;
            }
        }

        // 2. From URL parameter 'lang'
        if ($request->has('lang')) {
            return $request->get('lang');
        }

        // 3. From session
        if (Session::has('locale')) {
            return Session::get('locale');
        }

        // 4. From cookie
        if ($request->cookie('locale')) {
            return $request->cookie('locale');
        }

        // 5. Default language
        return config('app.locale', 'cs');
    }
}
