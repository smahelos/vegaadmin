<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Traits\HasRoles;
use Carbon\Carbon;

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
        return $this->paymentStatus ? $this->paymentStatus->slug : null;
    }

    /**
     * Get client name with fallback for unknown client
     */
    public function getClientNameAttribute()
    {
        return $this->client->name ?? __('invoices.placeholders.unknown_client');
    }

    /**
     * Get CSS class for payment status display
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
        ];
        
        return $colorMap[$this->payment_status_slug] ?? 'gray';
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