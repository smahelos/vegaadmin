<?php

declare(strict_types=1);

namespace App\Application\Payment\Commands;

use App\Application\Payment\DTO\SubscriptionPaymentResultDTO;
use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CallsOutsideDatabaseInterface;
use App\Application\Shared\UseCase\Contracts\CommandInterface;

/**
 * The gateway's return or notification. Public: only the payment id is taken from the request, the gateway is asked
 * for its state and the owner comes from the stored payment. Asks the gateway, so the gate opens no transaction.
 *
 * @implements CommandInterface<SubscriptionPaymentResultDTO>
 */
final readonly class HandlePaymentCallback implements CallsOutsideDatabaseInterface, CommandInterface
{
    public function __construct(
        public string $gateway,
        public ?string $paymentId,
    ) {}

    public static function access(): Access
    {
        return Access::public();
    }
}
