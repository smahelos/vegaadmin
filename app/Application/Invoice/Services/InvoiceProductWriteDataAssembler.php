<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services;

use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;

/**
 * Builds invoice lines from items that InvoiceRequest has already validated
 * (numbers in range, unit and currency known, product owned by the user).
 */
final class InvoiceProductWriteDataAssembler
{
    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<InvoiceProductWriteData>
     */
    public function fromValidated(array $items): array
    {
        return array_map(fn (array $item): InvoiceProductWriteData => $this->line($item), $items);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function line(array $item): InvoiceProductWriteData
    {
        $unit = $item['unit'] ?? null;
        $productId = $item['product_id'] ?? null;

        return new InvoiceProductWriteData(
            name: (string) $item['name'],
            quantity: $this->decimal($item['quantity']),
            unit: $unit === null || $unit === '' ? Unit::PIECES : Unit::from((string) $unit),
            price: Money::fromString($this->decimal($item['price']), (string) $item['currency']),
            taxRate: $this->decimal($item['tax_rate']),
            productId: $productId === null || $productId === '' ? null : ProductId::fromInt((int) $productId),
            category: isset($item['category']) ? (string) $item['category'] : null,
            description: isset($item['description']) ? (string) $item['description'] : null,
        );
    }

    /** "+1.5", ".5", "5." and 1.5 pass Laravel's numeric rules; Money expects "1.5", "0.5" and "5". */
    private function decimal(int|float|string $value): string
    {
        $decimal = rtrim(ltrim(trim((string) $value), '+'), '.');

        return str_starts_with($decimal, '.') ? '0' . $decimal : $decimal;
    }
}
