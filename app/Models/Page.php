<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Infrastructure\Shared\File\Traits\HasFileUploads;

class Page extends Model
{
    use HasFactory;
    use CrudTrait;
    use HasFileUploads;

    protected $table = 'pages';

    protected $fillable = [
        'name',
        'slug', 
        'description',
        'content',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'category_id',
        'parent_id',
        'sort_order',
        'published',
        'publishing_start',
        'publishing_end',
        'published_by',
        'main_image',
        'images',
    ];

    protected $casts = [
        'name' => 'array',
        'slug' => 'array',
        'description' => 'array',
        'content' => 'array',
        'meta_title' => 'array',
        'meta_description' => 'array',
        'meta_keywords' => 'array',
        'published' => 'boolean',
        'publishing_start' => 'datetime',
        'publishing_end' => 'datetime',
        'images' => 'array',
    ];

    /**
     * Boot method - handle auto sorting and published_by assignment
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            // Auto-assign sort_order if not provided
            if (is_null($page->sort_order)) {
                $maxOrder = static::where('parent_id', $page->parent_id)
                    ->where('category_id', $page->category_id)
                    ->max('sort_order') ?? 0;
                $page->sort_order = $maxOrder + 1;
            }

            // Auto-assign published_by if published and not already set
            if ($page->published && !$page->published_by && auth()->check()) {
                $page->published_by = auth()->id();
            }
        });

        static::updating(function ($page) {
            // Auto-assign published_by if published and not already set
            if ($page->published && !$page->published_by && auth()->check()) {
                $page->published_by = auth()->id();
            }
        });
    }

    /**
     * Get page category relationship
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PageCategory::class, 'category_id');
    }

    /**
     * Get page name in specified language with fallback
     * 
     * @param string|null $locale
     * @return string
     */
    public function getName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $names = $this->name ?? [];
        
        // Ensure we have an array
        if (!is_array($names)) {
            return (string) $names;
        }
        
        // Try requested locale first
        if (!empty($names[$locale])) {
            return $names[$locale];
        }
        
        // Fallback priority: cs -> en -> first available
        $fallbackLocales = ['cs', 'en'];
        foreach ($fallbackLocales as $fallbackLocale) {
            if (!empty($names[$fallbackLocale])) {
                return $names[$fallbackLocale];
            }
        }
        
