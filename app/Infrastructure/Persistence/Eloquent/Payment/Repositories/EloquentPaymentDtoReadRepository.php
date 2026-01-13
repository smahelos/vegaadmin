<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Domain\Payment\Contracts\PaymentDtoReadRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Payment\Mappers\EloquentPaymentMapper;
use App\Domain\Payment\DTO\PaymentDTO;

class EloquentPaymentDtoReadRepository implements PaymentDtoReadRepositoryInterface
{
    public function __construct(
        private readonly EloquentPaymentReadRepository $paymentReadRepository,
        private readonly EloquentPaymentMapper $mapper
    ) {
    }

    public function findById(int $id): ?PaymentDTO
    {
        $payment = $this->paymentReadRepository->findById($id);
        return $payment ? $this->mapper->toDto($payment) : null;
    }

    public function findByGatewayPaymentId(?string $gatewayPaymentId): ?PaymentDTO
    {
        if (!$gatewayPaymentId) return null;
        $payment = $this->paymentReadRepository->findByGatewayPaymentId($gatewayPaymentId);
        return $payment ? $this->mapper->toDto($payment) : null;
    }

    public function findPendingBySubscription(int $subscriptionId): array
    {
        $payment = $this->paymentReadRepository->findPendingBySubscription($subscriptionId);
        return array_map(fn($p) => $this->mapper->toDto($p), $payment);
    }
}
