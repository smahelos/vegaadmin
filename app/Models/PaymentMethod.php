<?php

namespace App\Models;

use App\Domain\Payment\Enums\PaymentMethodCode;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class PaymentMethod extends Model
{
    use CrudTrait;
    use HasRoles;

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

    /**
     * Get the expenses in this payment method.
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class, 'payment_method_id');
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
    public function getTranslatedNameAttribute(): string
    {
        return __('payment_methods.methods.' . PaymentMethodCode::from($this->slug)->value);
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