        // Return first available or empty string
        return !empty($names) ? reset($names) : '';
    }
    
    /**
     * Get page slug in specified language with fallback
     * 
     * @param string|null $locale
     * @return string
     */
    public function getSlug(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $slugs = $this->slug ?? [];
        
        // Ensure we have an array
        if (!is_array($slugs)) {
            return (string) $slugs;
        }
        
        // Try requested locale first
        if (!empty($slugs[$locale])) {
            return $slugs[$locale];
        }
        
        // Fallback priority: cs -> en -> first available
        $fallbackLocales = ['cs', 'en'];
        foreach ($fallbackLocales as $fallbackLocale) {
            if (!empty($slugs[$fallbackLocale])) {
                return $slugs[$fallbackLocale];
            }
        }
        
        // Return first available or empty string
        return !empty($slugs) ? reset($slugs) : '';
    }
    
    /**
     * Get page description in specified language with fallback
     * 
     * @param string|null $locale
     * @return string
     */
    public function getDescription(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $descriptions = $this->description ?? [];
        
        // Ensure we have an array
        if (!is_array($descriptions)) {
            return (string) $descriptions;
        }
        
        // Try requested locale first
        if (!empty($descriptions[$locale])) {
            return $descriptions[$locale];
        }
        
        // Fallback priority: cs -> en -> first available
        $fallbackLocales = ['cs', 'en'];
        foreach ($fallbackLocales as $fallbackLocale) {
            if (!empty($descriptions[$fallbackLocale])) {
                return $descriptions[$fallbackLocale];
            }
        }
        
        // Return first available or empty string
        return !empty($descriptions) ? reset($descriptions) : '';
    }

    /**
     * Get page content in specified language with fallback
     * 
     * @param string|null $locale
     * @return string
     */
    public function getContent(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $contents = $this->content ?? [];
        
        // Ensure we have an array
        if (!is_array($contents)) {
            return (string) $contents;
        }
        
        // Try requested locale first
        if (!empty($contents[$locale])) {
            return $contents[$locale];
        }
        
        // Fallback priority: cs -> en -> first available
        $fallbackLocales = ['cs', 'en'];
        foreach ($fallbackLocales as $fallbackLocale) {
            if (!empty($contents[$fallbackLocale])) {
                return $contents[$fallbackLocale];
            }
        }
        
        // Return first available or empty string
        return !empty($contents) ? reset($contents) : '';
    }

    /**
     * Get meta title in specified language with fallback
     * 
     * @param string|null $locale
     * @return string
     */
    public function getMetaTitle(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $metaTitles = $this->meta_title ?? [];
        
        // Ensure we have an array
        if (!is_array($metaTitles)) {
            return (string) $metaTitles;
        }
        
        // Try requested locale first
        if (!empty($metaTitles[$locale])) {
            return $metaTitles[$locale];
        }
        
        // Fallback priority: cs -> en -> first available, or use name as fallback
        $fallbackLocales = ['cs', 'en'];
        foreach ($fallbackLocales as $fallbackLocale) {
            if (!empty($metaTitles[$fallbackLocale])) {
                return $metaTitles[$fallbackLocale];
            }
        }
        
        // If no meta title, use page name
        return $this->getName($locale);
    }

    /**
     * Get meta description in specified language with fallback
     * 
     * @param string|null $locale
     * @return string
     */
    public function getMetaDescription(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $metaDescriptions = $this->meta_description ?? [];
        
        // Try requested locale first
        if (!empty($metaDescriptions[$locale])) {
            return $metaDescriptions[$locale];
        }
        
        // Fallback priority: cs -> en -> first available, or use description as fallback
        $fallbackLocales = ['cs', 'en'];
        foreach ($fallbackLocales as $fallbackLocale) {
            if (!empty($metaDescriptions[$fallbackLocale])) {
                return $metaDescriptions[$fallbackLocale];
            }
        }
        
        // If no meta description, use page description
        return $this->getDescription($locale);
    }

    /**
     * Get meta keywords in specified language with fallback
     * 
     * @param string|null $locale
     * @return string
     */
    public function getMetaKeywords(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $metaKeywords = $this->meta_keywords ?? [];
        
        // Ensure we have an array
        if (!is_array($metaKeywords)) {
            return (string) $metaKeywords;
        }
        
        // Try requested locale first
        if (!empty($metaKeywords[$locale])) {
            return $metaKeywords[$locale];
        }
        
        // Fallback priority: cs -> en -> first available
        $fallbackLocales = ['cs', 'en'];
        foreach ($fallbackLocales as $fallbackLocale) {
            if (!empty($metaKeywords[$fallbackLocale])) {
                return $metaKeywords[$fallbackLocale];
            }
        }
        
        // Return first available or empty string
        return !empty($metaKeywords) ? reset($metaKeywords) : '';
    }

    /**
     * Parent page relationship
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    /**
     * Child pages relationship
     */
    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Published by user relationship
     */
    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * Handle main_image upload
     */
    public function setMainImageAttribute($value)
    {
        $this->handleFileUpload('main_image', $value, 'pages', [
            'disk' => 'public',
            'createThumbnails' => true,
            'thumbnailWidth' => 300,
            'thumbnailHeight' => 200,
            'thumbnailPath' => 'thumbnails',
            'allowedFileTypes' => [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'jpeg', 'jpg', 'png', 'gif', 'webp',
            ],
            'maxFileSize' => 10240, // 10MB
        ]);
    }

    /**
     * Handle images (gallery) upload
     */
    public function setImagesAttribute($value)
    {
        $this->handleFileUpload('images', $value, 'pages/galleries', [
            'disk' => 'public',
            'createThumbnails' => true,
            'thumbnailWidth' => 200,
            'thumbnailHeight' => 200,
            'thumbnailPath' => 'thumbnails',
            'allowedFileTypes' => [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'jpeg', 'jpg', 'png', 'gif', 'webp',
            ],
            'maxFileSize' => 10240, // 10MB
            'multiple' => true,
        ]);
    }

    /**
     * Get main image URL
     */
    public function getMainImageUrlAttribute(): ?string
    {
        return $this->getAttributeFileUrl('main_image');
    }

    /**
     * Get main image thumbnail URL
     */
    public function getMainImageThumbUrlAttribute(): ?string
    {
        return $this->getThumbnailUrl('main_image');
    }

    /**
     * Get gallery images URLs
     */
    public function getImageUrlsAttribute(): array
    {
        if (empty($this->images)) {
            return [];
        }

        $urls = [];
        foreach ($this->images as $index => $image) {
            $urls[] = $this->getAttributeFileUrl('images', $index);
        }
        
        return array_filter($urls);
    }

    /**
     * Scope for published pages
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true)
            ->where(function ($q) {
                $q->where('publishing_start', '<=', now())
                  ->orWhereNull('publishing_start');
            })
            ->where(function ($q) {
                $q->where('publishing_end', '>=', now())
                  ->orWhereNull('publishing_end');
            });
    }

    /**
     * Scope for pages by category
     */
    public function scopeByCategory(Builder $query, $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope for root pages (no parent)
     */
    public function scopeRootPages(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Check if page is currently published
     */
    public function getIsCurrentlyPublishedAttribute(): bool
    {
        if (!$this->published) {
            return false;
        }

        $now = now();
        
        if ($this->publishing_start && $this->publishing_start > $now) {
            return false;
        }
        
        if ($this->publishing_end && $this->publishing_end < $now) {
            return false;
        }
        
        return true;
    }

    /**
     * Get breadcrumb trail for this page
     */
    public function getBreadcrumbsAttribute(): array
    {
        $breadcrumbs = [];
        $current = $this;
        
        while ($current) {
            array_unshift($breadcrumbs, [
                'name' => $current->getName(),
                'slug' => $current->getSlug(),
                'id' => $current->id,
            ]);
            $current = $current->parent;
        }
        
        return $breadcrumbs;
    }

    /**
     * Get URL to the receipt file
     * 
     * @param string $attribute
     * @return string|null
     */
    public function getFileUrl(string $attribute = 'pages', $index = null)
    {
        return $this->getAttributeFileUrl($attribute, $index);
    }
}
