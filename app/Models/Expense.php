<?php

declare(strict_types=1);

namespace App\Models;

use App\Infrastructure\Shared\File\Traits\HasFileUploads;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use CrudTrait;
    use HasFactory;
    use HasFileUploads;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'business_id',
        'contact_id',
        'bank_account_id',
        'category_id',
        'expense_date',
        'amount',
        'currency',
        'payment_method_id',
        'reference_number',
        'description',
        'attachments',
        'tax_amount',
        'tax_included',
        'status_id',
        'supplier_logo',
        'supplier_name',
        'supplier_company_number',
        'supplier_registration_country',
        'supplier_vat_number',
        'supplier_street',
        'supplier_city',
        'supplier_postal_code',
        'supplier_country',
        'supplier_email',
        'supplier_phone',
        'customer_name',
        'customer_company_number',
        'customer_registration_country',
        'customer_vat_number',
        'customer_street',
        'customer_city',
        'customer_postal_code',
        'customer_country',
        'customer_email',
        'customer_phone',
        'account_iban',
        'account_number',
        'account_bank_code',
        'account_country',
        'account_swift',
        'account_bank_name',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_included' => 'boolean',
        'attachments' => 'array',
    ];

    /**
     * The business whose purchase this document records.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Get the user who recorded this expense.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tax who recorded this expense.
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Get the status of this expense.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Get the category of this expense.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    /**
     * Get the payment method used for this expense.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function getFileUrl(string $attribute = 'attachments', ?int $index = null): ?string
    {
        return $this->getAttributeFileUrl($attribute, $index);
    }
}
