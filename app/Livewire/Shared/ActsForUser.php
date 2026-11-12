<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

/**
 * The signed-in frontend user of a component. Every action checks its permission again, because Livewire calls
 * an action without passing the route middleware of the page.
 */
trait ActsForUser
{
    protected function userId(): UserId
    {
        $id = Auth::id();
        abort_if($id === null, 403);

        return UserId::fromInt((int) $id);
    }

    /**
     * Runs the use case through the gate as the signed-in user.
     *
     * @template TResult
     *
     * @param  CommandInterface<TResult>|QueryInterface<TResult>  $useCase
     * @return TResult
     */
    protected function runUseCase(CommandInterface|QueryInterface $useCase): mixed
    {
        $useCases = App::make(AuthorizingUseCaseRunnerInterface::class);

        return $useCases->run(
            Actor::user($this->userId()),
            $useCase,
        );
    }

    /**
     * A 403 before validation or rendering. Asked first while a page renders the component: a refusal thrown there
     * would reach the browser as a view error.
     *
     * @param  class-string<CommandInterface<mixed>|QueryInterface<mixed>>  $useCase
     */
    protected function requireAllowed(string $useCase): void
    {
        abort_unless(
            $this->isAllowed($useCase),
            403,
        );
    }

    /**
     * Only for hiding what the user cannot do; the gate is still the check.
     *
     * @param  class-string<CommandInterface<mixed>|QueryInterface<mixed>>  $useCase
     */
    protected function isAllowed(string $useCase): bool
    {
        $useCases = App::make(AuthorizingUseCaseRunnerInterface::class);

        return $useCases->allowed(
            Actor::user($this->userId()),
            $useCase,
        );
    }
}
