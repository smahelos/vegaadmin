<?php

namespace Tests\Feature\Domain\Shared\File;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use App\Infrastructure\Shared\File\Factories\IncomingFileFactory;

class FileUploadContextLimitsFeatureTest extends TestCase
{
    use RefreshDatabase;
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
        $file = UploadedFile::fake()->create('logo.png', 2600, 'image/png'); // 600 KB exceeds 2048 KB

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('Soubor překračuje maximální velikost pro :attribute.');

        $this->service->handleFileUpload(
            $this->normalize($file),
            'invoice_logo',
            'logos',
            [ 'disk' => 'public' ],
            null,
            'invoice_logo'
        );
    }

    #[Test]
    public function attachment_context_allows_larger_size(): void
    {
        // attachment max_kb = 20480 (20 MB)
        $file = UploadedFile::fake()->create('big.pdf', 15000, 'application/pdf'); // 15 MB

        $path = $this->service->handleFileUpload(
            $this->normalize($file),
            'attachments',
            'attachments',
            [ 'disk' => 'public', 'randomizeFilename' => false, 'sanitizeFilename' => true ],
            null,
            'attachment'
        );

        $this->assertNotNull($path);
        $this->assertTrue(Storage::disk('public')->exists($path));
    }

    #[Test]
    public function invoice_logo_allows_only_images(): void
    {
        $pdf = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('Tento typ souboru není pro :attribute povolen.');
        $this->service->handleFileUpload(
            $this->normalize($pdf),
            'invoice_logo',
            'logos',
            [ 'disk' => 'public' ],
            null,
            'invoice_logo'
        );
    }
}
