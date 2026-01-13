<?php

namespace App\Domain\Analytics\DTO;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;

/**
 * Aggregated client invoice total (lightweight projection).
 */
class ClientTotalDTO
{
    public function __construct(
        public readonly int $clientId,
        public readonly string $clientName,
        public readonly Money $totalAmount,
    ) {}

    /**
     * @param array{client_id:int,client_name:string,total:float|int|string} $row
     */
    public static function fromArray(array $row, string $currency = 'CZK'): self
    {
        foreach (['client_id','client_name','total'] as $required) {
            if (!array_key_exists($required, $row)) {
                throw new \InvalidArgumentException("Missing required key '{$required}' for ClientTotalDTO");
            }
        }
        if (!is_numeric($row['total'])) {
            throw new \InvalidArgumentException('Total must be numeric for ClientTotalDTO');
        }
        $normalized = number_format((float)$row['total'], 2, '.', '');
        return new self(
            clientId: (int)$row['client_id'],
            clientName: (string)$row['client_name'],
            totalAmount: Money::fromString($normalized, $currency)
        );
    }

    /**
     * @return array{client_id:int,client_name:string,total_amount:float}
     */
    public function toArray(): array
    {
        return [
            'client_id' => $this->clientId,
            'client_name' => $this->clientName,
            'total_amount' => $this->totalAmount->toFloat(),
        ];
    }

    /**
     * Return formatted array with total amount as string using MoneyFormatter.
     * @param MoneyFormatterInterface|null $formatter Optional injected formatter (else app() resolved)
     * @param string|null $locale Locale override
     * @return array{client_id:int,client_name:string,total_amount:float,total_amount_formatted:string}
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
