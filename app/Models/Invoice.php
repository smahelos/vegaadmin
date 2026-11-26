<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;

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
        'issue_date' => 'datetime:Y-m-d',
        'tax_point_date' => 'datetime:Y-m-d',
        'due_in' => 'integer',
        'payment_amount' => 'decimal:2',
        'payment_status' => InvoiceStatus::class,
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    /**
     * Get the payment method associated with the invoice
     *
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    /**
     * Get the products related to this invoice
     *
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        $relation = $this->belongsToMany(Product::class, 'invoice_products');
        $relation->withPivot(
            [
                'name',
                'quantity',
                'price',
                'currency',
                'unit',
                'category',
                'description',
                'is_custom_product',
                'tax_rate',
            ],
        );
        $relation->withTimestamps();

        return $relation;
    }

    /**
     * Get all invoice products including custom products
     *
     * @return HasMany<InvoiceProduct, $this>
     */
    public function invoiceProducts(): HasMany
    {
        return $this->hasMany(InvoiceProduct::class);
    }

    /**
     * Get the user that owns the invoice
     *
     * @return BelongsTo<User, $this>
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
     * @return Attribute<?CarbonImmutable, never>
     */
    protected function dueDate(): Attribute
    {
        return Attribute::get(
            function (): ?CarbonImmutable {
                if ($this->issue_date === null || $this->due_in === null) {
                    return null;
                }
                $dueDate = InvoiceDueDate::fromIssueDateAndDays($this->issue_date->toDateTimeImmutable(), $this->due_in);

                return CarbonImmutable::instance($dueDate->date());
            },
        );
    }

    /**
     * Overdue is not stored: an unpaid invoice turns overdue the day after its due date.
     *
     * @return Attribute<bool, never>
     */
    protected function overdue(): Attribute
    {
        return Attribute::get(
            function (): bool {
                $status = $this->payment_status;
                if ($status === null || !$status->isUnpaid() || $this->issue_date === null || $this->due_in === null) {
                    return false;
                }
                $dueDate = InvoiceDueDate::fromIssueDateAndDays($this->issue_date->toDateTimeImmutable(), $this->due_in);

                $today = CarbonImmutable::today();

                return $dueDate->daysUntil($today->toDateTimeImmutable()) < 0;
            },
        );
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
}
