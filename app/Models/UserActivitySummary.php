<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class UserActivitySummary extends Model
{
    use CrudTrait;

    /**
     * The table associated with the model (database view).
     *
     * @var string
     */
    protected $table = 'user_activity_summary';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'last_invoice_date' => 'datetime',
        'total_invoices' => 'integer',
        'total_clients' => 'integer',
        'total_suppliers' => 'integer',
        'total_products' => 'integer',
        'invoices_last_30_days' => 'integer',
        'invoices_last_7_days' => 'integer'
    ];

    /**
     * Get the primary key for the model.
     * Using user_id as primary key for this view
     *
     * @return string
     */
    public function getKeyName()
    {
        return 'user_id';
    }

    /**
     * Scope for users with recent activity (invoices in last 30 days)
     */
    public function scopeWithRecentActivity($query)
    {
        return $query->where('invoices_last_30_days', '>', 0);
    }

    /**
     * Scope for users with high activity (more than 20 invoices in last 30 days)
     */
    public function scopeHighActivity($query)
    {
        return $query->where('invoices_last_30_days', '>', 20);
    }

    /**
     * Scope for inactive users (no invoices in last 30 days)
     */
    public function scopeInactive($query)
    {
        return $query->where('invoices_last_30_days', '=', 0);
    }

    /**
     * Get activity level as string
     */
    public function getActivityLevelAttribute()
    {
        if ($this->invoices_last_30_days >= 20) {
            return 'high';
        } elseif ($this->invoices_last_30_days >= 5) {
            return 'medium';
        } elseif ($this->invoices_last_30_days >= 1) {
            return 'low';
        } else {
            return 'inactive';
        }
    }

    /**
     * Get activity level badge class
     */
    public function getActivityBadgeClassAttribute()
    {
        return match($this->activity_level) {
            'high' => 'success',
            'medium' => 'primary',
            'low' => 'warning',
            'inactive' => 'secondary',
            default => 'secondary'
        };
    }

    /**
     * Get formatted activity level text
     */
    public function getFormattedActivityLevelAttribute()
    {
        return match($this->activity_level) {
            'high' => __('admin.user_activity.high_activity'),
            'medium' => __('admin.user_activity.medium_activity'),
            'low' => __('admin.user_activity.low_activity'),
            'inactive' => __('admin.user_activity.inactive'),
            default => __('admin.user_activity.unknown')
        };
    }

    /**
     * Get relationship to User model
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
