<?php

namespace App\Domain\Product\Factories;

use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Domain\Product\ValueObjects\ProductPrice;
use App\Domain\Shared\Str\Contracts\Str;

/**
 * Domain Factory for creating Product DTOs from raw data.
 * Provides consistent object creation with validation.
 */
class ProductDtoFactory
{
    public function __construct(private readonly Str $str) {}

    /**
     * Create ProductWriteData from array with domain validation.
     */
    public static function createWriteData(array $data): ProductWriteData
    {
        // Apply domain validation through Value Objects
        $validatedData = $data;
        
        if (isset($data['name'])) {
            $name = ProductName::fromString($data['name']);
            $validatedData['name'] = $name->getValue();
        }
        
        if (isset($data['price']) && $data['price'] !== null) {
            $price = ProductPrice::fromFloat((float) $data['price']);
            $validatedData['price'] = $price->getValue();
        }
        
        return ProductWriteData::fromArray($validatedData);
    }

    /**
     * Create ProductWriteData for product updates with partial data.
     */
    public static function createUpdateData(array $data, ?ProductDTO $existing = null): ProductWriteData
    {
        return self::createWriteData($data);
    }

    /**
     * Create default product data for first product.
     */
    public static function createDefaultProduct(array $data, int $userId): ProductWriteData
    {
        return self::createWriteData(array_merge($data, [
            'is_default' => true,
            'user_id' => $userId,
        ]));
    }

    /**
     * Create product data with generated slug.
     */
    public function createWithSlug(array $data): ProductWriteData
    {
        if (!empty($data['name']) && empty($data['slug'])) {
            $data['slug'] = $this->str->slug($data['name']);
        }
        
        return self::createWriteData($data);
    }
}
