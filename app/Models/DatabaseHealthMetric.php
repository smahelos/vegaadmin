<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'measured_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'metric_value' => 'decimal:4',
        'measured_at' => 'datetime',
    ];

    /**
     * Scope for recent metrics (last 24 hours)
     */
    public function scopeRecent($query)
    {
        return $query->where(
            'measured_at',
            '>=',
            now()->subDay(),
        );
    }
}
