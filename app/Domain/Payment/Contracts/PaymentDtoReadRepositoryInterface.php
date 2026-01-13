<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\DTO\PaymentDTO;

/**
 * Read-only operations for Payment aggregate.
 */
interface PaymentDtoReadRepositoryInterface
{
    public function findById(int $id): ?PaymentDTO;

    public function findByGatewayPaymentId(?string $gatewayPaymentId): ?PaymentDTO;
    
    /** @return array<int,PaymentDTO> */
    public function findPendingBySubscription(int $subscriptionId): array;
}
