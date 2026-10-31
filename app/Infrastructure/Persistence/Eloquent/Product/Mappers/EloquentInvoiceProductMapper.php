<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Product\Mappers;

use App\Domain\Invoice\DTO\InvoiceLineAmountDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Models\InvoiceProduct;

/**
 * Maps between Eloquent InvoiceProduct models and InvoiceProduct DTOs.
 * Isolates Domain layer from Eloquent implementation details.
 */
final class EloquentInvoiceProductMapper
{
    /**
     * @return array<string, mixed>
     */
    public function toAttributes(InvoiceId $invoiceId, InvoiceProductWriteData $data): array
    {
        return [
            'invoice_id' => $invoiceId->getValue(),
            'product_id' => $data->productId?->getValue(),
            'name' => $data->name,
            'quantity' => $data->quantity,
            'unit' => $data->unit->value,
            'category' => $data->category,
            'description' => $data->description,
            'price' => $data->price->getAmount(),
            'currency' => $data->price->getCurrency(),
            'tax_rate' => $data->taxRate,
            'tax_amount' => $data->taxAmount()->getAmount(),
            'total_price' => $data->total()->getAmount(),
            'is_custom_product' => $data->isCustomProduct(),
        ];
    }

    public function toAmounts(InvoiceProduct $line): InvoiceLineAmountDTO
    {
        return new InvoiceLineAmountDTO(
            Money::fromString((string) $line->total_price, $line->currency),
            Money::fromString((string) $line->tax_amount, $line->currency),
        );
    }
}
