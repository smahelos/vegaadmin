<?php

namespace App\Application\Product\Mappers;

use App\Domain\Product\DTO\ProductDTO;
use Illuminate\Support\Collection;

/**
 * Maps Product DTOs to arrays for Application layer response formatting.
 * Standardizes the output format for controllers and API responses.
 */
class ProductArrayMapper
{
    /**
     * Map single ProductDTO to array.
     */
    public function mapToArray(ProductDTO $product): array
    {
        // Defensive extraction with fallbacks to keep mapper tolerant to DTO changes
        $categoryName = is_array($product->category ?? null) ? ($product->category['name'] ?? null) : ($product->category_name ?? null);
        $supplierName = is_array($product->supplier ?? null) ? ($product->supplier['name'] ?? null) : ($product->supplier_name ?? null);
        $taxName = is_array($product->tax ?? null) ? ($product->tax['name'] ?? null) : ($product->tax_name ?? null);
        $taxRate = is_array($product->tax ?? null) ? ($product->tax['rate'] ?? null) : ($product->tax_rate ?? null);
        $productImage = $product->image ?? ($product->product_image ?? null);
        $invoiceItems = $product->invoices ?? ($product->invoice_items ?? []);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'slug' => $product->slug,
            'product_image' => $productImage,
            // Backward compatible numeric price for UI code expecting float
            'price' => $product->price?->toFloat(),
            // Rich money representation for modern consumers
            'price_money' => $product->price ? [
                'amount' => $product->price->getAmount(),
                'currency' => $product->price->getCurrency(),
            ] : null,
            'unit' => $product->unit,
            'category_id' => $product->category_id,
            'category_name' => $categoryName,
            'supplier_id' => $product->supplier_id,
            'supplier_name' => $supplierName,
            'tax_id' => $product->tax_id,
            'tax_name' => $taxName,
            'tax_rate' => $taxRate,
            'user_id' => $product->user_id,
            'is_default' => $product->is_default,
            'created_at' => $product->created_at,
            'invoice_items' => $invoiceItems,
        ];
    }

    /**
     * Map collection of ProductDTOs to array of arrays.
     */
    public function mapCollectionToArray(Collection $products): array
    {
        return $products->map(fn(ProductDTO $product) => $this->mapToArray($product))->all();
    }

    /**
     * Map single ProductDTO to simplified array for dropdowns.
     */
    public function mapToDropdownArray(ProductDTO $product): array
    {
        return [
            'id' => $product->id,
            'text' => $product->name,
            'price' => $product->price,
        ];
    }

    /**
     * Map collection of ProductDTOs to dropdown array.
     */
    public function mapCollectionToDropdownArray(Collection $products): array
    {
        return $products->map(fn(ProductDTO $product) => $this->mapToDropdownArray($product))->all();
    }
}
