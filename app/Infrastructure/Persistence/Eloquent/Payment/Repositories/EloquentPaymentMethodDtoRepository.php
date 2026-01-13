<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Payment\Contracts\PaymentMethodDtoWriteRepositoryInterface;
use App\Domain\Payment\DTO\PaymentMethodDTO;
use App\Domain\Payment\DTO\PaymentMethodWriteData;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Infrastructure\Persistence\Eloquent\Payment\Mappers\EloquentPaymentMethodMapper;
use App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentMethodRepository;

/**
 * Eloquent implementation of PaymentMethod DTO repository.
 * Delegates to existing EloquentPaymentMethodRepository and maps results to DTOs.
 */
class EloquentPaymentMethodDtoRepository implements PaymentMethodDtoReadRepositoryInterface, PaymentMethodDtoWriteRepositoryInterface
{
    public function __construct(
        private readonly EloquentPaymentMethodRepository $paymentMethodRepository,
        private readonly EloquentPaymentMethodMapper $mapper
    ) {
    }

    public function all(): array
    {
        $paymentMethods = $this->paymentMethodRepository->all();

        return $paymentMethods->map(fn($paymentMethod) => $this->mapper->toDto($paymentMethod))->toArray();
    }

    public function getAllForDropdown(): array
    {
        return $this->paymentMethodRepository->getAllForDropdown();
    }

    public function findById(PaymentMethodId $id): ?PaymentMethodDTO
    {
        $paymentMethod = $this->paymentMethodRepository->findById($id->getValue());
        
        return $paymentMethod ? $this->mapper->toDto($paymentMethod) : null;
    }

    public function findBySlug(string $slug): ?PaymentMethodDTO
    {
        $paymentMethod = $this->paymentMethodRepository->findBySlug($slug);
        
        return $paymentMethod ? $this->mapper->toDto($paymentMethod) : null;
    }

    public function create(PaymentMethodWriteData $writeData): PaymentMethodDTO
    {
        $eloquentPaymentMethod = $this->paymentMethodRepository->create($writeData->toArray());
        
        return $this->mapper->toDto($eloquentPaymentMethod);
    }

    public function update(PaymentMethodId $id, PaymentMethodWriteData $writeData): PaymentMethodDTO
    {
        $eloquentPaymentMethod = $this->paymentMethodRepository->updateById($id->getValue(), $writeData->toArray());
        
        return $this->mapper->toDto($eloquentPaymentMethod);
    }

    public function delete(PaymentMethodId $id): void
    {
        $this->paymentMethodRepository->deleteById($id->getValue());
    }
}
