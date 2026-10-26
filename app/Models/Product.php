<?php

namespace App\Models;

use App\Infrastructure\Shared\File\Traits\HasFileUploads;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use CrudTrait, HasFactory, HasFileUploads, Sluggable;

    protected $fillable = [
        'name',
        'slug',
        'user_id',
        'price',
        'unit',
        'currency',
        'tax_id',
        'category_id',
        'description',
        'is_default',
        'is_active',
        'image',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Boot method for the model
     * Sets all other products of the user to is_default = false
     * if this product is set as default
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $model): void {
            // If this product is set as default, unset default status for other products
            if ($model->is_default) {
                self::where('user_id', $model->user_id)
                    ->where(
                        'id',
                        '!=',
                        $model->id,
                    )
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Return the sluggable configuration array for this model.
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    /**
     * Get invoices associated with this product through the pivot table
     */
    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class, 'invoice_products')
            ->withPivot([
                'name',
                'quantity',
                'price',
                'currency',
                'unit',
                'category',
                'description',
                'is_custom_product',
                'tax_rate',
                'tax_amount',
                'total_price',
            ])
            ->withTimestamps();
    }

    /**
     * Get the user who owns the product.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the supplier, who sells the product.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the tax applied to the product.
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    /**
     * Get the category of the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Get the count of invoices that include this product
     */
    public function getInvoiceCountAttribute(): int
    {
        return $this->invoices()->count();
    }

    /**
     * Handle image upload, replacement and deletion
     */
    public function setImageAttribute(mixed $value): void
    {
        // Use standardized context-based handling (product_image)
        $this->handleFileUpload(
            'image',
            $value,
            'products/' . ($this->id ?? uniqid()),
            [
                'disk' => 'public',
                'sanitizeFilename' => true,
                'randomizeFilename' => false,
            ],
            'product_image',
        );
    }

    /**
     * Get URL to the image file
     */
    public function getFileUrl(string $attribute = 'image', ?int $index = null): ?string
    {
        return $this->getAttributeFileUrl($attribute, $index);
    }

    /**
     * Get a URL to the image thumbnail file
     */
    public function getImageThumbUrl(): ?string
    {
        if (! empty($this->image)) {
            return Storage::disk('public')->path($this->image);
        }

        return null;
    }
}
