<?php

namespace App\Infrastructure\Persistence\Eloquent\Product\Mappers;

use App\Domain\Product\DTO\ProductDTO;
use App\Models\Product;

/**
 * Maps between Eloquent Product models and Product DTOs.
 * Isolates Domain layer from Eloquent implementation details.
 */
class EloquentProductMapper
{
    /**
     * Convert Eloquent Product model to ProductDTO.
     */
    public function toDto(Product $model): ProductDTO
    {
        $tax = ($model->relationLoaded('tax') && $model->tax)
            ? ['id' => $model->tax->getAttribute('id'), 'name' => $model->tax->getAttribute('name'), 'rate' => $model->tax->getAttribute('rate')]
            : null;
        $category = ($model->relationLoaded('category') && $model->category)
            ? ['id' => $model->category->getAttribute('id'), 'name' => $model->category->getAttribute('name')]
            : null;
        $supplier = ($model->relationLoaded('supplier') && $model->supplier)
            ? ['id' => $model->supplier->getAttribute('id'), 'name' => $model->supplier->getAttribute('name')]
            : null;

        $currency = $model->getAttribute('currency') ?: 'CZK';
        $priceRaw = $model->getAttribute('price');
        $priceMoney = $priceRaw !== null
            ? \App\Domain\Shared\Money\ValueObjects\Money::fromFloat((float)$priceRaw, strtoupper($currency))
            : null;

        return ProductDTO::fromArray([
            'id' => $model->getAttribute('id'),
            'name' => $model->getAttribute('name'),
            'slug' => $model->getAttribute('slug'),
            'image' => $model->getAttribute('image'),
            'description' => $model->getAttribute('description'),
            'price' => $priceMoney,
            'tax_id' => $model->getAttribute('tax_id'),
            'currency' => $model->getAttribute('currency'),
            'unit' => $model->getAttribute('unit'),
            'tax' => $tax,
            'invoices' => $model->relationLoaded('invoices') ? $model->invoices->pluck('id')->all() : [],
            'category_id' => $model->getAttribute('category_id'),
            'category' => $category,
            'supplier_id' => $model->getAttribute('supplier_id'),
            'supplier' => $supplier,
            'is_default' => $model->getAttribute('is_default'),
            'is_active' => $model->getAttribute('is_active'),
            'user_id' => $model->getAttribute('user_id'),
            'created_at' => $model->getAttribute('created_at'),
            'updated_at' => $model->getAttribute('updated_at'),
        ]);
    }

    /**
     * Convert array of Eloquent Product models to ProductDTO array.
     * 
     * @param Product[] $products
     * @return ProductDTO[]
     */
    public function toDtoArray(array $products): array
    {
        return array_map(fn(Product $product) => $this->toDto($product), $products);
    }
}
