<?php

namespace App\Domain\Analytics\DTO;

use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;

/**
 * Represents a single monthly aggregate row (month string + total amount).
 */
class MonthlyStatDTO
{
    public function __construct(
        public readonly string $month, // Format: YYYY-MM
        public readonly Money $total,
    ) {}

    /**
     * @param array{month:string,total:float|int|string} $row
     * @param string $currency Base currency (default CZK)
     */
    public static function fromArray(array $row, string $currency = 'CZK'): self
    {
        foreach (['month','total'] as $required) {
            if (!array_key_exists($required, $row)) {
                throw new \InvalidArgumentException("Missing required key '{$required}' for MonthlyStatDTO");
            }
        }
        if (!preg_match('/^\d{4}-\d{2}$/', (string)$row['month'])) {
            throw new \InvalidArgumentException('Month must be in format YYYY-MM');
        }
        if (!is_numeric($row['total'])) {
            throw new \InvalidArgumentException('Total must be numeric for MonthlyStatDTO');
        }
        $normalized = number_format((float)$row['total'], 2, '.', '');
        return new self(
            month: $row['month'],
            total: Money::fromString($normalized, $currency)
        );
    }

    /**
     * @return array{month:string,total:float}
     */
    public function toArray(): array
    {
        return [
            'month' => $this->month,
            'total' => $this->total->toFloat(),
        ];
    }

    /**
     * Return formatted array with total amount as string using MoneyFormatter.
     * @param MoneyFormatterInterface|null $formatter Optional injected formatter (else app() resolved)
     * @param string|null $locale Locale override
     * @return array{month:string,total:float,total_formatted:string}
     */
    public function toFormattedArray(?MoneyFormatterInterface $formatter = null, ?string $locale = null): array
    {
        if ($formatter === null) {
            throw new \InvalidArgumentException('MoneyFormatterInterface instance is required for formatting in Domain DTO');
        }
        $base = $this->toArray();
        $base['total_formatted'] = $formatter->formatWithCode($this->total, $locale);
        return $base;
    }
}
