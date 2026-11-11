<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Http\Support\CurrentActor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Answers a missing permission with a 403 before the form request validates; the gate still checks the use
 * case the action runs (rules/http.md, Routes).
 */
final class EnsureUseCaseAllowed
{
    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
        private readonly CurrentActor $currentActor,
    ) {}

    /**
     * @param  class-string<CommandInterface<mixed>|QueryInterface<mixed>>  $useCase
     */
    public function handle(
        Request $request,
        Closure $next,
        string $useCase,
    ): Response {
        $actor = $this->currentActor->find();
        if ($actor === null || !$this->useCases->allowed($actor, $useCase)) {
            throw new AccessDeniedHttpException;
        }

        return $next($request);
    }
}
