<?php

namespace App\Http\Middleware;

use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Frontend API access middleware
 * Only works with web guard authentication
 */
class RequireFrontendApiAccess
{
    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
    ) {}

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

        $user = Actor::user(
            UserId::fromInt((int) $guard->id()),
        );
        if (!$this->useCases->holds($user, Permission::API_ACCESS)) {
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

        $redirector = redirect();

        return $redirector->guest(
            route('frontend.login', ['locale' => App::getLocale()]),
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

        $redirector = redirect();
        $redirect = $redirector->route('frontend.dashboard', ['locale' => App::getLocale()]);

        return $redirect->with(
            'error',
            __('users.auth.forbidden'),
        );
    }
}
