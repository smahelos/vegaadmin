<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Infrastructure\Shared\File\Traits\HasFileUploads;
use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use App\Infrastructure\Shared\File\Factories\IncomingFileFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
        'supplier_id',
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
        'status_id'
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
     * Get the supplier associated with this expense.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who recorded this expense.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tax who recorded this expense.
     */
    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Get the status of this expense.
     */
    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Get the category of this expense.
     */
    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    /**
     * Get the payment method used for this expense.
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    /**
     * Handle multiple files upload for attachments
     *
     * @param mixed $value
     * @return void
     */
    public function setAttachmentsAttribute($value)
    {
        $fileUploadService = app(FileUploadServiceInterface::class);
        $current = $this->attachments ?? [];

        // Handle removals
        if (request()->has('attachments_removed')) {
            $removed = json_decode(request()->input('attachments_removed'), true) ?? [];
            foreach ($removed as $file) {
                $fileUploadService->deleteFile($file, 'public');
                $current = array_values(array_filter($current, fn($f) => $f !== $file));
            }
        }

        // Process new uploads via context aware service (context: attachment)
        if (request()->hasFile('attachments')) {
            $destinationPath = 'expenses/attachments/' . ($this->id ?? uniqid());
            foreach (request()->file('attachments') as $file) {
                $stored = $fileUploadService->handleFileUpload(
                    $file instanceof UploadedFile ? IncomingFileFactory::fromUploadedFile($file) : $file,
                    'attachments',
                    $destinationPath,
                    [
                        'disk' => 'public',
                        'randomizeFilename' => false, // unique ensured by generateUniqueFilename in service when sanitize true
                        'sanitizeFilename' => true,
                        'createThumbnails' => false,
                    ],
                    null,
                    'attachment'
                );
                if ($stored) { $current[] = $stored; }
            }
        }

        if (is_array($value) && isset($value[0]) && is_string($value[0])) {
            $this->attributes['attachments'] = json_encode($value);
        } else {
            $this->attributes['attachments'] = json_encode(array_values($current));
        }
    }

    /**
     * Get URL to the receipt file
     *
     * @param string $attribute
     * @return string|null
     */
    public function getFileUrl(string $attribute = 'attachments', $index = null)
    {
        return $this->getAttributeFileUrl($attribute, $index);
    }
}
