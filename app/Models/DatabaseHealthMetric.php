<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DatabaseHealthMetric extends Model
{
    use CrudTrait;
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'database_health_metrics';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'metric_name',
        'metric_value',
        'metric_unit',
        'status',
        'recommendation',
        'measured_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'metric_value' => 'decimal:4',
        'measured_at' => 'datetime'
    ];

    /**
     * Get status badge
     */
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'good' => '<span class="badge badge-success">Good</span>',
            'warning' => '<span class="badge badge-warning">Warning</span>',
            'critical' => '<span class="badge badge-danger">Critical</span>'
        ];

        return $badges[$this->status] ?? '<span class="badge badge-light">Unknown</span>';
    }

    /**
     * Get formatted metric value with unit
     */
    public function getFormattedValueAttribute()
    {
        if ($this->metric_unit) {
            return $this->metric_value . ' ' . $this->metric_unit;
        }
        
        return $this->metric_value;
    }

    /**
     * Scope for recent metrics (last 24 hours)
     */
    public function scopeRecent($query)
    {
        return $query->where('measured_at', '>=', now()->subDay());
    }

    /**
     * Scope for specific status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for metric type
     */
    public function scopeMetricType($query, $metricName)
    {
        return $query->where('metric_name', $metricName);
    }
}
