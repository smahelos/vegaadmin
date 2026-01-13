<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SubscriptionPlanFeature extends Model
{
    use HasFactory;
    use CrudTrait;
    use SoftDeletes;
    
    protected $fillable = [
        'name',
        'description',
        'slug',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = $model->generateSlug($model->name);
            }
        });
    }

    /**
     * Get subscription plans that have this feature
     */
    public function subscriptionPlans(): BelongsToMany
    {
        return $this->belongsToMany(
            SubscriptionPlan::class,
            'subscription_plan_subscription_plan_feature'
        );
    }

    /**
     * Scope for active features
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered features
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Check if feature is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Generate slug from name
     */
    public function generateSlug(string $name = null): string
    {
        $nameToSlug = $name ?? $this->name;
        $baseSlug = Str::slug($nameToSlug);
        
        // Check if slug exists and make it unique
        $counter = 1;
        $slug = $baseSlug;
        
        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}
