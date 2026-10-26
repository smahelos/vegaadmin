<?php

declare(strict_types=1);

namespace App\Application\Party\Services;

use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use App\Domain\Shared\File\DTO\IncomingFile;
use RuntimeException;

/**
 * Logo files of businesses on the public disk; the business stores only the path.
 */
final class BusinessLogoApplicationService
{
    private const DISK = 'public';

    private const CONTEXT = 'business_logo';

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
            'businesses/logos',
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

    public function delete(string $path): void
    {
        $this->files->deleteFile($path, self::DISK);
    }
}
