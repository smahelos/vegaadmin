<?php

namespace App\Domain\Payment\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing the refund state of a payment.
 * Encapsulates status (none/partial/full), refunded amount and total amount.
 */
class RefundState
{
    private string $status; // none|partially_refunded|refunded
    private float $refundedAmount; // decimal 2 precision
    private float $totalAmount; // original payment amount
    private string $currency; // ISO 4217

    /**
     * Internal constructor. Use named constructors for clarity.
     *
     * @param string $status One of: none|partially_refunded|refunded
     * @param float $refundedAmount Already refunded amount (2 decimals)
     * @param float $totalAmount Original total payment amount (2 decimals)
     * @param string $currency ISO 4217 currency code
     */
    private function __construct(string $status, float $refundedAmount, float $totalAmount, string $currency)
    {
        $this->assertValid($status, $refundedAmount, $totalAmount);
        $this->status = $status;
        $this->refundedAmount = round($refundedAmount, 2);
        $this->totalAmount = round($totalAmount, 2);
        $this->currency = strtoupper($currency);
    }

    /**
     * Create a state representing no refund yet performed.
     *
     * @param float $totalAmount Original payment amount
     * @param string $currency ISO 4217 code
     * @return self
     */
    public static function none(float $totalAmount, string $currency): self
    {
        return new self('none', 0.0, $totalAmount, $currency);
    }

    /**
     * Create a state representing a partial refund.
     *
     * @param float $refundedAmount Amount already refunded (must be > 0 and < total)
     * @param float $totalAmount Original payment amount
     * @param string $currency ISO 4217 code
     * @return self
     */
    public static function partial(float $refundedAmount, float $totalAmount, string $currency): self
    {
        return new self('partially_refunded', $refundedAmount, $totalAmount, $currency);
    }

    /**
     * Create a state representing a full refund.
     *
     * @param float $totalAmount Original payment amount (will equal refunded amount)
     * @param string $currency ISO 4217 code
     * @return self
     */
    public static function full(float $totalAmount, string $currency): self
    {
        return new self('refunded', $totalAmount, $totalAmount, $currency);
    }

    /**
     * Factory resolving the correct state (none/partial/full) from numeric amounts.
     *
     * @param float $refundedAmount Refunded amount (>= 0)
     * @param float $totalAmount Original payment amount (>= 0)
     * @param string $currency ISO 4217 code
     * @return self
     */
    public static function fromAmounts(float $refundedAmount, float $totalAmount, string $currency): self
    {
        if ($refundedAmount <= 0.0001) {
            return self::none($totalAmount, $currency);
        }
        if ($refundedAmount + 0.0001 < $totalAmount) { // allow slight floating diff
            return self::partial($refundedAmount, $totalAmount, $currency);
        }
        return self::full($totalAmount, $currency);
    }

    /**
     * Add another refund amount and return a new state instance.
     *
     * @param float $amount Incremental refund amount (> 0)
     * @return self Updated refund state
     */
    public function addRefund(float $amount): self
    {
        if ($amount < 0.01) {
            throw new InvalidArgumentException('Refund increment must be at least 0.01');
        }
        $newRefunded = $this->refundedAmount + $amount;
        if ($newRefunded - $this->totalAmount > 0.0001) {
            throw new InvalidArgumentException('Cannot refund more than total amount');
        }
        return self::fromAmounts($newRefunded, $this->totalAmount, $this->currency);
    }

    /**
     * Determine whether the given refund amount is allowed.
     *
     * @param float $amount Proposed refund amount
     * @return bool True if refund is possible
     */
    public function canRefund(float $amount): bool
    {
        if ($amount < 0.01) { return false; }
        if ($this->isFullyRefunded()) { return false; }
        return ($this->refundedAmount + $amount) <= ($this->totalAmount + 0.0001);
    }

    /**
     * Get the remaining refundable amount.
     *
     * @return float Remaining amount (>= 0)
     */
    public function remainingAmount(): float
    {
        return max(0.0, round($this->totalAmount - $this->refundedAmount, 2));
    }

    /**
     * Check if payment is partially (but not fully) refunded.
     *
     * @return bool
     */
    public function isPartiallyRefunded(): bool
    {
        return $this->status === 'partially_refunded';
    }

    /**
     * Check if payment is fully refunded.
     *
     * @return bool
     */
    public function isFullyRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Export state as associative array.
     *
     * @return array{status:string,refunded_amount:float,total_amount:float,remaining_amount:float,currency:string}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'refunded_amount' => $this->refundedAmount,
            'total_amount' => $this->totalAmount,
            'remaining_amount' => $this->remainingAmount(),
            'currency' => $this->currency,
        ];
    }

    /**
     * Get status keyword.
     *
     * @return string
     */
    public function getStatus(): string { return $this->status; }
    /**
     * Get refunded amount.
     *
     * @return float
     */
    public function getRefundedAmount(): float { return $this->refundedAmount; }
    /**
     * Get original total amount.
     *
     * @return float
     */
    public function getTotalAmount(): float { return $this->totalAmount; }
    /**
     * Get currency code.
     *
     * @return string
     */
    public function getCurrency(): string { return $this->currency; }

    /**
     * Validate invariants for the state combination.
     *
     * @param string $status
     * @param float $refundedAmount
     * @param float $totalAmount
     * @return void
     */
    private function assertValid(string $status, float $refundedAmount, float $totalAmount): void
    {
        if (!in_array($status, ['none', 'partially_refunded', 'refunded'], true)) {
            throw new InvalidArgumentException('Invalid refund status: ' . $status);
        }
        if ($totalAmount < 0) {
            throw new InvalidArgumentException('Total amount cannot be negative');
        }
        if ($refundedAmount < 0) {
            throw new InvalidArgumentException('Refunded amount cannot be negative');
        }
        if ($refundedAmount - $totalAmount > 0.0001) {
            throw new InvalidArgumentException('Refunded amount cannot exceed total amount');
        }
        if ($status === 'none' && $refundedAmount > 0.0001) {
            throw new InvalidArgumentException('None status must have zero refunded amount');
        }
        if ($status === 'refunded' && abs($refundedAmount - $totalAmount) > 0.0001) {
            throw new InvalidArgumentException('Full refund status must equal total amount');
        }
    }
}
