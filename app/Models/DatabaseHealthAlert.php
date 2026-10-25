<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatabaseHealthAlert extends Model
{
    use CrudTrait;
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'database_health_alerts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'alert_type',
        'severity',
        'message',
        'metric_data',
        'resolved',
        'resolved_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'metric_data' => 'array',
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    /**
     * Scope for unresolved alerts
     */
    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where('resolved', false);
    }

    /**
     * Scope for specific severity
     */
    public function scopeSeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    /**
     * Mark alert as resolved
     */
    public function markResolved(): void
    {
        $this->update([
            'resolved' => true,
            'resolved_at' => now(),
        ]);
    }
}
