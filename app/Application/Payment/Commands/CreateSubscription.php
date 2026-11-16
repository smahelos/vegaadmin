<?php

declare(strict_types=1);

namespace App\Application\Payment\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Payment\DTO\SubscriptionDTO;
use App\Domain\Payment\DTO\SubscriptionWriteData;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * A subscription the admin enters without a payment.
 *
 * @implements CommandInterface<SubscriptionDTO>
 */
final readonly class CreateSubscription extends OwnedUseCase implements CommandInterface
{
    public function __construct(
        UserId $owner,
        public SubscriptionWriteData $data,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permissionWithOwner(Permission::SUBSCRIPTION_CREATE);
    }
}
