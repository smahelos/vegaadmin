<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends Model
{
    use CrudTrait;
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */
    protected $table = 'suppliers';
    protected $guarded = ['id'];
    
    protected $casts = [
        'is_default' => 'boolean',
        'has_payment_info' => 'boolean',
    ];

    /**
     * Boot method for the model
     * Sets all other suppliers of the user to is_default = false
     * if this supplier is set as default
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function (self $supplier) {
            // Update payment info flag
            $supplier->has_payment_info = $supplier->hasCompletePaymentInfo();
            
            // If this supplier is set as default, unset default status for other suppliers
            if ($supplier->is_default) {
                self::where('user_id', $supplier->user_id)
                    ->where('id', '!=', $supplier->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    /**
     * Get invoices associated with this supplier
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the user that owns the supplier
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
    /**
     * Get the full address of the supplier
     */
    public function getFullAddressAttribute()
    {
        return "{$this->street}, {$this->zip} {$this->city}, {$this->country}";
    }

    /**
     * Get the full name with shortcut of the supplier
     */
    public function getFullNameAttribute()
    {
        return "{$this->name} ({$this->shortcut})";
    }

    /**
     * Format account number in number/code format
     * 
     * @return string|null
     */
    public function getFullAccountNumberAttribute(): ?string
    {
        if (!empty($this->account_number) && !empty($this->bank_code)) {
            return $this->account_number . '/' . $this->bank_code;
        }
        return null;
    }

    /**
     * Check if supplier has complete payment information for QR payment
     *
     * @return bool
     */
    public function hasCompletePaymentInfo(): bool
    {
        return !empty($this->account_number) && !empty($this->bank_code);
    }

    /**
     * Determine if supplier has any payment information for QR code
     *
     * @return bool
     */
    public function getHasPaymentInfoAttribute(): bool
    {
        return (!empty($this->account_number) && !empty($this->bank_code)) || !empty($this->iban);
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
     * Mutator for is_default attribute to ensure boolean value
     */
    public function setIsDefaultAttribute($value)
    {
        $this->attributes['is_default'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}