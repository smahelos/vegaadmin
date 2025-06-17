<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use App\Traits\HasFileUploads;

class Product extends Model
{
    use HasFactory, Sluggable, CrudTrait, HasFileUploads;

    protected $fillable = [
        'name',
        'slug',
        'user_id',
        'price',
        'tax_id',
        'category_id',
        'supplier_id',
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
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function (self $model) {
            // If this product is set as default, unset default status for other products
            if ($model->is_default) {
                self::where('user_id', $model->user_id)
                    ->where('id', '!=', $model->id)
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
                'source' => 'name'
            ]
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
    public function invoices()
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
                        'total_price'
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
    public function tax()
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
    public function getInvoiceCountAttribute()
    {
        return $this->invoices()->count();
    }

    /**
     * Handle image upload, replacement and deletion
     *
     * @param mixed $value
     * @return void
     */
    public function setImageAttribute($value)
    {
        $this->handleFileUpload('image', $value, 'products', [
            'disk' => 'public',
            'createThumbnails' => true,
            'thumbnailWidth' => 200,
            'thumbnailHeight' => 200,
            'thumbnailPath' => 'thumbnails',
            'allowedFileTypes' => [
                // Images
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'jpeg', 'jpg', 'png', 'gif', 'webp',
            ],
            'maxFileSize' => 10240, // 10MB max file size
        ]);
    }

    /**
     * Get URL to the image file
     * 
     * @param string $attribute
     * @return string|null
     */
    public function getFileUrl(string $attribute = 'image', $index = null)
    {
        return $this->getAttributeFileUrl($attribute, $index);
    }

    /**
     * Get a URL to the image thumbnail file
     * 
     * @return string|null
     */
    public function getImageThumbUrl()
    {
        if (!empty($this->image)) {
            return Storage::disk('public')->url($this->image);
        }
        return null;
    }
}
