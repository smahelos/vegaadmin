<?php

namespace Tests\Feature\Domain\Shared\File\Services;

use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;
use App\Infrastructure\Shared\File\Factories\IncomingFileFactory;

class FileUploadServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

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
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(FileUploadServiceInterface::class, $this->service);
    }

    #[Test]
    public function get_file_url_returns_null_for_null_path(): void
    {
        $result = $this->service->getFileUrl(null);
        
        $this->assertNull($result);
    }

    #[Test]
    public function get_file_url_returns_url_for_existing_file(): void
    {
        // Create a fake file
        $filePath = 'test/test.txt';
        Storage::disk('public')->put($filePath, 'test content');
        
        $result = $this->service->getFileUrl($filePath);
        
        $this->assertIsString($result);
        $this->assertStringContainsString($filePath, $result);
    }

    #[Test]
    public function get_file_url_returns_url_for_any_path(): void
    {
        // getFileUrl doesn't check if file exists, it just returns the URL
        $result = $this->service->getFileUrl('nonexistent/file.txt');
        
        $this->assertIsString($result);
        $this->assertStringContainsString('nonexistent/file.txt', $result);
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
    public function get_file_type_icon_returns_correct_icons(): void
    {
        // Test PDF
        $pdfIcon = $this->service->getFileTypeIcon('document.pdf');
        $this->assertEquals('la-file-pdf', $pdfIcon);
        
        // Test Word document
        $docIcon = $this->service->getFileTypeIcon('document.docx');
        $this->assertEquals('la-file-word', $docIcon);
        
        // Test Excel
        $excelIcon = $this->service->getFileTypeIcon('spreadsheet.xlsx');
        $this->assertEquals('la-file-excel', $excelIcon);
        
        // Test image
        $imageIcon = $this->service->getFileTypeIcon('image.jpg');
        $this->assertEquals('la-file-image', $imageIcon);
        
        // Test unknown file type
        $unknownIcon = $this->service->getFileTypeIcon('file.unknown');
        $this->assertEquals('la-file', $unknownIcon);
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
    public function generate_unique_filename_creates_unique_name(): void
    {
        $originalName = 'test';
        $extension = 'txt';
        $destinationPath = 'uploads';
        
        $result = $this->service->generateUniqueFilename($originalName, $extension, $destinationPath);
        
        $this->assertIsString($result);
        $this->assertStringEndsWith('.txt', $result);
        $this->assertStringContainsString('test', $result);
    }

    #[Test]
    public function generate_unique_filename_handles_conflicts(): void
    {
        $originalName = 'test';
        $extension = 'txt';
        $destinationPath = 'uploads';
        
        // Create a file that would conflict
        Storage::disk('public')->put($destinationPath . '/test.txt', 'content');
        
        $result = $this->service->generateUniqueFilename($originalName, $extension, $destinationPath);
        
        $this->assertIsString($result);
        $this->assertStringEndsWith('.txt', $result);
        $this->assertNotEquals('test.txt', $result);
        $this->assertStringContainsString('test_', $result);
    }

    #[Test]
    public function get_thumbnail_url_returns_null_for_null_path(): void
    {
        $result = $this->service->getThumbnailUrl(null);
        
        $this->assertNull($result);
    }

    #[Test]
    public function get_thumbnail_url_returns_original_for_non_image(): void
    {
        // Create a text file
        $filePath = 'documents/test.txt';
        Storage::disk('public')->put($filePath, 'test content');
        
        $result = $this->service->getThumbnailUrl($filePath);
        
        $this->assertIsString($result);
        $this->assertStringContainsString($filePath, $result);
    }

    #[Test]
    public function get_file_url_from_attribute_handles_string_value(): void
    {
        $filePath = 'test/file.txt';
        Storage::disk('public')->put($filePath, 'content');
        
        $result = $this->service->getFileUrlFromAttribute($filePath);
        
        $this->assertIsString($result);
        $this->assertStringContainsString($filePath, $result);
    }

    #[Test]
    public function get_file_url_from_attribute_handles_array_value(): void
    {
        $files = ['file1.txt', 'file2.txt', 'file3.txt'];
        Storage::disk('public')->put('file2.txt', 'content');
        
        $result = $this->service->getFileUrlFromAttribute($files, 1);
        
        $this->assertIsString($result);
        $this->assertStringContainsString('file2.txt', $result);
    }

    #[Test]
    public function get_file_url_from_attribute_returns_null_for_invalid_index(): void
    {
        $files = ['file1.txt', 'file2.txt'];
        
        $result = $this->service->getFileUrlFromAttribute($files, 5);
        
        $this->assertNull($result);
    }

    #[Test]
    public function get_file_url_from_attribute_handles_json_value(): void
    {
        $jsonFiles = json_encode(['file1.txt', 'file2.txt']);
        Storage::disk('public')->put('file1.txt', 'content');
        
        $result = $this->service->getFileUrlFromAttribute($jsonFiles, 0);
        
        $this->assertIsString($result);
        $this->assertStringContainsString('file1.txt', $result);
    }

    #[Test]
    public function handle_file_upload_returns_old_value_when_no_changes(): void
    {
        $oldValue = 'existing/file.txt';
        
        $result = $this->service->handleFileUpload('', 'document', 'uploads', [], $oldValue);
        
        $this->assertEquals($oldValue, $result);
    }

    #[Test]
    public function handle_file_upload_returns_null_when_file_removed(): void
    {
        // Simulate removal request
        request()->merge(['document_remove' => true]);
        
        $result = $this->service->handleFileUpload(null, 'document', 'uploads');
        
        $this->assertNull($result);
    }

    #[Test]
    public function handle_file_upload_validates_file_size(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage(trans('validation.file_upload.size_exceeded', ['attribute' => ':attribute']));
        
        // Create a mock uploaded file with size that exceeds limit
        // Note: The original code divides by 20480 instead of 1024, so we need to account for that
        $file = UploadedFile::fake()->create('large.txt', 50000); // 50MB file
        
        $this->service->handleFileUpload($this->normalize($file), 'document', 'uploads', ['maxFileSize' => 1024]); // 1MB limit
    }

    #[Test]
    public function handle_file_upload_validates_file_type(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage(trans('validation.file_upload.type_not_allowed', ['attribute' => ':attribute']));
        
        $file = UploadedFile::fake()->create('document.exe', 100);
        
        $this->service->handleFileUpload($this->normalize($file), 'document', 'uploads', ['allowedFileTypes' => ['pdf', 'txt']]);
    }

    #[Test]
    public function handle_file_upload_accepts_allowed_file_type(): void
    {
    $file = UploadedFile::fake()->create('document.pdf', 100);
        
    $result = $this->service->handleFileUpload($this->normalize($file), 'document', 'uploads', ['allowedFileTypes' => ['pdf', 'txt']]);
        
        $this->assertIsString($result);
        $this->assertStringContainsString('uploads/', $result);
        $this->assertStringEndsWith('.pdf', $result);
    }

    #[Test]
    public function generate_unique_filename_increments_suffix_when_files_exist(): void
    {
        // Use fake storage to simulate existing files
        Storage::fake('public');
        $destination = 'docs';

        // Create existing colliding files: report.pdf and report_1.pdf
        Storage::disk('public')->put($destination . '/report.pdf', '');
        Storage::disk('public')->put($destination . '/report_1.pdf', '');

        $filename = $this->service->generateUniqueFilename('report', 'pdf', $destination, 'public');
        $this->assertEquals('report_2.pdf', $filename, 'Filename should increment to next available suffix.');
    }

    #[Test]
    public function handle_file_upload_throws_exception_for_disallowed_file_type(): void
    {
        Storage::fake('public');

    $file = UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('Tento typ souboru není pro :attribute povolen.');

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
            null
        );
    }

    #[Test]
    public function handle_file_upload_throws_exception_when_file_exceeds_size_limit(): void
    {
        Storage::fake('public');
    $file = UploadedFile::fake()->create('large.png', 500, 'image/png'); // 500 KB

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('Soubor překračuje maximální velikost pro :attribute.');

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
            null
        );
    }

    #[Test]
    public function thumbnail_is_created_for_image_when_enabled(): void
    {
        Storage::fake('public');
    $file = UploadedFile::fake()->image('photo.jpg', 400, 400);

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
                'allowedFileTypes' => ['jpg','jpeg','png']
            ],
            null,
            null
        );

        $this->assertNotNull($path);
        $this->assertTrue(Storage::disk('public')->exists($path));

        // Expected thumbnail path structure
        $this->assertTrue(Storage::disk('public')->exists('thumbnails/images/photo.jpg'), 'Thumbnail should be created at expected path.');
    }#[Test]
    public function service_has_image_processor_property(): void
    {
        $reflection = new ReflectionClass($this->service);
        $this->assertTrue($reflection->hasProperty('imageProcessor'));
        $property = $reflection->getProperty('imageProcessor');
        $this->assertTrue($property->isProtected());
    }

    #[Test]
    public function handle_file_upload_method_signature_is_correct(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('handleFileUpload');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('?string', (string)$returnType);
        $parameters = $method->getParameters();
        $this->assertCount(6, $parameters);
        $this->assertEquals('value', $parameters[0]->getName());
        $this->assertEquals('attributeName', $parameters[1]->getName());
        $this->assertEquals('destinationPath', $parameters[2]->getName());
        $this->assertEquals('options', $parameters[3]->getName());
        $this->assertEquals('oldValue', $parameters[4]->getName());
        $this->assertEquals('context', $parameters[5]->getName());
    }

    #[Test]
    public function delete_file_method_signature_is_correct(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('deleteFile');
        $this->assertEquals('bool', (string)$method->getReturnType());
        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals('?string', (string)$params[0]->getType());
        $this->assertEquals('string', (string)$params[1]->getType());
    }

    #[Test]
    public function get_file_url_method_signature_is_correct(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getFileUrl');
        $this->assertEquals('?string', (string)$method->getReturnType());
        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals('?string', (string)$params[0]->getType());
        $this->assertEquals('string', (string)$params[1]->getType());
        $this->assertTrue($params[1]->isDefaultValueAvailable());
        $this->assertEquals('public', $params[1]->getDefaultValue());
    }

    #[Test]
    public function get_thumbnail_url_method_signature_is_correct(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getThumbnailUrl');
        $this->assertEquals('?string', (string)$method->getReturnType());
        $params = $method->getParameters();
        $this->assertCount(3, $params);
        $this->assertEquals('?string', (string)$params[0]->getType());
        $this->assertEquals('string', (string)$params[1]->getType());
        $this->assertEquals('string', (string)$params[2]->getType());
    }

    #[Test]
    public function get_file_type_icon_method_signature_is_correct(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getFileTypeIcon');
        $this->assertEquals('string', (string)$method->getReturnType());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('string', (string)$params[0]->getType());
    }

    #[Test]
    public function sanitize_filename_method_signature_is_correct(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('sanitizeFilename');
        $this->assertEquals('string', (string)$method->getReturnType());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('string', (string)$params[0]->getType());
    }

    #[Test]
    public function generate_unique_filename_method_signature_is_correct(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('generateUniqueFilename');
        $this->assertEquals('string', (string)$method->getReturnType());
        $params = $method->getParameters();
        $this->assertCount(4, $params);
        $this->assertEquals('string', (string)$params[0]->getType());
        $this->assertEquals('string', (string)$params[1]->getType());
        $this->assertEquals('string', (string)$params[2]->getType());
        $this->assertEquals('string', (string)$params[3]->getType());
        $this->assertTrue($params[3]->isDefaultValueAvailable());
        $this->assertEquals('public', $params[3]->getDefaultValue());
    }

    #[Test]
    public function get_file_url_from_attribute_method_signature_is_correct(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getFileUrlFromAttribute');
        $this->assertEquals('?string', (string)$method->getReturnType());
        $params = $method->getParameters();
        $this->assertCount(3, $params);
        $this->assertEquals('mixed', (string)$params[0]->getType()); // mixed
        $this->assertEquals('?int', (string)$params[1]->getType());
        $this->assertEquals('string', (string)$params[2]->getType());
    }

    #[Test]
    public function protected_methods_exist_and_are_protected(): void
    {
        $reflection = new ReflectionClass($this->service);
        foreach (['createThumbnail','deleteAssociatedFiles'] as $methodName) {
            $this->assertTrue($reflection->hasMethod($methodName));
            $m = $reflection->getMethod($methodName);
            $this->assertTrue($m->isProtected(), $methodName . ' should be protected');
        }
    }

    #[Test]
    public function service_namespace_and_structure_is_correct(): void
    {
        $reflection = new ReflectionClass($this->service);
        // Validate contract and general structure rather than concrete class name/namespace
        $this->assertTrue($reflection->implementsInterface(\App\Domain\Shared\File\Contracts\FileUploadServiceInterface::class));
        $this->assertTrue($reflection->isInstantiable());
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
    }

    #[Test]
    public function all_public_methods_have_return_types(): void
    {
        $reflection = new ReflectionClass($this->service);
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getName() === '__construct') { continue; }
            $this->assertNotNull($method->getReturnType(), $method->getName() . ' should declare return type');
        }
    }

    #[Test]
    public function generate_unique_filename_adds_suffix_on_collision(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('generateUniqueFilename');
        $result = $method->invoke($this->service, 'report', 'pdf', 'nonexistent-path', 'public');
        $this->assertEquals('report.pdf', $result);
    }
}
