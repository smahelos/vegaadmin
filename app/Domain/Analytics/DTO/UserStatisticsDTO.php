<?php

namespace App\Domain\Analytics\DTO;

use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;

/**
 * Immutable Data Transfer Object representing basic aggregate counters for a user's dashboard.
 */
class UserStatisticsDTO
{
    public function __construct(
        public readonly int $invoiceCount,
        public readonly int $clientCount,
        public readonly int $suppliersCount,
        public readonly Money $totalAmount,
    ) {}

    /**
     * Factory from raw associative array (legacy array payload with float total_amount).
     * @param array{invoice_count:int,client_count:int,suppliers_count:int,total_amount:float|int|string} $data
     * @param string $currency Base currency code (default CZK)
     */
    public static function fromArray(array $data, string $currency = 'CZK'): self
    {
        // Validate required keys and basic types
        foreach (['invoice_count','client_count','suppliers_count','total_amount'] as $required) {
            if (!array_key_exists($required, $data)) {
                throw new \InvalidArgumentException("Missing required key '{$required}' for UserStatisticsDTO");
            }
        }
        if (!is_numeric($data['invoice_count']) || !is_numeric($data['client_count']) || !is_numeric($data['suppliers_count'])) {
            throw new \InvalidArgumentException('Count fields must be numeric for UserStatisticsDTO');
        }
        if (!is_numeric($data['total_amount'])) {
            throw new \InvalidArgumentException('Total amount must be numeric for UserStatisticsDTO');
        }
        $normalized = number_format((float)$data['total_amount'], 2, '.', '');
        return new self(
            invoiceCount: $data['invoice_count'],
            clientCount: $data['client_count'],
            suppliersCount: $data['suppliers_count'],
            totalAmount: Money::fromString($normalized, $currency)
        );
    }

    /**
     * Normalize to array for JSON serialization or backward compatibility (float total).
     * @return array{invoice_count:int,client_count:int,suppliers_count:int,total_amount:float}
     */
    public function toArray(): array
    {
        return [
            'invoice_count' => $this->invoiceCount,
            'client_count' => $this->clientCount,
            'suppliers_count' => $this->suppliersCount,
            'total_amount' => $this->totalAmount->toFloat(),
        ];
    }

    /**
     * Return base array (backward compatibility) + formatted amount using MoneyFormatter.
     * @param MoneyFormatterInterface|null $formatter Optional injected formatter (else app() resolved)
     * @param string|null $locale Locale override
     * @return array{invoice_count:int,client_count:int,suppliers_count:int,total_amount:float,total_amount_formatted:string}
     */
    public function toFormattedArray(?MoneyFormatterInterface $formatter = null, ?string $locale = null): array
    {
        if ($formatter === null) {
            throw new \InvalidArgumentException('MoneyFormatterInterface instance is required for formatting in Domain DTO');
        }
        $base = $this->toArray();
        $base['total_amount_formatted'] = $formatter->formatWithCode($this->totalAmount, $locale);
        return $base;
    }
}
