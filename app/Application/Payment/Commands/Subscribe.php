<?php

declare(strict_types=1);

namespace App\Application\Payment\Commands;

use App\Application\Payment\DTO\SubscriptionPaymentResultDTO;
use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CallsOutsideDatabaseInterface;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * Creates a pending subscription of the plan and starts its payment at the gateway, so the gate opens no transaction.
 *
 * @implements CommandInterface<SubscriptionPaymentResultDTO>
 */
final readonly class Subscribe extends OwnedUseCase implements CallsOutsideDatabaseInterface, CommandInterface
{
    public function __construct(
        UserId $owner,
        public int $planId,
        public string $gateway,
        public string $returnUrl,
        public string $notifyUrl,
        public string $language,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::SUBSCRIPTION_CREATE);
    }
}
