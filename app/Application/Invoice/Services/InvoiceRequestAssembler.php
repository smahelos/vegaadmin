<?php

namespace App\Application\Invoice\Services;

use App\Application\Invoice\Contracts\InvoiceRequestAssemblerInterface;
use App\Application\Invoice\DTO\InvoiceMutationPayload;
use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use App\Http\Requests\InvoiceRequest;
use App\Infrastructure\Shared\File\Factories\IncomingFileFactory;
use Illuminate\Http\UploadedFile;

class InvoiceRequestAssembler implements InvoiceRequestAssemblerInterface
{
    public function __construct(
        private readonly FileUploadServiceInterface $fileUploadService,
        private readonly InvoiceProductWriteDataAssembler $products,
    ) {}

    public function assemble(InvoiceRequest $request): InvoiceMutationPayload
    {
        $validated = $request->validated();

        $products = $this->products->fromValidated($validated['invoice-products']);

        $payload = new InvoiceMutationPayload($validated, $products);

        // Handle logo upload uniformly
        if (array_key_exists('invoice_logo', $validated) && $request->hasFile('invoice_logo')) {
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
    private function handleInvoiceLogoUpload(UploadedFile|string|null $file): ?string
    {
        if (! $file) {
            return null;
        }

        $value = $file instanceof UploadedFile
            ? IncomingFileFactory::fromUploadedFile($file)
            : $file;

        return $this->fileUploadService->handleFileUpload(
            $value,
            'invoice_logo',
            'invoices/logos/' . uniqid(),
            [
                'disk' => 'public',
                'sanitizeFilename' => true,
                'randomizeFilename' => false,
            ],
            null,
            'invoice_logo',
        );
    }
}
