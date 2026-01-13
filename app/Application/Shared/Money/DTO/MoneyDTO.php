<?php

namespace App\Application\Shared\Money\DTO;

use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Money\Enum\Currency;

/**
 * Transport DTO wrapping the Money value object for presentation layers.
 * Keeps immutable Money internally while exposing simple scalar array when needed.
 */
class MoneyDTO
{
    public function __construct(private readonly Money $money) {}

    public static function fromMoney(Money $money): self
    {
        return new self($money);
    }

    public static function fromFloat(float $amount, Currency $currency): self
    {
        return new self(Money::fromFloat($amount, $currency->value));
    }

    public function money(): Money
    {
        return $this->money;
    }

    /**
     * Export legacy array shape for blade/JS (string amount + currency code).
     *
     * @return array{amount:string,currency:string}
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->money->getAmount(),
            'currency' => $this->money->getCurrency(),
        ];
    }
}
