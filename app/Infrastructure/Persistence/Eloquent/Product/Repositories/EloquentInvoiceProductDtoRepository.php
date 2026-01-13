<?php

namespace App\Infrastructure\Persistence\Eloquent\Product\Repositories;

use App\Domain\Invoice\DTO\InvoiceProductDTO;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Product\Contracts\InvoiceProductDtoReadRepositoryInterface;
use App\Domain\Product\Contracts\InvoiceProductDtoWriteRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Product\Mappers\EloquentInvoiceProductMapper;

/**
 * Eloquent implementation of InvoiceProduct DTO repository.
 * Delegates to existing EloquentInvoiceProductRepository and maps results to DTOs.
 */
class EloquentInvoiceProductDtoRepository implements InvoiceProductDtoReadRepositoryInterface, InvoiceProductDtoWriteRepositoryInterface
{
    public function __construct(
        private readonly EloquentInvoiceProductRepository $productRepository,
        private readonly EloquentInvoiceProductMapper $mapper,
    ) {}

    /**
     * @return array<int, InvoiceProductDTO>
     */
    public function allForInvoice(InvoiceId $invoiceId): array
    {
        // Normalize invoice id in case raw int is provided by callers
        $invoiceIdValue = $invoiceId instanceof InvoiceId ? $invoiceId->getValue() : (int) $invoiceId;

        $products = $this->productRepository->allForInvoice($invoiceIdValue);
        // productRepository returns array; map each row to DTO via model reconstruction isn't needed here
        // but we prefer mapping from model to keep consistency, so adjust repository to return models in future if needed
        // For now, convert array rows to DTO using fromArray normalization
        return array_map(function (array $row) {
            // Normalize monetary fields to Money via DTO factory
            return InvoiceProductDTO::fromArray($row);
        }, $products);
    }

    public function create(array $data): InvoiceProductDTO
    {
        $product = $this->productRepository->create($data)->toArray();
        return InvoiceProductDTO::fromArray($product);
    }

    public function bulkCreate(InvoiceId $invoiceId, array $products): void
    {
        $this->productRepository->bulkCreate($invoiceId->getValue(), $products);
    }

    public function deleteByInvoiceId(InvoiceId $invoiceId): void
    {
        $this->productRepository->deleteByInvoiceId($invoiceId->getValue());
    }

    public function listForInvoiceWithProduct(InvoiceId $invoiceId): array
    {
        return $this->productRepository->listForInvoiceWithProduct($invoiceId->getValue());
    }
}
