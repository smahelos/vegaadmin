<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services;

use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use App\Domain\Shared\File\DTO\IncomingFile;
use RuntimeException;

/**
 * Logo files of invoices on the public disk; the invoice stores only the path.
 */
final class InvoiceLogoApplicationService
{
    private const DISK = 'public';

    private const CONTEXT = 'invoice_logo';

    public function __construct(
        private readonly FileUploadServiceInterface $files,
    ) {}

    /**
     * @return string the stored path
     */
    public function store(IncomingFile $file): string
    {
        $path = $this->files->handleFileUpload(
            $file,
            self::CONTEXT,
            'invoices/logos',
            [
                'disk' => self::DISK,
                'sanitizeFilename' => true,
                'randomizeFilename' => true,
            ],
            null,
            self::CONTEXT,
        );

        return $path ?? throw new RuntimeException('The logo was not stored');
    }
}
