<?php

namespace App\Domain\Product\Validation;

use App\Domain\Product\Exceptions\ProductValidationException;
use App\Domain\Product\ValueObjects\ProductName;
use App\Domain\Product\ValueObjects\ProductPrice;
use App\Domain\Shared\Str\Contracts\Str;

/**
 * Domain validator for Product business rules and constraints.
 */
class ProductCreationValidator
{
    public function __construct(private readonly Str $str) {}

    /**
     * Validate product data for creation.
     * 
     * @throws ProductValidationException
     */
    public function validateForCreation(array $data, int $userId): array
    {
        // Validate required fields
        if (empty($data['name'])) {
            throw ProductValidationException::invalidName($data['name'] ?? '');
        }
        
        // Validate name using Value Object
        $name = ProductName::fromString($data['name']);
        
        // Validate price if provided
        if (isset($data['price'])) {
            $price = ProductPrice::fromFloat((float) $data['price']);
            if ($price->isZero() && !($data['allow_zero_price'] ?? false)) {
                throw ProductValidationException::invalidPrice($price->getValue());
            }
        }
        
        // Validate currency
        if (!empty($data['currency']) && !$this->isValidCurrency($data['currency'])) {
            throw ProductValidationException::invalidCurrency($data['currency']);
        }
        
        return $this->normalizeProductData($data, $userId);
    }
    
    /**
     * Validate product data for updates.
     * 
     * @throws ProductValidationException
     */
    public function validateForUpdate(array $data): array
    {
        // Validate name if provided
        if (isset($data['name']) && !empty($data['name'])) {
            ProductName::fromString($data['name']);
        }
        
        // Validate price if provided
        if (isset($data['price']) && $data['price'] !== null) {
            $price = ProductPrice::fromFloat((float) $data['price']);
            if ($price->isZero() && !($data['allow_zero_price'] ?? false)) {
                throw ProductValidationException::invalidPrice($price->getValue());
            }
        }
        
        // Validate currency if provided
        if (isset($data['currency']) && !empty($data['currency']) && !$this->isValidCurrency($data['currency'])) {
            throw ProductValidationException::invalidCurrency($data['currency']);
        }
        
        return $data;
    }
    
    /**
     * Normalize product data with defaults and business rules.
     */
    private function normalizeProductData(array $data, int $userId): array
    {
        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'slug' => $data['slug'] ?? $this->str->slug($data['name']),
            'price' => isset($data['price']) ? (float) $data['price'] : 0.0,
            'currency' => $data['currency'] ?? 'CZK',
            'unit' => $data['unit'] ?? 'ks',
            'category_id' => isset($data['category_id']) ? (int) $data['category_id'] : null,
            'supplier_id' => isset($data['supplier_id']) ? (int) $data['supplier_id'] : null,
            'tax_id' => isset($data['tax_id']) ? (int) $data['tax_id'] : null,
            'image' => $data['image'] ?? null,
            'is_default' => (bool) ($data['is_default'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'user_id' => $userId,
        ];
    }
    
    /**
     * Validate currency code.
     */
    private function isValidCurrency(string $currency): bool
    {
        $validCurrencies = ['CZK', 'EUR', 'USD', 'GBP', 'PLN', 'HUF'];
        return in_array(strtoupper($currency), $validCurrencies, true);
    }
}
