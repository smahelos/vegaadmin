<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Domain\Payment\Contracts\PaymentDtoWriteRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Payment\Mappers\EloquentPaymentMapper;
use App\Domain\Payment\DTO\PaymentDTO;
use App\Domain\Payment\DTO\PaymentWriteData;

class EloquentPaymentDtoWriteRepository implements PaymentDtoWriteRepositoryInterface
{
    public function __construct(
        private readonly EloquentPaymentWriteRepository $paymentWriteRepository,
        private readonly EloquentPaymentMapper $mapper
    ) {
    }

    public function create(PaymentWriteData $data): PaymentDTO
    {
        $attributes = $data->toModelAttributes();
        return $this->mapper->toDto(
            $this->paymentWriteRepository->create($attributes)
        );
    }

    public function updateById(int $paymentId, PaymentWriteData $data): PaymentDTO
    {
        $attributes = $data->toModelAttributes();
        $m = $this->paymentWriteRepository->updateById($paymentId, $attributes);
        return $m ? $this->mapper->toDto($m) : null;
    }

    public function applyRefund(int $paymentId, float $amount): bool
    {
        return $this->paymentWriteRepository->applyRefund($paymentId, $amount);
    }
}
