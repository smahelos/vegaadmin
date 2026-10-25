<?php

namespace Tests\Feature\Domain\Shared\File;

use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use App\Infrastructure\Shared\File\Factories\IncomingFileFactory;
use App\Models\FileHash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceLogoHashDedupIntegrationTest extends TestCase
{
    private function normalize(mixed $value): mixed
    {
        return $value instanceof UploadedFile ? IncomingFileFactory::fromUploadedFile($value) : $value;
    }

    #[Test]
    public function second_identical_invoice_logo_reuses_path(): void
    {
        Storage::fake('public');
        $service = app(FileUploadServiceInterface::class);
        $file1 = UploadedFile::fake()->image(
            'logo.png',
            120,
            120,
        );
        $tmpCopy = tempnam(sys_get_temp_dir(), 'dup');
        copy($file1->getRealPath(), $tmpCopy);
        $file2 = new UploadedFile(
            $tmpCopy,
            'other.png',
            'image/png',
            null,
            true,
        );

        $path1 = $service->handleFileUpload(
            $this->normalize($file1),
            'invoice_logo',
            'invoices/logos/' . uniqid(),
            ['disk' => 'public'],
            null,
            'invoice_logo',
        );
        $path2 = $service->handleFileUpload(
            $this->normalize($file2),
            'invoice_logo',
            'invoices/logos/' . uniqid(),
            ['disk' => 'public'],
            null,
            'invoice_logo',
        );

        $this->assertSame($path1, $path2);
        $record = FileHash::where('path', $path1)->first();
        $this->assertNotNull($record);
        $this->assertGreaterThanOrEqual(2, $record->ref_count);
    }
}
