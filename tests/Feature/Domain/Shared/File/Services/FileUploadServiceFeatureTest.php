<?php

namespace Tests\Feature\Domain\Shared\File\Services;

use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use App\Infrastructure\Shared\File\Factories\IncomingFileFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FileUploadServiceFeatureTest extends TestCase
{
    private FileUploadServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FileUploadServiceInterface::class);

        // Use fake storage for testing
        Storage::fake('public');
    }

    /**
     * Convert UploadedFile to IncomingFile VO to match service signature.
     */
    private function normalize(mixed $value): mixed
    {
        return $value instanceof UploadedFile ? IncomingFileFactory::fromUploadedFile($value) : $value;
    }

    #[Test]
    public function delete_file_returns_true_for_existing_file(): void
    {
        // Create a fake file
        $filePath = 'test/test.txt';
        Storage::disk('public')->put($filePath, 'test content');

        $result = $this->service->deleteFile($filePath, 'public');

        $this->assertTrue($result);
        $this->assertFalse(Storage::disk('public')->exists($filePath));
    }

    #[Test]
    public function delete_file_returns_false_for_nonexistent_file(): void
    {
        $result = $this->service->deleteFile('nonexistent/file.txt', 'public');

        $this->assertFalse($result);
    }

    #[Test]
    public function delete_file_handles_null_path(): void
    {
        $result = $this->service->deleteFile(null, 'public');

        $this->assertFalse($result);
    }

    #[Test]
    public function sanitize_filename_removes_special_characters(): void
    {
        $input = 'file name with spaces & special chars!@#.txt';
        $result = $this->service->sanitizeFilename($input);

        $this->assertIsString($result);
        $this->assertDoesNotMatchRegularExpression('/[^a-zA-Z0-9._-]/', $result);
    }

    #[Test]
    public function sanitize_filename_preserves_extension(): void
    {
        $input = 'document.pdf';
        $result = $this->service->sanitizeFilename($input);

        $this->assertStringEndsWith('.pdf', $result);
    }

    #[Test]
    public function sanitize_filename_handles_czech_characters(): void
    {
        $input = 'soubor_s_česky_názvem.txt';
        $result = $this->service->sanitizeFilename($input);

        $this->assertIsString($result);
        $this->assertStringEndsWith('.txt', $result);
    }

    #[Test]
    public function handle_file_upload_returns_old_value_when_no_changes(): void
    {
        $oldValue = 'existing/file.txt';

        $result = $this->service->handleFileUpload(
            '',
            'document',
            'uploads',
            [],
            $oldValue,
        );

        $this->assertEquals($oldValue, $result);
    }

    #[Test]
    public function handle_file_upload_returns_null_when_file_removed(): void
    {
        // Simulate removal request
        request()->merge(['document_remove' => true]);

        $result = $this->service->handleFileUpload(
            null,
            'document',
            'uploads',
        );

        $this->assertNull($result);
    }

    #[Test]
    public function handle_file_upload_validates_file_size(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(trans('validation.file_upload.size_exceeded', ['attribute' => ':attribute']));

        // Create a mock uploaded file with size that exceeds limit
        // Note: The original code divides by 20480 instead of 1024, so we need to account for that
        $file = UploadedFile::fake()->create('large.txt', 50000); // 50MB file

        $this->service->handleFileUpload(
            $this->normalize($file),
            'document',
            'uploads',
            ['maxFileSize' => 1024],
        ); // 1MB limit
    }

    #[Test]
    public function handle_file_upload_validates_file_type(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(trans('validation.file_upload.type_not_allowed', ['attribute' => ':attribute']));

        $file = UploadedFile::fake()->create('document.exe', 100);

        $this->service->handleFileUpload(
            $this->normalize($file),
            'document',
            'uploads',
            ['allowedFileTypes' => ['pdf', 'txt']],
        );
    }

    #[Test]
    public function handle_file_upload_accepts_allowed_file_type(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $result = $this->service->handleFileUpload(
            $this->normalize($file),
            'document',
            'uploads',
            ['allowedFileTypes' => ['pdf', 'txt']],
        );

        $this->assertIsString($result);
        $this->assertStringContainsString('uploads/', $result);
        $this->assertStringEndsWith('.pdf', $result);
    }

    #[Test]
    public function handle_file_upload_throws_exception_for_disallowed_file_type(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create(
            'malware.exe',
            10,
            'application/octet-stream',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(__('validation.file_upload.type_not_allowed', ['attribute' => ':attribute']));

        $this->service->handleFileUpload(
            $this->normalize($file),
            'attachment',
            'uploads',
            [
                'disk' => 'public',
                'allowedFileTypes' => ['png', 'jpg'], // Only allow images
                'randomizeFilename' => false,
                'sanitizeFilename' => true,
            ],
            null,
            null,
        );
    }

    #[Test]
    public function handle_file_upload_throws_exception_when_file_exceeds_size_limit(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create(
            'large.png',
            500,
            'image/png',
        ); // 500 KB

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(__('validation.file_upload.size_exceeded', ['attribute' => ':attribute']));

        $this->service->handleFileUpload(
            $this->normalize($file),
            'attachment',
            'oversize',
            [
                'disk' => 'public',
                'allowedFileTypes' => ['png'],
                'maxFileSize' => 100, // set small limit to trigger exception
                'randomizeFilename' => false,
                'sanitizeFilename' => true,
            ],
            null,
            null,
        );
    }

    #[Test]
    public function thumbnail_is_created_for_image_when_enabled(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image(
            'photo.jpg',
            400,
            400,
        );

        $path = $this->service->handleFileUpload(
            $this->normalize($file),
            'image',
            'images',
            [
                'disk' => 'public',
                'createThumbnails' => true,
                'thumbnailWidth' => 100,
                'thumbnailHeight' => 80,
                'thumbnailPath' => 'thumbnails',
                'randomizeFilename' => false,
                'sanitizeFilename' => true,
                'allowedFileTypes' => [
                    'jpg',
                    'jpeg',
                    'png',
                ],
            ],
            null,
            null,
        );

        $this->assertNotNull($path);
        $this->assertTrue(Storage::disk('public')->exists($path));

        // Expected thumbnail path structure
        $this->assertTrue(Storage::disk('public')->exists('thumbnails/images/photo.jpg'), 'Thumbnail should be created at expected path.');
    }
}
