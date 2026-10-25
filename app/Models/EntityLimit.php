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
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the permission this limit is associated with
     */
    public function permission()
    {
        // For special anonymous permission, return null
        if ($this->permission_name === self::ANONYMOUS_PERMISSION) {
            return null;
        }

        return Permission::where('name', $this->permission_name)->first();
    }

    /**
     * Get usage records for this entity limit
     * Note: This is a conceptual relationship based on permission_name and entity_type
     */
    public function usage()
    {
        // In new permission-based system, we don't have direct FK relationship
        // Usage is matched by permission_name and entity_type
        return EntityLimitUsage::where('entity_type', $this->entity_type)
            ->whereHas('user.permissions', function ($query): void {
                $query->where('name', $this->permission_name);
            });
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to only active limits
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by entity type
     */
    public function scopeForEntity($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope by metric type
     */
    public function scopeByMetricType($query, string $metricType)
    {
        return $query->where('metric_type', $metricType);
    }

    /**
     * Scope by period type
     */
    public function scopeByPeriod($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    /**
     * Scope by permission name
     */
    public function scopeByPermission($query, string $permissionName)
    {
        return $query->where('permission_name', $permissionName);
    }

    /**
     * Scope for anonymous user limits
     */
    public function scopeForAnonymous($query)
    {
        return $query->where('permission_name', self::ANONYMOUS_PERMISSION);
    }

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

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get available permissions for entity limits (excluding system permissions)
     */
    public static function getAvailablePermissions(): array
    {
        $permissions = Permission::where('guard_name', 'backpack')
            ->whereNotIn('name', ['backpack.access'])
            ->pluck('name')
            ->toArray();

        // Add special anonymous permission
        $permissions[] = self::ANONYMOUS_PERMISSION;

        return $permissions;
    }

    /**
     * Get entity types as options for forms
     */
    public static function getEntityTypeOptions(): array
    {
        $options = [];
        foreach (self::ENTITY_TYPES as $type) {
            $options[$type] = ucfirst($type);
        }

        return $options;
    }

    /**
     * Get metric types as options for forms
     */
    public static function getMetricTypeOptions(): array
    {
        return self::METRIC_TYPES;
    }

    /**
     * Get period types as options for forms
     */
    public static function getPeriodTypeOptions(): array
    {
        return self::PERIOD_TYPES;
    }
}
