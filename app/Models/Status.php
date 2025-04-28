<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class Status extends Model
{
    use CrudTrait;
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */
    protected $table = 'statuses';
    protected $guarded = ['id'];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    /**
     * Get all invoices with this status
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'payment_status_id');
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
    /**
     * Generate HTML representation of status color for admin panel
     */
    public function getColorPreviewAttribute()
    {
        $color = $this->color ?? 'bg-gray-100 text-gray-800';
        
        // Extract background color for preview
        $bgColor = '';
        if (preg_match('/bg-(\w+)-\d+/', $color, $matches)) {
            $bgColor = $matches[1];
        }
        
        return '<span class="px-2 py-1 rounded '.$color.'" style="background-color: '.($bgColor ? $bgColor : '#f3f4f6').'">
                '.$this->name.'</span>';
    }

    /**
     * Get translated status name based on slug
     *
     * @return string
     */
    public function getTranslatedNameAttribute()
    {
        // Check if status has a defined slug
        if (empty($this->slug)) {
            return $this->name ?? __('invoices.placeholders.not_available');
        }
        
        try {
            // Create translation key based on slug
            $translationKey = 'payment_statuses.' . $this->slug;
            
            // Check if translation exists
            if (\Illuminate\Support\Facades\Lang::has($translationKey)) {
                return trans($translationKey);
            }
            
            // If translation doesn't exist, return original name
            return $this->name;
        } catch (\Exception $e) {
            // In case of error, return original name and log the error
            Log::error('Error translating payment status: ' . $e->getMessage());
            return $this->name;
        }
    }

    /**
     * Scope for active statuses only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Get the status color
     */
    public function getColorAttribute($value)
    {
        return $value ?? 'bg-gray-100 text-gray-800';
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
    /**
     * Ensure slug is properly formatted
     */
    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = Str::slug($value);
    }
}