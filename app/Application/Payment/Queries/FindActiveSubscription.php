<?php

declare(strict_types=1);

namespace App\Application\Payment\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Payment\DTO\SubscriptionDTO;
use App\Domain\User\Enums\Permission;

/** @implements QueryInterface<SubscriptionDTO|null> */
final readonly class FindActiveSubscription extends OwnedUseCase implements QueryInterface
{
    public static function access(): Access
    {
        return Access::permission(Permission::SUBSCRIPTION_VIEW);
    }
}
