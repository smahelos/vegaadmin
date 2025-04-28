<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class PaymentMethod extends Model
{
    use CrudTrait;
    use HasRoles;
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */
    protected $table = 'payment_methods';
    protected $guarded = ['id'];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    /**
     * Many-to-many relationship with invoices
     */
    public function invoices()
    {
        return $this->belongsToMany('App\Models\Invoice', 'invoices');
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
    /**
     * Get translated name of payment method
     * 
     * @return string
     */
    public function getTranslatedNameAttribute()
    {
        // Check if payment method has defined slug
        if (empty($this->slug)) {
            return $this->name ?? __('invoices.placeholders.not_available');
        }
        
        // Create translation key based on slug
        $translationKey = 'payment_methods.' . $this->slug;
        
        // Get translation
        $translation = __($translationKey);
        
        // If translation doesn't exist (returns original key), use original name
        return ($translation === $translationKey) ? $this->name : $translation;
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
}