<?php

namespace App\Domain\Payment\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing a payment amount with currency validation
 */
class PaymentAmount
{
    private float $amount;
    private string $currency;

    // Limits delegated to PaymentAmountLimits helper for single source of truth.

    public function __construct(float $amount, string $currency)
    {
        $this->validateAmount($amount);
        $this->validateCurrency($currency);
        $this->validateCurrencyLimit($amount, $currency);

        $this->amount = $amount;
        $this->currency = strtoupper($currency);
    }

    /**
     * Create from string representation
     */
    public static function fromString(string $amountString, string $currency): self
    {
        $amount = floatval($amountString);
        return new self($amount, $currency);
    }

    /**
     * Create PaymentAmount from Money VO (validates 2-decimal constraint and limits).
     */
    public static function fromMoney(\App\Domain\Shared\Money\ValueObjects\Money $money): self
    {
        $currency = $money->getCurrency();
        // Force 2 decimal monetary semantic for payments.
        $float = (float) number_format((float)$money->getAmount(), 2, '.', '');
        return new self($float, $currency);
    }

    /**
     * Get the amount as float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Get the currency code
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Get formatted amount for banking (2 decimal places)
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->amount, 2, '.', '');
    }

    /**
     * Get amount in cents/minor units
     */
    public function getAmountInCents(): int
    {
        return (int) round($this->amount * 100);
    }

    /**
     * Check if amount is zero (below threshold)
     */
    public function isZero(): bool
    {
        return $this->amount < 0.001; // Treat amounts below 0.001 as zero for comparison purposes
    }

    /**
     * Add another payment amount (must be same currency)
     */
    public function add(PaymentAmount $other): PaymentAmount
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot add amounts with different currencies: {$this->currency} vs {$other->currency}"
            );
        }

        return new PaymentAmount($this->amount + $other->amount, $this->currency);
    }

    /**
     * Subtract another payment amount (must be same currency)
     */
    public function subtract(PaymentAmount $other): PaymentAmount
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot subtract amounts with different currencies: {$this->currency} vs {$other->currency}"
            );
        }

        return new PaymentAmount($this->amount - $other->amount, $this->currency);
    }

    /**
     * Check if this amount equals another
     */
    public function equals(PaymentAmount $other): bool
    {
        return $this->currency === $other->currency && 
               abs($this->amount - $other->amount) < 0.001;
    }

    /**
     * Check if this amount is greater than another
     */
    public function greaterThan(PaymentAmount $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot compare amounts with different currencies: {$this->currency} vs {$other->currency}"
            );
        }

        return $this->amount > $other->amount;
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return $this->getFormattedAmount() . ' ' . $this->currency;
    }

    /**
     * Validate amount is positive and not zero
     */
    private function validateAmount(float $amount): void
    {
        if ($amount < PaymentAmountLimits::minAmount()) {
            throw new InvalidArgumentException(
        "Payment amount must be at least " . PaymentAmountLimits::minAmount() . ", got: {$amount}"
            );
        }
    }

    /**
     * Validate currency is supported
     */
    private function validateCurrency(string $currency): void
    {
        $currency = strtoupper($currency);
        if (!PaymentAmountLimits::isSupported($currency)) {
            throw new InvalidArgumentException("Unsupported currency: {$currency}");
        }
    }

    /**
     * Validate amount doesn't exceed currency limit
     */
    private function validateCurrencyLimit(float $amount, string $currency): void
    {
        $currency = strtoupper($currency);
        $maxAmount = PaymentAmountLimits::maxFor($currency);
        
        if ($amount > $maxAmount) {
            throw new InvalidArgumentException(
                "Amount {$amount} {$currency} exceeds maximum limit of {$maxAmount} {$currency}"
            );
        }
    }
}
