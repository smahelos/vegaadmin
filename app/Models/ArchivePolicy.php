<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ArchivePolicy extends Model
{
    use CrudTrait;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'table_name',
        'retention_months',
        'date_column',
        'enabled',
        'last_archived_at',
        'records_archived'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'enabled' => 'boolean',
        'last_archived_at' => 'datetime',
        'retention_months' => 'integer',
        'records_archived' => 'integer'
    ];

    /**
     * Get enabled status badge
     */
    public function getEnabledBadgeAttribute()
    {
        return $this->enabled 
            ? '<span class="badge badge-success">Enabled</span>'
            : '<span class="badge badge-secondary">Disabled</span>';
    }

    /**
     * Get retention period formatted
     */
    public function getRetentionPeriodAttribute()
    {
        return $this->retention_months . ' months (' . round($this->retention_months / 12, 1) . ' years)';
    }

    /**
     * Get last archived formatted
     */
    public function getLastArchivedFormattedAttribute()
    {
        return $this->last_archived_at 
            ? $this->last_archived_at->format('d.m.Y H:i')
            : 'Never';
    }

    /**
     * Get records to archive estimate
     */
    public function getRecordsToArchiveAttribute()
    {
        if (!$this->enabled) {
            return 0;
        }

        $cutoffDate = now()->subMonths($this->retention_months);
        
        try {
            return \DB::table($this->table_name)
                ->where($this->date_column, '<', $cutoffDate)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Scope for enabled policies
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }
}
