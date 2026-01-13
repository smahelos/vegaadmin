<?php

namespace App\Application\Product\Services;

use Illuminate\Validation\ValidationException;

/**
 * Centralized validation service for Product business rules.
 * 
 * Validates business logic constraints before delegating
 * operations to the Domain layer.
 */
class ProductInputValidationService
{
    /**
     * Validate product business rules.
     */
    public function validateProductBusinessRules(array $data, int $userId, ?int $excludeProductId = null): void
    {
        $isUpdate = $excludeProductId !== null;
        $this->validateRequiredFields($data, $isUpdate);
        $this->validateDataTypes($data);
        $this->validateBusinessConstraints($data, $userId, $excludeProductId);
    }

    /**
     * Validate required fields are present.
     */
    private function validateRequiredFields(array $data, bool $isUpdate): void
    {
        // Name is required only on creation. On update, validate only if provided.
        if (!$isUpdate) {
            if (empty($data['name'])) {
                throw ValidationException::withMessages([
                    'name' => ['Product name is required.']
                ]);
            }
        } else {
            if (array_key_exists('name', $data) && empty($data['name'])) {
                throw ValidationException::withMessages([
                    'name' => ['Product name is required when provided.']
                ]);
            }
        }

        // Price is required only on creation. On update it can be omitted if not changing.
        if (!$isUpdate) {
            $hasPrice = array_key_exists('price', $data) || array_key_exists('price_money', $data) || array_key_exists('amount', $data);
            if (!$hasPrice) {
                throw ValidationException::withMessages([
                    'price' => ['Product price is required and must be non-negative.']
                ]);
            }
        }

        if (!isset($data['user_id']) || !is_numeric($data['user_id'])) {
            throw ValidationException::withMessages([
                'user_id' => ['User ID is required for product.']
            ]);
        }
    }

    /**
     * Validate data types and formats.
     */
    private function validateDataTypes(array $data): void
    {
        // Accept price in multiple shapes; validate numerically after normalization
        if (isset($data['price']) && !is_numeric($data['price'])) {
            // allow non-numeric here (string like "10.50") and defer numeric check below
        }

        if (isset($data['category_id']) && !empty($data['category_id']) && !is_numeric($data['category_id'])) {
            throw ValidationException::withMessages([
                'category_id' => ['Category ID must be a valid number.']
            ]);
        }

        if (isset($data['supplier_id']) && !empty($data['supplier_id']) && !is_numeric($data['supplier_id'])) {
            throw ValidationException::withMessages([
                'supplier_id' => ['Supplier ID must be a valid number.']
            ]);
        }

        if (isset($data['tax_id']) && !empty($data['tax_id']) && !is_numeric($data['tax_id'])) {
            throw ValidationException::withMessages([
                'tax_id' => ['Tax ID must be a valid number.']
            ]);
        }
    }

    /**
     * Validate business constraints.
     */
    private function validateBusinessConstraints(array $data, int $userId, ?int $excludeProductId = null): void
    {
        // Validate name length
        if (isset($data['name']) && strlen($data['name']) > 255) {
            throw ValidationException::withMessages([
                'name' => ['Product name must not exceed 255 characters.']
            ]);
        }

        // Validate description length
        if (isset($data['description']) && strlen($data['description']) > 1000) {
            throw ValidationException::withMessages([
                'description' => ['Product description must not exceed 1000 characters.']
            ]);
        }

        // Validate price constraints
        if (isset($data['price']) || isset($data['price_money']) || isset($data['amount'])) {
            if (isset($data['price_money']['amount'])) {
                $price = (float) $data['price_money']['amount'];
            } elseif (isset($data['amount'])) {
                $price = (float) str_replace([',',' '], ['.',''], (string)$data['amount']);
            } else {
                $price = (float) $data['price'];
            }
            if ($price < 0) {
                throw ValidationException::withMessages([
                    'price' => ['Product price cannot be negative.']
                ]);
            }
            if ($price > 999999.99) {
                throw ValidationException::withMessages([
                    'price' => ['Product price cannot exceed 999,999.99.']
                ]);
            }
        }

        // Validate user ownership
        if (isset($data['user_id']) && (int) $data['user_id'] !== $userId) {
            throw ValidationException::withMessages([
                'user_id' => ['Product cannot be assigned to a different user.']
            ]);
        }
    }
}
