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
 * Backpack API access middleware
 * Only works with backpack guard authentication
 */
class RequireBackpackApiAccess
{
    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
    ) {}

    /**
     * Handle backpack API access check
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check backpack guard - never mix with web
        $auth = backpack_auth();
        if (!$auth->check()) {
            $defaultAuth = auth();
            Log::warning(
                'Backpack authentication failed',
                [
                    'path' => $request->path(),
                    'backpack_check' => $auth->check(),
                    'laravel_auth_check' => $defaultAuth->check(),
                ],
            );

            return $this->unauthorized($request);
        }

        $admin = Actor::admin(
            UserId::fromInt((int) $auth->id()),
        );
        if (!$this->useCases->holds($admin, Permission::API_ACCESS)) {
            Log::warning(
                'Backpack user lacks API access permission',
                [
                    'user_id' => $auth->id(),
                    'path' => $request->path(),
                    'checked_permission' => Permission::API_ACCESS->value,
                    'checked_guard' => 'backpack',
                ],
            );

            return $this->forbidden($request);
        }

        // Like Backpack's admin middleware, so the gate's actor on these routes is the admin (CurrentActor)
        $defaultAuth = auth();
        $defaultAuth->shouldUse(
            backpack_guard_name(),
        );

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
                    'error' => __('backpack::base.unauthorized'),
                    'code' => 401,
                ],
                401,
            );
        }

        $redirector = redirect();

        return $redirector->guest(
            backpack_url('login'),
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
                    'error' => __('backpack::crud.unauthorized_access'),
                    'code' => 403,
                ],
                403,
            );
        }

        // Redirect to dashboard URL (backpack_url already returns full path)
        $redirect = redirect(
            backpack_url('dashboard'),
        );

        return $redirect->with(
            'error',
            __('backpack::crud.unauthorized_access'),
        );
    }
}
