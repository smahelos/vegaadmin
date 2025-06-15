<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PerformanceMetric extends Model
{
    use CrudTrait;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'metric_type',
        'table_name',
        'query_type',
        'metric_value',
        'metric_unit',
        'metadata',
        'measured_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => 'array',
        'measured_at' => 'datetime',
        'metric_value' => 'decimal:4'
    ];

    /**
     * Get formatted metric value
     */
    public function getFormattedValueAttribute()
    {
        return number_format($this->metric_value, 2) . ' ' . $this->metric_unit;
    }

    /**
     * Get metric type formatted
     */
    public function getMetricTypeFormattedAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->metric_type));
    }


    /**
     * Scope for specific metric type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('metric_type', $type);
    }

    /**
     * Scope for specific table
     */
    public function scopeForTable($query, $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    /**
     * Scope for recent metrics
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('measured_at', '>=', now()->subDays($days));
    }
}
