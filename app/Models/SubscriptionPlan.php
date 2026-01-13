<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SubscriptionPlan extends Model
{
    use HasFactory;
    use CrudTrait;
    
    protected $fillable = [
        'name',
        'description',
        'price',
        'currency',
        'billing_period',
        'billing_interval',
        'is_active',
        'trial_days',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'trial_days' => 'integer',
        'billing_interval' => 'integer',
    ];

    /**
     * Get subscriptions for this plan
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get features associated with this plan
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(
            SubscriptionPlanFeature::class,
            'subscription_plan_subscription_plan_feature'
        );
    }

    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get formatted price with currency
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format((float) $this->price, 2) . ' ' . $this->currency;
    }

    /**
     * Check if plan has trial period
     */
    public function hasTrialPeriod(): bool
    {
        return $this->trial_days > 0;
    }
}
