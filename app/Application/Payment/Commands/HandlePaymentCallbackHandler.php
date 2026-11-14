<?php

declare(strict_types=1);

namespace App\Application\Payment\Commands;

use App\Application\Payment\DTO\SubscriptionPaymentResultDTO;
use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;

final class HandlePaymentCallbackHandler
{
    public function __construct(
        private readonly SubscriptionPaymentServiceInterface $payments,
    ) {}

    public function handle(HandlePaymentCallback $command): SubscriptionPaymentResultDTO
    {
        $result = $this->payments->handlePaymentCallback(
            $command->gateway,
            ['id' => $command->paymentId],
        );

        return SubscriptionPaymentResultDTO::fromArray($result);
    }
}
