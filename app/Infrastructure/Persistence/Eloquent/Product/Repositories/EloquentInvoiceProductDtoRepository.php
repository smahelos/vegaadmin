<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Product\Repositories;

use App\Domain\Invoice\DTO\InvoiceLineAmountDTO;
use App\Domain\Invoice\DTO\InvoiceProductDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Product\Contracts\InvoiceProductDtoReadRepositoryInterface;
use App\Domain\Product\Contracts\InvoiceProductDtoWriteRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Product\Mappers\EloquentInvoiceProductMapper;
use App\Models\InvoiceProduct;

/**
 * Eloquent implementation of InvoiceProduct DTO repository.
 * Delegates to existing EloquentInvoiceProductRepository and maps results to DTOs.
 */
final class EloquentInvoiceProductDtoRepository implements InvoiceProductDtoReadRepositoryInterface, InvoiceProductDtoWriteRepositoryInterface
{
    public function __construct(
        private readonly EloquentInvoiceProductRepository $productRepository,
        private readonly EloquentInvoiceProductMapper $mapper,
    ) {}

    public function create(InvoiceId $invoiceId, InvoiceProductWriteData $data): InvoiceProductDTO
    {
        $product = $this->productRepository->create($this->mapper->toAttributes($invoiceId, $data))->toArray();

        return InvoiceProductDTO::fromArray($product);
    }

    /**
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function bulkCreate(InvoiceId $invoiceId, array $products): void
    {
        foreach ($products as $product) {
            $this->productRepository->create($this->mapper->toAttributes($invoiceId, $product));
        }
    }

    public function deleteByInvoiceId(InvoiceId $invoiceId): void
    {
        $this->productRepository->deleteByInvoiceId($invoiceId->getValue());
    }

    public function amountsForInvoice(InvoiceId $invoiceId): array
    {
        return InvoiceProduct::query()->where('invoice_id', $invoiceId->getValue())->get()
            ->map(fn (InvoiceProduct $line): InvoiceLineAmountDTO => $this->mapper->toAmounts($line))->all();
    }
}
