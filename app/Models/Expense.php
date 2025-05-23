<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasFileUploads;
use App\Services\FileUploadService;
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
        // Get the service
        $fileUploadService = app(FileUploadService::class);
        
        // Current attachments
        $currentAttachments = $this->attachments ?? [];
        
        // Process removed files
        if (request()->has('attachments_removed')) {
            $removedFiles = json_decode(request()->input('attachments_removed'), true) ?? [];
            foreach ($removedFiles as $file) {
                // Remove from storage
                $fileUploadService->deleteFile($file, 'public');
                
                // Remove from current files array
                $currentAttachments = array_filter($currentAttachments, function($item) use ($file) {
                    return $item !== $file;
                });
            }
        }
        
        // Process new uploads
        if (request()->hasFile('attachments')) {
            $files = request()->file('attachments');
            $destinationPath = 'expenses/attachments/' . ($this->id ?? uniqid());
            
            foreach ($files as $file) {
                // Set options for file upload
                $options = [
                    'disk' => 'public',
                    'randomizeFilename' => true,
                    'allowedFileTypes' => [
                        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                        'jpeg', 'jpg', 'png', 'gif', 'webp',
                        'application/pdf', 'pdf',
                        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'doc', 'docx',
                        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'xls', 'xlsx',
                        'text/plain', 'txt'
                    ],
                    'maxFileSize' => 10240, // 10MB
                    'sanitizeFilename' => true,
                ];
                
                // Generate filename
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = strtolower($file->getClientOriginalExtension());
            
                // Remove diacritics and special characters
                $sanitizedName = $fileUploadService->sanitizeFilename($originalName);
            
                // Check for duplicate filenames
                $baseName = $sanitizedName;
                $counter = 1;
                $filename = $baseName . '.' . $extension;
            
                // Keep checking for duplicates until we find a unique name
                while (Storage::disk('public')->exists($destinationPath . '/' . $filename)) {
                    $filename = $baseName . '_' . $counter . '.' . $extension;
                    $counter++;
                }
            
                // Store the file with sanitized name
                $filePath = $file->storeAs($destinationPath, $filename, 'public');
                
                // Add to attachments array
                if ($filePath) {
                    $currentAttachments[] = $filePath;
                }
            }
        }
        
        // Keep current attachments if value is hidden input
        if (is_array($value) && isset($value[0]) && is_string($value[0])) {
            $this->attributes['attachments'] = json_encode($value);
        } else {
            // Set the attribute with the updated array
            $this->attributes['attachments'] = json_encode(array_values($currentAttachments));
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
