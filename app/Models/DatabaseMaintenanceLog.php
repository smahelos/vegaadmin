<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatabaseMaintenanceLog extends Model
{
    use CrudTrait;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'task_type',
        'table_name',
        'status',
        'description',
        'results',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'results' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get formatted duration
     */
    public function getDurationAttribute(): ?string
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return $this->started_at->diffForHumans($this->completed_at, true);
    }
}
