<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Domain\Payment\Contracts\PaymentDtoReadRepositoryInterface;
use App\Domain\Payment\DTO\PaymentDTO;
use App\Infrastructure\Persistence\Eloquent\Payment\Mappers\EloquentPaymentMapper;

class EloquentPaymentDtoReadRepository implements PaymentDtoReadRepositoryInterface
{
    public function __construct(
        private readonly EloquentPaymentReadRepository $paymentReadRepository,
        private readonly EloquentPaymentMapper $mapper,
    ) {}

    public function findByGatewayPaymentIdForUpdate(string $gateway, ?string $gatewayPaymentId): ?PaymentDTO
    {
        if (!$gatewayPaymentId) {
            return null;
        }
        $payment = $this->paymentReadRepository->findByGatewayPaymentIdForUpdate($gateway, $gatewayPaymentId);

        return $payment ? $this->mapper->toDto($payment) : null;
    }

    public function findPendingBySubscription(int $subscriptionId): array
    {
        $payment = $this->paymentReadRepository->findPendingBySubscription($subscriptionId);

        return array_map(
            fn ($p) => $this->mapper->toDto($p),
            $payment,
        );
    }
}
