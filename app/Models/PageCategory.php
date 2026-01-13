<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PageCategory extends Model
{
    use HasFactory, CrudTrait;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    protected $casts = [
        'name' => 'array',
        'slug' => 'array', 
        'description' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->generateSlugForAllLocales();
        });
        
        static::updating(function ($model) {
            $model->generateSlugForAllLocales();
        });
    }

    /**
     * Generate slugs for all locales
     */
    protected function generateSlugForAllLocales()
    {
        $locales = config('app.available_locales', ['cs', 'en', 'de', 'sk']);
        $slugs = is_array($this->slug) ? $this->slug : [];
        $names = is_array($this->name) ? $this->name : [];
        
        foreach ($locales as $locale) {
            // Only generate slug if name is provided for this locale and slug is empty
            if (!empty($names[$locale]) && empty($slugs[$locale])) {
                $slugs[$locale] = Str::slug($names[$locale]);
            }
        }
        
        $this->slug = $slugs;
    }

    /**
     * Get name for specified locale with fallback
     */
    public function getName(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        
        if (!is_array($this->name)) {
            return (string) $this->name;
        }
        
        // Try requested locale first
        if (!empty($this->name[$locale])) {
            return $this->name[$locale];
        }
        
        // Fallback to available locales in order
        $availableLocales = config('app.available_locales', ['cs', 'en', 'de', 'sk']);
        foreach ($availableLocales as $fallbackLocale) {
            if (!empty($this->name[$fallbackLocale])) {
                return $this->name[$fallbackLocale];
            }
        }
        
        return null;
    }

    /**
     * Get slug for specified locale with fallback
     */
    public function getSlug(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        
        if (!is_array($this->slug)) {
            return (string) $this->slug;
        }
        
        // Try requested locale first
        if (!empty($this->slug[$locale])) {
            return $this->slug[$locale];
        }
        
        // Fallback to available locales in order
        $availableLocales = config('app.available_locales', ['cs', 'en', 'de', 'sk']);
        foreach ($availableLocales as $fallbackLocale) {
            if (!empty($this->slug[$fallbackLocale])) {
                return $this->slug[$fallbackLocale];
            }
        }
        
        return null;
    }

    /**
     * Get description for specified locale with fallback
     */
    public function getDescription(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        
        if (!is_array($this->description)) {
            return $this->description ? (string) $this->description : null;
        }
        
        // Try requested locale first
        if (!empty($this->description[$locale])) {
            return $this->description[$locale];
        }
        
        // Fallback to available locales in order
        $availableLocales = config('app.available_locales', ['cs', 'en', 'de', 'sk']);
        foreach ($availableLocales as $fallbackLocale) {
            if (!empty($this->description[$fallbackLocale])) {
                return $this->description[$fallbackLocale];
            }
        }
        
        return null;
    }

    /**
     * Set name for specified locale
     */
    public function setNameForLocale(string $locale, string $value): void
    {
        $names = is_array($this->name) ? $this->name : [];
        $names[$locale] = $value;
        $this->name = $names;
    }

    /**
     * Set slug for specified locale
     */
    public function setSlugForLocale(string $locale, string $value): void
    {
        $slugs = is_array($this->slug) ? $this->slug : [];
        $slugs[$locale] = $value;
        $this->slug = $slugs;
    }

    /**
     * Set description for specified locale
     */
    public function setDescriptionForLocale(string $locale, ?string $value): void
    {
        $descriptions = is_array($this->description) ? $this->description : [];
        $descriptions[$locale] = $value;
        $this->description = $descriptions;
    }

    /**
     * Pages belonging to this category
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class, 'category_id');
    }

    /**
     * Count of pages in this category
     */
    public function getPagesCountAttribute(): int
    {
        return $this->pages()->count();
    }

    /**
     * Get published pages count
     */
    public function getPublishedPagesCountAttribute(): int
    {
        return $this->pages()->where('published', true)->count();
    }
}
