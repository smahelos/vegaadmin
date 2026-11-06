<?php

namespace App\Models;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
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
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    /**
     * Get the payment method associated with the invoice
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    /**
     * Get the products related to this invoice
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
                'tax_amount',
                'total_price',
            ],
        );
        $relation->withTimestamps();

        return $relation;
    }

    /**
     * Get all invoice products including custom products
     */
    public function invoiceProducts(): HasMany
    {
        return $this->hasMany(InvoiceProduct::class);
    }

    /**
     * Get the user that owns the invoice
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment status of the invoice
     */
    public function paymentStatus(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'payment_status_id');
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
    /**
     * Get translated payment status name
     */
    public function getPaymentStatusNameAttribute(): string
    {
        return $this->paymentStatus ? $this->paymentStatus->translated_name : __('invoices.placeholders.not_available');
    }

    /**
     * Get CSS class for payment status display with fallback
     */
    public function getStatusColorClassAttribute(): string
    {
        return $this->paymentStatus?->color ?: 'gray';
    }

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

    protected function paid(): Attribute
    {
        return Attribute::get(
            fn (): bool => $this->invoiceStatus() === InvoiceStatus::PAID,
        );
    }

    /**
     * Overdue is not stored: an unpaid invoice turns overdue the day after its due date.
     */
    protected function overdue(): Attribute
    {
        return Attribute::get(
            function (): bool {
                $status = $this->invoiceStatus();
                if ($status === null || !$status->isUnpaid() || $this->issue_date === null || $this->due_in === null) {
                    return false;
                }
                $dueDate = InvoiceDueDate::fromIssueDateAndDays($this->issue_date->toDateTimeImmutable(), $this->due_in);

                $today = CarbonImmutable::today();

                return $dueDate->daysUntil($today->toDateTimeImmutable()) < 0;
            },
        );
    }

    private function invoiceStatus(): ?InvoiceStatus
    {
        return InvoiceStatus::tryFrom($this->paymentStatus?->slug ?? '');
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
     * Get all products (regular and custom) associated with this invoice
     */
    public function getInvoiceProductsDataAttribute(): array
    {
        $products = [];

        // Ensure the relation is loaded
        if ($this->relationLoaded('invoiceProducts')) {
            $invoiceProducts = $this->invoiceProducts;
        } else {
            $relation = $this->invoiceProducts();
            $invoiceProducts = $relation->get();
        }

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

    public function getPaymentAmountMoneyAttribute(): Money
    {
        // The decimal cast reads the column as a decimal string, so the amount never passes through a float
        $amount = $this->payment_amount ?? '0';
        $currency = $this->payment_currency ?: 'CZK';

        return Money::fromString(
            $amount,
            strtoupper($currency),
        );
    }

    public function getSubtotalMoneyAttribute(): Money
    {
        return $this->totals()->subtotal;
    }

    public function getTotalTaxMoneyAttribute(): Money
    {
        return $this->totals()->tax;
    }

    /** Totals from the stored lines: base recomputed through Money, VAT as stored with each line. */
    private function totals(): InvoiceTotals
    {
        $totals = InvoiceTotals::zero(
            Currency::from(
                strtoupper($this->payment_currency ?: Currency::CZK->value),
            ),
        );
        foreach ($this->invoiceProducts as $line) {
            $price = Money::fromString(
                (string) $line->price,
                strtoupper($line->currency),
            );
            $totals = $totals->withLine(
                $price->multiplyBy((string) $line->quantity),
                Money::fromString(
                    (string) $line->tax_amount,
                    strtoupper($line->currency),
                ),
            );
        }

        return $totals;
    }
}
