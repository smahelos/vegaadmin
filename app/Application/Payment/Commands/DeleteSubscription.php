<?php

declare(strict_types=1);

namespace App\Application\Payment\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Payment\ValueObjects\SubscriptionId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/** @implements CommandInterface<null> */
final readonly class DeleteSubscription extends OwnedUseCase implements CommandInterface
{
    public function __construct(
        UserId $owner,
        public SubscriptionId $id,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::SUBSCRIPTION_DELETE);
    }
}
