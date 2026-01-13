<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;
    use CrudTrait;
    
    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'next_billing_at',
        'amount',
        'currency',
        'metadata',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'next_billing_at' => 'datetime',
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription plan
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Get payments for this subscription
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope for active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for expired subscriptions
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               ($this->ends_at === null || $this->ends_at > now());
    }

    /**
     * Check if subscription is in trial period
     */
    public function isInTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at > now();
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->ends_at !== null && $this->ends_at < now());
    }

    /**
     * Get days until expiration
     */
    public function daysUntilExpiration(): ?int
    {
        if ($this->ends_at === null) {
            return null;
        }

        return (int) now()->diffInDays($this->ends_at, false);
    }

    /**
     * Cancel subscription
     */
    public function cancel(): bool
    {
        $this->status = 'cancelled';
        return $this->save();
    }

    /**
     * Activate subscription
     */
    public function activate(): bool
    {
        $this->status = 'active';
        $this->starts_at = now();
        
        // Set trial period if applicable
        if ($this->subscriptionPlan->hasTrialPeriod()) {
            $this->trial_ends_at = now()->addDays($this->subscriptionPlan->trial_days);
        }
        
        return $this->save();
    }
}
