<?php

namespace App\Domain\Product\DTO;


/**
 * Immutable write data for Product mutations.
 * All fields are optional to support partial updates; null means "do not change".
 */
class ProductWriteData
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?string $slug = null,
        public readonly mixed $image = null, // Can be string path or UploadedFile
        /** Price can be float or normalized decimal string */
        public readonly string|float|null $price = null,
        public readonly ?string $unit = null,
        public readonly ?int $category_id = null,
        public readonly ?int $supplier_id = null,
        public readonly ?string $currency = null,
        public readonly ?int $tax_id = null,
        public readonly ?int $user_id = null,
        public readonly bool $is_default = false,
        public readonly bool $is_active = false,
    ) {}

    /**
     * Create from associative array.
     */
    public static function fromArray(array $data): self
    {
        // Normalize price + currency from multiple accepted shapes
        $normPrice = null; // string|float|null
        $normCurrency = $data['currency'] ?? null;

        // Case 1: price as Money VO (domain type)
        if (isset($data['price']) && $data['price'] instanceof \App\Domain\Shared\Money\ValueObjects\Money) {
            $normPrice = self::normalizeAmountString($data['price']->getAmount()); // force 2 decimals
            $normCurrency = $data['price']->getCurrency();
        }

        // Case 2: price_money array: ['amount'=>string|float, 'currency'=>string]
        if ($normPrice === null && isset($data['price_money']) && is_array($data['price_money'])) {
            $pm = $data['price_money'];
            if (isset($pm['amount'])) {
                $normPrice = is_string($pm['amount'])
                    ? self::normalizeAmountString($pm['amount'])
                    : number_format((float)$pm['amount'], 2, '.', '');
            }
            if (isset($pm['currency']) && is_string($pm['currency'])) {
                $normCurrency = strtoupper($pm['currency']);
            }
        }

        // Case 3: amount+currency at top-level
        if ($normPrice === null && isset($data['amount'])) {
            $normPrice = is_string($data['amount'])
                ? self::normalizeAmountString($data['amount'])
                : number_format((float)$data['amount'], 2, '.', '');
        }
        if ($normCurrency === null && isset($data['amount']) && isset($data['currency']) && is_string($data['currency'])) {
            $normCurrency = strtoupper($data['currency']);
        }

        // Case 4: legacy price float/string + optional currency
        if ($normPrice === null && isset($data['price']) && !$data['price'] instanceof \App\Domain\Shared\Money\ValueObjects\Money) {
            $normPrice = is_string($data['price'])
                ? self::normalizeAmountString($data['price'])
                : number_format((float)$data['price'], 2, '.', '');
        }

        // Final normalization: ensure currency uppercase if provided
        if (is_string($normCurrency)) {
            $normCurrency = strtoupper($normCurrency);
        }

        return new self(
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            slug: $data['slug'] ?? null,
            image: $data['image'] ?? null,
            price: $normPrice,
            unit: $data['unit'] ?? null,
            category_id: isset($data['category_id']) ? (int) $data['category_id'] : null,
            supplier_id: isset($data['supplier_id']) ? (int) $data['supplier_id'] : null,
            currency: $normCurrency,
            tax_id: isset($data['tax_id']) ? (int) $data['tax_id'] : null,
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            is_default: (bool) ($data['is_default'] ?? false),
            is_active: (bool) ($data['is_active'] ?? false),
        );
    }

    /**
     * Normalize string amount into canonical decimal with dot and max 2 places.
     */
    private static function normalizeAmountString(string $amount): string
    {
        $amount = str_replace([' ', ','], ['', '.'], trim($amount));
        if (!preg_match('/^\d+(?:\.\d+)?$/', $amount)) {
            // Fallback - cast to float and format
            return number_format((float)$amount, 2, '.', '');
        }
        // Limit to 2 decimals for storage
        if (str_contains($amount, '.')) {
            [$i, $f] = explode('.', $amount, 2);
            $f = substr($f, 0, 2);
            return $i . '.' . str_pad($f, 2, '0', STR_PAD_RIGHT);
        }
        return $amount;
    }

    /**
     * Convert to associative array for infrastructure layer usage.
     * Filters out null values to avoid overwriting existing data with null during updates.
     */
    public function toArray(): array
    {
        $properties = get_object_vars($this);
        $filtered = array_filter($properties, fn($value) => $value !== null);
        
        return $filtered;
    }
}
