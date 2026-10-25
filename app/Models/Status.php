<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

    /**
     * Get the category of this status
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(StatusCategory::class, 'category_id');
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
    /**
     * Admin badge in the status color. The color is a plain name (green, yellow, ...) as chosen in the
     * admin form; the frontend builds Tailwind classes from it (bg-{color}-100).
     */
    public function getColorPreviewAttribute(): string
    {
        // Tabler has no plain bg-gray badge color
        $color = ['gray' => 'secondary'][$this->color ?? 'gray'] ?? $this->color;

        return '<span class="badge bg-' . e($color) . ' text-' . e($color) . '-fg">' . e($this->name) . '</span>';
    }

    /**
     * Get translated status name based on slug
     */
    public function getTranslatedNameAttribute(): string
    {
        // Check if status has a defined slug
        if (empty($this->slug)) {
            return $this->name ?? __('invoices.placeholders.not_available');
        }

        try {
            // Create translation key based on slug
            $translationKey = 'statuses.statuses.' . $this->slug;

            // Check if translation exists
            if (Lang::has($translationKey)) {
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
    public function setSlugAttribute($value): void
    {
        $this->attributes['slug'] = Str::slug($value);
    }
}
