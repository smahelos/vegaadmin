<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Entry to the admin: backpack.access on the backpack guard, held directly or through a role.
 */
final class RequireBackpackAccess
{
    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated with backpack guard
        $auth = backpack_auth();
        if (!$auth->check()) {
            return $this->respondToUnauthorizedRequest($request, false);
        }

        $admin = Actor::admin(
            UserId::fromInt((int) $auth->id()),
        );
        if (!$this->useCases->holds($admin, Permission::BACKPACK_ACCESS)) {
            Log::warning(
                'User without backpack.access attempted to access admin area',
                [
                    'user_id' => $auth->id(),
                    'path' => $request->path(),
                ],
            );

            return $this->respondToUnauthorizedRequest($request, true);
        }

        return $next($request);
    }

    /**
     * Handle unauthorized access
     */
    private function respondToUnauthorizedRequest(Request $request, bool $isAuthenticated = false): Response
    {
        if ($request->ajax() || $request->wantsJson()) {
            $statusCode = $isAuthenticated ? 403 : 401;
            $message = $isAuthenticated ? __('users.auth.no_admin_access') : __('users.auth.unauthenticated');

            $responseFactory = response();

            return $responseFactory->json(
                [
                    'error' => $message,
                    'code' => $statusCode,
                ],
                $statusCode,
            );
        }

        if ($isAuthenticated) {
            // User is logged in but doesn't have proper roles
            // Log them out and redirect to login with error message
            $auth = backpack_auth();
            $auth->logout();

            $redirector = redirect();
            $redirect = $redirector->route('backpack.auth.login');

            return $redirect->withErrors(
                [
                    'email' => __('admin.auth.no_backpack_access'),
                ],
            );
        }

        // User is not logged in - redirect to login
        $redirector = redirect();

        return $redirector->guest(
            backpack_url('login'),
        );
    }
}
