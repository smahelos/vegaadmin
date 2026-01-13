<?php

namespace App\Application\Invoice\Services;

use App\Application\Invoice\Contracts\InvoiceRequestAssemblerInterface;
use App\Application\Invoice\DTO\InvoiceMutationPayload;
use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use App\Http\Requests\InvoiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class InvoiceRequestAssembler implements InvoiceRequestAssemblerInterface
{
    public function __construct(private readonly FileUploadServiceInterface $fileUploadService) {}

    public function assemble(?User $user, InvoiceRequest $request, bool $guest = false): InvoiceMutationPayload
    {
        $validated = $request->validated();

        // Decode products JSON (field name 'invoice-products')
        $rawProducts = $request->input('invoice-products');
        if (is_string($rawProducts)) {
            $decoded = json_decode($rawProducts, true);
            $products = is_array($decoded) ? $decoded : [];
        } elseif (is_array($rawProducts)) {
            $products = $rawProducts;
        } else {
            $products = [];
        }

        $payload = new InvoiceMutationPayload($validated, $products, null, locale: app()->getLocale(), guest: $guest);

        // Handle logo upload uniformly
        if ($request->hasFile('invoice_logo')) {
            $path = $this->handleInvoiceLogoUpload($request->file('invoice_logo'));
            if ($path) {
                $payload = $payload->withLogo($path);
            }
        }

        return $payload;
    }

    /**
     * Handle invoice logo upload
     */
    private function handleInvoiceLogoUpload(\Illuminate\Http\UploadedFile|string|null $file): ?string
    {
        if (!$file) { 
            return null; 
        }
        
        $value = $file instanceof \Illuminate\Http\UploadedFile 
            ? \App\Infrastructure\Shared\File\Factories\IncomingFileFactory::fromUploadedFile($file) 
            : $file;
            
        return $this->fileUploadService->handleFileUpload(
            $value,
            'invoice_logo',
            'invoices/logos/' . uniqid(),
            ['disk' => 'public', 'sanitizeFilename' => true, 'randomizeFilename' => false],
            null,
            'invoice_logo'
        );
    }
}
