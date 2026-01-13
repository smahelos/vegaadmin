<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entity Limit Usage Model - Permission-based System
 * 
 * Tracks actual usage of entities within specific periods for limit enforcement.
 * No longer tied to specific EntityLimit records, but tracks by entity type directly.
 */
class EntityLimitUsage extends Model
{
    use HasFactory, CrudTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'entity_limit_usage';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'entity_type',
        'metric_type',
        'period_type',
        'period_start',
        'period_end',
        'current_value',
        'last_reset_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'current_value' => 'decimal:2',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'last_reset_at' => 'datetime'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that this usage belongs to
     * Note: Can be null for anonymous users
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to current period
     */
    public function scopeCurrentPeriod($query)
    {
        $now = now();
        return $query->where('period_start', '<=', $now)
                    ->where('period_end', '>=', $now);
    }

    /**
     * Scope by user (including null for anonymous)
     */
    public function scopeForUser($query, ?int $userId)
    {
        return $query->where('user_id', $userId);
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
    public function scopeByPeriodType($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    /**
     * Scope for anonymous users
     */
    public function scopeForAnonymous($query)
    {
        return $query->whereNull('user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS & ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this usage period is currently active
     */
    public function getIsCurrentPeriodAttribute(): bool
    {
        $now = now();
        return $this->period_start <= $now && $this->period_end >= $now;
    }

    /**
     * Get display name for the usage record
     */
    public function getDisplayNameAttribute(): string
    {
        $user = $this->user ? "User #{$this->user->id}" : 'Anonymous';
        return "{$user} - {$this->entity_type} {$this->metric_type} ({$this->period_type})";
    }

    /**
     * Get formatted period string
     */
    public function getPeriodStringAttribute(): string
    {
        return $this->period_start->format('M j, Y') . ' - ' . $this->period_end->format('M j, Y');
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get usage summary for a user across all entities
     */
    public static function getUserUsageSummary(?int $userId): array
    {
        $usage = self::forUser($userId)
            ->currentPeriod()
            ->selectRaw('entity_type, metric_type, period_type, SUM(current_value) as total_usage')
            ->groupBy(['entity_type', 'metric_type', 'period_type'])
            ->get();

        $summary = [];
        foreach ($usage as $item) {
            $summary[$item->entity_type][$item->metric_type][$item->period_type] = $item->total_usage;
        }

        return $summary;
    }

    /**
     * Clean up old usage records (older than 1 year)
     */
    public static function cleanupOldRecords(): int
    {
        $cutoffDate = now()->subYear();
        
        return self::where('period_end', '<', $cutoffDate)->delete();
    }
}
