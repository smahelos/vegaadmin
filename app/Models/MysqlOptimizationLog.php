<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MysqlOptimizationLog extends Model
{
    use CrudTrait;
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mysql_optimization_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'setting_name',
        'current_value',
        'recommended_value',
        'description',
        'priority',
        'applied'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'applied' => 'boolean'
    ];

    /**
     * Get priority badge
     */
    public function getPriorityBadgeAttribute()
    {
        $badges = [
            'high' => '<span class="badge badge-danger">High</span>',
            'medium' => '<span class="badge badge-warning">Medium</span>',
            'low' => '<span class="badge badge-info">Low</span>'
        ];

        return $badges[$this->priority] ?? '<span class="badge badge-light">Unknown</span>';
    }

    /**
     * Get applied status badge
     */
    public function getAppliedBadgeAttribute()
    {
        return $this->applied 
            ? '<span class="badge badge-success">Applied</span>'
            : '<span class="badge badge-secondary">Pending</span>';
    }

    /**
     * Get current value from MySQL if not set
     */
    public function getCurrentValueFromDbAttribute()
    {
        if ($this->current_value) {
            return $this->current_value;
        }

        try {
            $result = \DB::select("SHOW VARIABLES LIKE ?", [$this->setting_name]);
            return $result[0]->Value ?? 'Not found';
        } catch (\Exception $e) {
            return 'Error reading';
        }
    }

    /**
     * Scope for high priority items
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    /**
     * Scope for unapplied items
     */
    public function scopeUnapplied($query)
    {
        return $query->where('applied', false);
    }
}
