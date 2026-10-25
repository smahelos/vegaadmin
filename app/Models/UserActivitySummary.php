<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Columns of the user_activity_summary database view, which Larastan cannot read from the migrations.
 *
 * @property-read int $user_id
 * @property-read string $user_name
 * @property-read string $user_email
 * @property-read int $total_invoices
 * @property-read int $total_clients
 * @property-read int $total_suppliers
 * @property-read int $total_products
 * @property-read Carbon|null $last_invoice_date
 * @property-read int $invoices_last_30_days
 * @property-read int $invoices_last_7_days
 */
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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'total_invoices',
        'total_clients',
        'total_suppliers',
        'total_products',
        'last_invoice_date',
        'invoices_last_30_days',
        'invoices_last_7_days',
    ];

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
        'invoices_last_7_days' => 'integer',
    ];

    /**
     * Get the primary key for the model.
     * Using user_id as primary key for this view
     */
    public function getKeyName(): string
    {
        return 'user_id';
    }

    /**
     * Get activity level as string
     */
    public function getActivityLevelAttribute(): string
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
    public function getActivityBadgeClassAttribute(): string
    {
        return match ($this->activity_level) {
            'high' => 'success',
            'medium' => 'primary',
            'low' => 'warning',
            'inactive' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get formatted activity level text
     */
    public function getFormattedActivityLevelAttribute(): string
    {
        return match ($this->activity_level) {
            'high' => __('admin.user_activity.high_activity'),
            'medium' => __('admin.user_activity.medium_activity'),
            'low' => __('admin.user_activity.low_activity'),
            'inactive' => __('admin.user_activity.inactive'),
            default => __('admin.user_activity.unknown'),
        };
    }

    /**
     * Get relationship to User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
