<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'resolved_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'metric_data' => 'array',
        'resolved' => 'boolean',
        'resolved_at' => 'datetime'
    ];

    /**
     * Get severity badge
     */
    public function getSeverityBadgeAttribute()
    {
        $badges = [
            'info' => '<span class="badge badge-info">Info</span>',
            'warning' => '<span class="badge badge-warning">Warning</span>',
            'critical' => '<span class="badge badge-danger">Critical</span>'
        ];

        return $badges[$this->severity] ?? '<span class="badge badge-light">Unknown</span>';
    }

    /**
     * Get resolved status badge
     */
    public function getResolvedBadgeAttribute()
    {
        return $this->resolved 
            ? '<span class="badge badge-success">Resolved</span>'
            : '<span class="badge badge-secondary">Active</span>';
    }

    /**
     * Scope for unresolved alerts
     */
    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    /**
     * Scope for specific severity
     */
    public function scopeSeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope for alert type
     */
    public function scopeAlertType($query, $alertType)
    {
        return $query->where('alert_type', $alertType);
    }

    /**
     * Scope for recent alerts (last 7 days)
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subWeek());
    }

    /**
     * Mark alert as resolved
     */
    public function markResolved()
    {
        $this->update([
            'resolved' => true,
            'resolved_at' => now()
        ]);
    }
}
