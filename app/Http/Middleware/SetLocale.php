<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request and set application locale
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Get language from URL parameter 'lang', session, cookie or default
            if ($request->has('lang')) {
                $lang = $request->get('lang');
            } elseif (Session::has('locale')) {
                $lang = Session::get('locale');
            } elseif ($request->cookie('locale')) {
                $lang = $request->cookie('locale');
            } else {
                // Use default language
                $lang = config('app.locale');
            }
    
            // Verify that language is supported
            if (in_array($lang, config('app.available_locales', ['cs', 'en', 'de', 'sk']))) {
                App::setLocale($lang);
                Session::put('locale', $lang);
                cookie('locale', $lang, 60 * 24 * 30); // 30 days
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
}