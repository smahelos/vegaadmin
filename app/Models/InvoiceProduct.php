<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceProduct extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'invoice_id',
        'product_id',
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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'quantity' => 'float',
        'price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_custom_product' => 'boolean',
    ];

    /**
     * Get the invoice that owns the product
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the product if not custom
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate the tax amount
     */
    public function calculateTaxAmount()
    {
        $this->tax_amount = ($this->price * $this->quantity * $this->tax_rate) / 100;
        return $this->tax_amount;
    }

    /**
     * Calculate the total price
     */
    public function calculateTotalPrice()
    {
        $basePrice = $this->price * $this->quantity;
        $this->total_price = $basePrice + $this->tax_amount;
        return $this->total_price;
    }

    /**
     * Auto-calculate values on save
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (InvoiceProduct $invoiceProduct) {
            $invoiceProduct->calculateTaxAmount();
            $invoiceProduct->calculateTotalPrice();
        });
    }
}
