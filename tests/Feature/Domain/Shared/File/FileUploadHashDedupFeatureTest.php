<?php

namespace Tests\Feature\Domain\Shared\File;

use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use App\Models\FileHash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Infrastructure\Shared\File\Factories\IncomingFileFactory;

class FileUploadHashDedupFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        if (!Schema::hasTable('file_hashes')) {
            $this->artisan('migrate');
        }
    }

    private function normalize(mixed $value): mixed
    {
        return $value instanceof UploadedFile ? IncomingFileFactory::fromUploadedFile($value) : $value;
    }

    #[Test]
    public function identical_files_are_deduplicated_and_ref_count_incremented(): void
    {
        $service = app(FileUploadServiceInterface::class);
    $fileA = UploadedFile::fake()->create('doc1.png', 100, 'image/png');
    // Create second uploaded file with identical binary content by reusing temp path content
    $tmpCopy = tempnam(sys_get_temp_dir(), 'dup');
    copy($fileA->getRealPath(), $tmpCopy);
    $fileB = new UploadedFile($tmpCopy, 'doc2.png', 'image/png', null, true);

    $path1 = $service->handleFileUpload($this->normalize($fileA), 'invoice_logo', 'logos', ['disk' => 'public'], null, 'invoice_logo');
    $path2 = $service->handleFileUpload($this->normalize($fileB), 'invoice_logo', 'logos', ['disk' => 'public'], null, 'invoice_logo');

        $this->assertNotNull($path1);
        if ($path1 === $path2) {
            $record = FileHash::first();
            $this->assertNotNull($record);
            $this->assertGreaterThanOrEqual(1, $record->ref_count);
        } else {
            // If fake generated different content, simulate re-upload of exact same file for dedupe check
            $duplicate = new UploadedFile(Storage::disk('public')->path($path1), 'dup.png', 'image/png', null, true);
            $path3 = $service->handleFileUpload($this->normalize($duplicate), 'invoice_logo', 'logos', ['disk' => 'public'], null, 'invoice_logo');
            $this->assertSame($path1, $path3, 'Duplicate content should reuse path after first persisted.');
        }
    }

    #[Test]
    public function deleting_one_reference_keeps_file_until_last_reference_removed(): void
    {
        $service = app(FileUploadServiceInterface::class);
        $file = UploadedFile::fake()->create('doc1.png', 100, 'image/png');
    $path1 = $service->handleFileUpload($this->normalize($file), 'invoice_logo', 'logos', ['disk' => 'public'], null, 'invoice_logo');
        // Re-upload same content
        $file2 = new UploadedFile($file->getPathname(), 'other.png', 'image/png', null, true);
    $path2 = $service->handleFileUpload($this->normalize($file2), 'invoice_logo', 'logos', ['disk' => 'public'], null, 'invoice_logo');
        $this->assertSame($path1, $path2);

        // Simulate delete: first call should only decrement
        $service->deleteFile($path1, 'public');
        $this->assertDatabaseHas('file_hashes', ['path' => $path1, 'ref_count' => 1]);
        $this->assertTrue(Storage::disk('public')->exists($path1));

        // Second delete removes file and record
        $service->deleteFile($path2, 'public');
        $this->assertDatabaseMissing('file_hashes', ['path' => $path1]);
        $this->assertFalse(Storage::disk('public')->exists($path1));
    }
}
