<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Traits\HasRoles;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\InvoiceProduct;
use Illuminate\Support\Facades\Log;

class Invoice extends Model
{
    use CrudTrait;
    use HasFactory;
    use HasRoles;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */
    protected $table = 'invoices';
    protected $guarded = ['id'];
    protected $casts = [
        'issue_date' => 'date',
        'tax_point_date' => 'date',
        'due_in' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    /**
     * Many-to-many relationship with clients
     */
    public function clients()
    {
        return $this->belongsToMany('App\Models\Client', 'clients');
    }

    /**
     * Many-to-many relationship with suppliers
     */
    public function suppliers()
    {
        return $this->belongsToMany('App\Models\Supplier', 'suppliers');
    }

    /**
     * Many-to-many relationship with statuses
     */
    public function statuses()
    {
        return $this->belongsToMany('App\Models\Status', 'statuses')
            ->withPivot('name', 'slug');
    }

    /**
     * Get the client associated with the invoice
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Get the supplier associated with the invoice
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the payment method associated with the invoice
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paymentMethod()
    {
        return $this->belongsTo(\App\Models\PaymentMethod::class, 'payment_method_id');
    }

    /**
     * Get the products related to this invoice
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'invoice_products')
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
     * Get all invoice products including custom products
     */
    public function invoiceProducts()
    {
        return $this->hasMany(InvoiceProduct::class);
    }

    /**
     * Get the user that owns the invoice
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment status of the invoice
     */
    public function paymentStatus()
    {
        return $this->belongsTo(\App\Models\Status::class, 'payment_status_id');
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
    /**
     * Legacy alias for backward compatibility with code using paymentMethods()
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function paymentMethods()
    {
        if ($this->relationLoaded('paymentMethod') && $this->paymentMethod) {
            return collect([$this->paymentMethod]);
        } elseif ($this->payment_method_id) {
            $paymentMethod = $this->paymentMethod()->first();
            return collect([$paymentMethod]);
        }
        
        return collect([]);
    }

    /**
     * Calculate invoice total amount
     *
     * @return float
     */
    public function calculateTotalAmount()
    {
        try {
            if ($this->relationLoaded('invoiceProducts') && $this->invoiceProducts->isNotEmpty()) {
                $total = $this->invoiceProducts->sum('total_price');
            } else {
                // Check if the relation is loaded
                if (!$this->exists) {
                    return 0.0;
                }
                
                $total = $this->invoiceProducts()->sum('total_price');
            }
            
            // Update the invoice header
            $this->payment_amount = $total;
            $this->save();
            
            return $total;
        } catch (\Exception $e) {
            \Log::error('Error calculating total amount: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Get translated payment status name
     *
     * @return string
     */
    public function getPaymentStatusNameAttribute()
    {
        return $this->paymentStatus ? $this->paymentStatus->translated_name : __('invoices.placeholders.not_available');
    }

    /**
     * Get payment status slug
     */
    public function getPaymentStatusSlugAttribute()
    {
        return $this->paymentStatus ? $this->paymentStatus->slug : 'unknown';
    }

    /**
     * Get client name with fallback for unknown client
     */
    public function getClientNameAttribute()
    {
        // Check if the relation is loaded
        if ($this->relationLoaded('client') && $this->client) {
            return $this->client->name;
        } elseif ($this->client_id) {
            // If client is needed (lazy loading)
            $client = $this->client()->first();
            return $client ? $client->name : __('invoices.placeholders.unknown_client');
        }
    
        return __('invoices.placeholders.unknown_client');
    }

    /**
     * Get CSS class for payment status display with fallback
     * 
     * @return string
     */
    public function getStatusColorClassAttribute()
    {
        if (!$this->paymentStatus) {
            return 'gray';
        }

        // Prioritize color from database if exists
        if ($this->paymentStatus->color) {
            return $this->paymentStatus->color;
        }
        
        $colorMap = [
            'paid' => 'green',
            'pending' => 'yellow',
            'overdue' => 'red',
            'partially_paid' => 'blue',
            'canceled' => 'gray',
            'unknown' => 'gray',
        ];
    
        $slug = $this->getPaymentStatusSlugAttribute();

        return $colorMap[$slug] ?? 'gray';
    }

    /**
     * Calculate due date based on issue date and due days
     *
     * @return \Carbon\Carbon|null
     */
    public function getDueDateAttribute()
    {
        if ($this->issue_date && $this->due_in) {
            return Carbon::parse($this->issue_date)->addDays($this->due_in);
        }
        
        if ($this->payment_draft_date) {
            return Carbon::parse($this->payment_draft_date);
        }
        
        return null;
    }

    /**
     * Sync products from invoice_text JSON data
     */
    public function syncProductsFromJson()
    {
        $productsData = [];
        $jsonData = [];
        
        try {
            if (!empty($this->invoice_text)) {
                $jsonData = json_decode($this->invoice_text, true);
            }
        } catch (\Exception $e) {
            \Log::error('Error parsing invoice_text JSON: ' . $e->getMessage());
            return;
        }
        
        // If items are present in the JSON data
        // and are in the expected format, proceed with syncing
        if (isset($jsonData['items']) && is_array($jsonData['items'])) {
            foreach ($jsonData['items'] as $item) {
                if (isset($item['product_id']) && !empty($item['product_id'])) {
                    $productId = (int) $item['product_id'];
                    
                    // Prepare data for sync
                    $productsData[$productId] = [
                        'quantity' => (float) ($item['quantity'] ?? 1),
                        'price' => (float) ($item['price'] ?? 0),
                        'tax_rate' => (float) ($item['tax'] ?? 0)
                    ];
                }
            }
        }
        
        // Synchronizing products with pivot table
        if (!empty($productsData)) {
            $this->products()->sync($productsData);
        }
    }

    /**
     * Get total price without tax
     */
    public function getSubtotalAttribute()
    {
        try {
            if (!$this->relationLoaded('invoiceProducts') || $this->invoiceProducts->isEmpty()) {
                // Check if the relation exists
                if (!$this->exists) {
                    return 0.0;
                }
                
                return $this->invoiceProducts()->sum(\DB::raw('price * quantity')) ?: 0.0;
            }
            
            return $this->invoiceProducts->sum(function($item) {
                return ($item->price ?? 0) * ($item->quantity ?? 0);
            });
        } catch (\Exception $e) {
            \Log::error('Error calculating subtotal: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Get total tax amount
     */
    public function getTotalTaxAttribute()
    {
        try {
            if (!$this->relationLoaded('invoiceProducts') || $this->invoiceProducts->isEmpty()) {
                // Check if the relation exists
                if (!$this->exists) {
                    return 0.0;
                }
                
                return $this->invoiceProducts()->sum('tax_amount') ?: 0.0;
            }
            
            return $this->invoiceProducts->sum('tax_amount') ?: 0.0;
        } catch (\Exception $e) {
            \Log::error('Error calculating total tax: ' . $e->getMessage());
            return 0.0;
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
    
    /**
     * Get supplier name with fallback for unknown supplier.
     */
    protected function supplierName(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Kontrola existence vztahu supplier
                if ($this->relationLoaded('supplier') && $this->supplier) {
                    return $this->supplier->name;
                } elseif ($this->supplier_id) {
                    // Lazy loading pokud je potřeba
                    $supplier = $this->supplier()->first();
                    return $supplier ? $supplier->name : __('invoices.placeholders.unknown_supplier');
                }
                
                return __('invoices.placeholders.unknown_supplier');
            },
        );
    }

    /**
     * Get all products (regular and custom) associated with this invoice
     * 
     * @return array
     */
    public function getInvoiceProductsDataAttribute(): array
    {
        $products = [];
    
        // Ensure the relation is loaded
        $invoiceProducts = $this->relationLoaded('invoiceProducts') ? 
            $this->invoiceProducts : $this->invoiceProducts()->get();

            Log::info('Invoice Products:', ['invoiceProducts' => $invoiceProducts->toArray()]);
        
        foreach ($invoiceProducts as $invoiceProduct) {
            $productData = [
                'id' => $invoiceProduct->id,
                'product_id' => $invoiceProduct->product_id,
                'name' => $invoiceProduct->name,
                'quantity' => $invoiceProduct->quantity,
                'unit' => $invoiceProduct->unit,
                'price' => $invoiceProduct->price,
                'currency' => $invoiceProduct->currency,
                'tax_rate' => $invoiceProduct->tax_rate,
                'tax_amount' => $invoiceProduct->tax_amount,
                'total_price' => $invoiceProduct->total_price,
                'is_custom_product' => $invoiceProduct->is_custom_product,
            ];
            
            // Add product details if this is not a custom product
            if (!$invoiceProduct->is_custom_product && $invoiceProduct->product) {
                $productData['product'] = [
                    'id' => $invoiceProduct->product->id,
                    'name' => $invoiceProduct->product->name,
                    // Add other product fields as needed
                ];
            }
            
            $products[] = $productData;
        }
        
        return $products;
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
