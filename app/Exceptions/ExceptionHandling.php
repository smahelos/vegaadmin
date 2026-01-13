<?php

namespace App\Exceptions;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use Throwable;
use Illuminate\Auth\AuthenticationException;

class ExceptionHandling
{
    /**
     * Register central exception render callbacks.
     */
    public static function registerCallbacks(Exceptions $exceptions): void
    {
        // Invoice model not found
        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if (self::isInvoiceRoute($request)) {
                return redirect()->route('frontend.invoices', ['locale' => app()->getLocale()])
                    ->with('error', __('invoices.messages.not_found'));
            }
            return null;
        });

        // Entity limit exceeded
        $exceptions->render(function (EntityLimitExceededException $e, $request) {
            // JSON first: standardized payload with translated message
            if ($request->expectsJson()) {
                $translated = __($e->getMessageKey(), $e->getContext());
                return response()->json([
                    'message' => $translated,
                    'message_key' => $e->getMessageKey(),
                    'context' => $e->getContext(),
                    'limit' => $e->getLimitData(),
                ], 403);
            }
            // Route-specific UX: keep existing shortcuts for common flows
            if (self::isInvoiceRoute($request)) {
                return back()->withInput()->with('error', __('invoices.messages.limit_exceeded'));
            }
            if (self::isSupplierRoute($request)) {
                return back()->withInput()->with('error', __('suppliers.messages.limit_exceeded'));
            }
            // Generic redirect with translated key from exception
            return back()->withInput()->with('error', __($e->getMessageKey(), $e->getContext()));
        });

        // Authentication failure on invoice routes
        // Web requests should redirect to localized frontend login, JSON should get 401
        $exceptions->render(function (AuthenticationException $e, $request) {
            if (self::isInvoiceRoute($request)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => __('users.auth.unauthenticated'),
                        'code' => 401,
                    ], 401);
                }
                // Let guests be redirected to the correct localized login page
                return redirect()->guest(route('frontend.login', ['locale' => app()->getLocale()]));
            }
            return null;
        });

        // Generic invoice fallback (non JSON guest store)
        $exceptions->render(function (Throwable $e, $request) {
            if (self::isInvoiceRoute($request)) {
                if ($e instanceof ModelNotFoundException || $e instanceof EntityLimitExceededException) {
                    return null; // handled above
                }
                // Let Laravel handle validation exceptions to preserve session error bag
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return null;
                }
                // Skip handling for guest JSON store route (correct route name with dots)
                if ($request->routeIs('frontend.invoice.storeGuest') || $request->routeIs('frontend.invoice.store.guest')) {
                    return null; // controller JSON handling
                }
                return redirect()->back()->with('error', __('invoices.messages.update_error'));
            }
            return null;
        });
    }

    private static function isInvoiceRoute($request): bool
    {
        $routeName = $request->route()?->getName();
        if (!$routeName) { return false; }
        return str_starts_with($routeName, 'frontend.invoice') || $routeName === 'frontend.invoices';
    }

    private static function isSupplierRoute($request): bool
    {
        $routeName = $request->route()?->getName();
        if (!$routeName) { return false; }
        return str_starts_with($routeName, 'frontend.supplier') || $routeName === 'frontend.suppliers';
    }
}
