<?php

declare(strict_types=1);

namespace App\Http\Support;

use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Enums\ActorKind;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

/**
 * The signed-in user of the current default guard as an Actor; the Backpack middleware switches the default
 * guard to backpack on admin routes.
 */
final class CurrentActor
{
    public function __construct(
        private readonly AuthFactory $auth,
    ) {}

    public function find(): ?Actor
    {
        $guard = $this->auth->guard();
        $id = $guard->id();
        $kind = ActorKind::fromGuard($this->auth->getDefaultDriver());
        if ($id === null || $kind === null) {
            return null;
        }
        $user = UserId::fromInt((int) $id);

        return match ($kind) {
            ActorKind::USER => Actor::user($user),
            ActorKind::ADMIN => Actor::admin($user),
            ActorKind::ANONYMOUS, ActorKind::SYSTEM => null,
        };
    }
}
