<?php

namespace Tests\Feature\Domain\Shared\File;

use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use App\Infrastructure\Shared\File\Factories\IncomingFileFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FileUploadContextLimitsFeatureTest extends TestCase
{
    private FileUploadServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = app(FileUploadServiceInterface::class);
    }

    private function normalize(mixed $value): mixed
    {
        return $value instanceof UploadedFile ? IncomingFileFactory::fromUploadedFile($value) : $value;
    }

    #[Test]
    public function invoice_logo_context_enforces_stricter_size_limit(): void
    {
        // invoice_logo max_kb = 2048 (config)
        $fileFactory = UploadedFile::fake();
        $file = $fileFactory->create(
            'logo.png',
            2600,
            'image/png',
        ); // 600 KB exceeds 2048 KB

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(
            __('validation.file_upload.size_exceeded', ['attribute' => ':attribute']),
        );

        $this->service->handleFileUpload(
            $this->normalize($file),
            'invoice_logo',
            'logos',
            ['disk' => 'public'],
            null,
            'invoice_logo',
        );
    }

    #[Test]
    public function attachment_context_allows_larger_size(): void
    {
        // attachment max_kb = 20480 (20 MB)
        $fileFactory = UploadedFile::fake();
        $file = $fileFactory->create(
            'big.pdf',
            15000,
            'application/pdf',
        ); // 15 MB

        $path = $this->service->handleFileUpload(
            $this->normalize($file),
            'attachments',
            'attachments',
            [
                'disk' => 'public',
                'randomizeFilename' => false,
                'sanitizeFilename' => true,
            ],
            null,
            'attachment',
        );

        $this->assertNotNull($path);
        $disk = Storage::disk('public');
        $this->assertTrue(
            $disk->exists($path),
        );
    }

    #[Test]
    public function invoice_logo_allows_only_images(): void
    {
        $fileFactory = UploadedFile::fake();
        $pdf = $fileFactory->create(
            'doc.pdf',
            100,
            'application/pdf',
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(
            __('validation.file_upload.type_not_allowed', ['attribute' => ':attribute']),
        );
        $this->service->handleFileUpload(
            $this->normalize($pdf),
            'invoice_logo',
            'logos',
            ['disk' => 'public'],
            null,
            'invoice_logo',
        );
    }
}
