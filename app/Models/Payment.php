<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = [
        'subscription_id',
        'gateway',
        'gateway_payment_id',
        'status',
        'amount',
    'refunded_amount',
        'currency',
        'payment_method',
        'gateway_data',
        'failure_reason',
        'paid_at',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        // Use float for refunded_amount to allow incremental arithmetic; decimal cast caused assignment issues in tests.
        'refunded_amount' => 'float',
        'gateway_data' => 'array',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the subscription that owns the payment
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Scope for completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if payment is failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted(): bool
    {
        $this->status = 'completed';
        $this->paid_at = now();
        return $this->save();
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(string $reason = null): bool
    {
        $this->status = 'failed';
        if ($reason) {
            $this->failure_reason = $reason;
        }
        return $this->save();
    }

    /**
     * Register a refunded amount (partial or full) updating status accordingly.
     *
     * @param float $amount Amount refunded in this operation (>=0)
     * @return bool
     */
    public function applyRefund(float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }
        $current = (float) ($this->refunded_amount ?? 0.0);
        $newTotal = round($current + $amount, 2);
        $this->refunded_amount = (string) $newTotal; // decimal cast expects stringable numeric
        if ($newTotal + 0.0001 < (float) $this->amount) {
            $this->status = 'partially_refunded';
        } else {
            $this->status = 'refunded';
            $this->refunded_amount = (string) (float)$this->amount; // clamp
        }
        return $this->save();
    }

    /**
     * Whether payment is partially refunded.
     * @return bool
     */
    public function isPartiallyRefunded(): bool
    {
        return $this->status === 'partially_refunded';
    }

    /**
     * Whether payment is fully refunded.
     * @return bool
     */
    public function isFullyRefunded(): bool
    {
        return $this->status === 'refunded';
    }
}
