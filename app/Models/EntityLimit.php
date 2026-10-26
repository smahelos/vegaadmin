<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

/**
 * Entity Limit Model - Permission-based System
 *
 * Defines limits for different entities based on permissions.
 * Each record links a permission to an entity with specific limits.
 * Real-time calculation picks the highest limit from all user permissions.
 */
class EntityLimit extends Model
{
    use CrudTrait, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'permission_name',
        'entity_type',
        'limit_value',
        'period_type',
        'metric_type',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'limit_value' => 'integer',
    ];

    /**
     * Available entity types
     */
    public const ENTITY_TYPES = [
        'invoice',
        'client',
        'supplier',
        'product',
        'expense',
        'user',
        'page',
    ];

    /**
     * Available metric types (what we're measuring)
     */
    public const METRIC_TYPES = [
        'count' => 'Count-based limit',
        'value' => 'Value-based limit (monetary)',
        'size' => 'Size-based limit (file size)',
    ];

    /**
     * Available period types
     */
    public const PERIOD_TYPES = [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
        'lifetime' => 'Lifetime',
    ];

    /**
     * Special permission name for anonymous users
     */
    public const ANONYMOUS_PERMISSION = '__anonymous_user__';

    /*
    |--------------------------------------------------------------------------
    | BOOT METHOD
    |--------------------------------------------------------------------------
    */

    /**
     * Boot method to clear cache when EntityLimit is saved/deleted
     */
    protected static function boot(): void
    {
        parent::boot();

        // Clear cache when EntityLimit is saved or deleted
        static::saved(function (): void {
            Artisan::call('cache:clear');
        });

        static::deleted(function (): void {
            Artisan::call('cache:clear');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS & ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this is an anonymous user limit
     */
    public function getIsAnonymousAttribute(): bool
    {
        return $this->permission_name === self::ANONYMOUS_PERMISSION;
    }
}
