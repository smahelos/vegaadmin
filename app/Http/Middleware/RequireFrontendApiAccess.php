<?php

namespace App\Http\Middleware;

use App\Domain\User\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Frontend API access middleware
 * Only works with web guard authentication
 */
class RequireFrontendApiAccess
{
    /**
     * Handle frontend API access check
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check web guard - never mix with backpack
        $guard = Auth::guard('web');
        if (!$guard->check()) {
            return $this->unauthorized($request);
        }

        $user = $guard->user();

        // Check if user has frontend API access permission
        if (!$user->checkPermissionTo(Permission::API_ACCESS, 'web')) {
            return $this->forbidden($request);
        }

        return $next($request);
    }

    /**
     * Return unauthorized response
     */
    private function unauthorized(Request $request): Response
    {
        if ($request->expectsJson() || $request->ajax()) {
            $responseFactory = response();

            return $responseFactory->json(
                [
                    'error' => __('users.auth.unauthenticated'),
                    'code' => 401,
                ],
                401,
            );
        }

        $application = app();
        $redirector = redirect();

        return $redirector->guest(
            route('frontend.login', ['locale' => $application->getLocale()]),
        );
    }

    /**
     * Return forbidden response
     */
    private function forbidden(Request $request): Response
    {
        if ($request->expectsJson() || $request->ajax()) {
            $responseFactory = response();

            return $responseFactory->json(
                [
                    'error' => __('users.auth.forbidden'),
                    'code' => 403,
                ],
                403,
            );
        }

        $application = app();
        $redirector = redirect();
        $redirect = $redirector->route('frontend.dashboard', ['locale' => $application->getLocale()]);

        return $redirect->with(
            'error',
            __('users.auth.forbidden'),
        );
    }
}
