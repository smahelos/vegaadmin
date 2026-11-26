<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Product\Repositories;

use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Product\Contracts\InvoiceProductDtoWriteRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Product\Mappers\EloquentInvoiceProductMapper;

/**
 * Eloquent implementation of InvoiceProduct DTO repository.
 * Delegates to existing EloquentInvoiceProductRepository and maps results to DTOs.
 */
final class EloquentInvoiceProductDtoRepository implements InvoiceProductDtoWriteRepositoryInterface
{
    public function __construct(
        private readonly EloquentInvoiceProductRepository $productRepository,
        private readonly EloquentInvoiceProductMapper $mapper,
    ) {}

    /**
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function bulkCreate(InvoiceId $invoiceId, array $products): void
    {
        foreach ($products as $product) {
            $this->productRepository->create(
                $this->mapper->toAttributes($invoiceId, $product),
            );
        }
    }

    public function deleteByInvoiceId(InvoiceId $invoiceId): void
    {
        $this->productRepository->deleteByInvoiceId($invoiceId->value);
    }
}
