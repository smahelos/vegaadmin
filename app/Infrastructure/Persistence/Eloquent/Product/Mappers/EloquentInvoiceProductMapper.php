<?php

namespace App\Infrastructure\Persistence\Eloquent\Product\Mappers;

use App\Domain\Invoice\DTO\InvoiceProductDTO;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Models\InvoiceProduct;

/**
 * Maps between Eloquent InvoiceProduct models and InvoiceProduct DTOs.
 * Isolates Domain layer from Eloquent implementation details.
 */
class EloquentInvoiceProductMapper
{
    /**
     * Convert Eloquent InvoiceProduct model to InvoiceProductDTO.
     */
    public function toDto(InvoiceProduct $model): InvoiceProductDTO
    {
        $currency = $model->getAttribute('currency') ?: 'CZK';
        $priceMoney = Money::fromString(number_format((float)$model->getAttribute('price'), 2, '.', ''), strtoupper($currency));
        $taxMoney = Money::fromString(number_format((float)$model->getAttribute('tax_amount'), 2, '.', ''), strtoupper($currency));
        $totalMoney = Money::fromString(number_format((float)$model->getAttribute('total_price'), 2, '.', ''), strtoupper($currency));

        return InvoiceProductDTO::fromArray([
            'id' => $model->getAttribute('id'),
            'invoice_id' => $model->getAttribute('invoice_id'),
            'product_id' => $model->getAttribute('product_id'),
            'name' => $model->getAttribute('name'),
            'quantity' => $model->getAttribute('quantity'),
            'unit' => $model->getAttribute('unit'),
            'category' => $model->getAttribute('category'),
            'description' => $model->getAttribute('description'),
            'price' => $priceMoney,
            'currency' => $currency,
            'tax_rate' => $model->getAttribute('tax_rate') ?? 0.0,
            'tax_amount' => $taxMoney,
            'total_price' => $totalMoney,
            'is_custom_product' => (bool)$model->getAttribute('is_custom_product'),
            'created_at' => $model->getAttribute('created_at'),
            'updated_at' => $model->getAttribute('updated_at'),
        ]);
    }

    /**
     * Convert array of Eloquent InvoiceProduct models to InvoiceProductDTO array.
     *
     * @param InvoiceProduct[] $products
     * @return InvoiceProductDTO[]
     */
    public function toDtoArray(array $products): array
    {
        return array_map(fn(InvoiceProduct $product) => $this->toDto($product), $products);
    }
}
