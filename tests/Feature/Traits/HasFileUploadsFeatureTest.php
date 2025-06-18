<?php

namespace Tests\Feature\Traits;

use App\Services\FileUploadService;
use App\Traits\HasFileUploads;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

// Test model that uses the trait
class TestModelWithFileUploads extends Model
{
    use HasFileUploads;
    
    protected $fillable = ['*'];
    public $table = 'test_models';
    public $timestamps = false;
    
    // Override for testing
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setRawAttributes($attributes, true);
    }
    
    public function testIsFileImage(string $attribute): bool
    {
        return $this->isFileImage($attribute);
    }
    
    public function callHandleFileUpload(string $attribute, $value, string $path, array $options = []): void
    {
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod('handleFileUpload');
        $method->setAccessible(true);
        $method->invokeArgs($this, [$attribute, $value, $path, $options]);
    }
    
    public function callGetFileUrl(string $attribute, string $disk = 'public'): ?string
    {
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod('getFileUrl');
        $method->setAccessible(true);
        return $method->invokeArgs($this, [$attribute, $disk]);
    }
    
    public function callGetThumbnailUrl(string $attribute, string $thumbnailFolder = 'thumbnails', string $disk = 'public'): ?string
    {
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod('getThumbnailUrl');
        $method->setAccessible(true);
        return $method->invokeArgs($this, [$attribute, $thumbnailFolder, $disk]);
    }
}

class HasFileUploadsFeatureTest extends TestCase
{
    use RefreshDatabase, HasFileUploads;

    protected array $attributes = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    #[Test]
    public function handle_file_upload_integrates_with_file_upload_service(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('test.jpg');
        $this->attributes = ['old_file' => 'old_file.jpg'];

        // Act
        $this->handleFileUpload('image', $file, 'uploads/images');

        // Assert
        $this->assertNotNull($this->attributes['image']);
        $this->assertNotEquals('old_file.jpg', $this->attributes['image']);
    }

    #[Test]
    public function handle_file_upload_preserves_old_value_when_no_new_file(): void
    {
        // Arrange
        $this->attributes = ['image' => 'existing_file.jpg'];

        // Act
        $this->handleFileUpload('image', null, 'uploads/images');

        // Assert
        $this->assertEquals('existing_file.jpg', $this->attributes['image']);
    }

    #[Test]
    public function handle_file_upload_passes_options_to_service(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('test.jpg');
        $options = ['maxFileSize' => 2048, 'allowedMimeTypes' => ['image/jpeg']];

        // Act - Should not throw exception with valid options
        $this->handleFileUpload('image', $file, 'uploads/images', $options);

        // Assert
        $this->assertNotNull($this->attributes['image']);
    }

    #[Test]
    public function get_file_url_integrates_with_file_upload_service(): void
    {
        // Arrange
        $this->attributes = ['file' => 'test-file.pdf'];
        Storage::disk('public')->put('test-file.pdf', 'fake content');

        // Act
        $url = $this->getFileUrl('file');

        // Assert
        $this->assertNotNull($url);
        $this->assertStringContainsString('test-file.pdf', $url);
    }

    #[Test]
    public function get_file_url_returns_url_for_missing_file(): void
    {
        // Arrange
        $this->attributes = ['file' => 'nonexistent.pdf'];

        // Act
        $url = $this->getFileUrl('file');

        // Assert - FileUploadService may return URL even if file doesn't exist
        $this->assertNotNull($url);
        $this->assertStringContainsString('nonexistent.pdf', $url);
    }

    #[Test]
    public function get_file_url_uses_custom_disk(): void
    {
        // Arrange
        Storage::fake('custom');
        $this->attributes = ['file' => 'test-file.pdf'];
        Storage::disk('custom')->put('test-file.pdf', 'fake content');

        // Act
        $url = $this->getFileUrl('file', 'custom');

        // Assert
        $this->assertNotNull($url);
    }

    #[Test]
    public function get_thumbnail_url_integrates_with_file_upload_service(): void
    {
        // Arrange
        $this->attributes = ['image' => 'test-image.jpg'];
        Storage::disk('public')->put('test-image.jpg', 'fake image content');

        // Act
        $url = $this->getThumbnailUrl('image');

        // Assert
        // Should return original URL since no thumbnail exists
        $this->assertNotNull($url);
    }

    #[Test]
    public function get_thumbnail_url_uses_custom_thumbnail_folder(): void
    {
        // Arrange
        $this->attributes = ['image' => 'test-image.jpg'];
        Storage::disk('public')->put('test-image.jpg', 'fake image content');

        // Act
        $url = $this->getThumbnailUrl('image', 'custom_thumbnails');

        // Assert
        $this->assertNotNull($url);
    }

    #[Test]
    public function get_thumbnail_url_uses_custom_disk(): void
    {
        // Arrange
        Storage::fake('custom');
        $this->attributes = ['image' => 'test-image.jpg'];
        Storage::disk('custom')->put('test-image.jpg', 'fake image content');

        // Act
        $url = $this->getThumbnailUrl('image', 'thumbnails', 'custom');

        // Assert
        $this->assertNotNull($url);
    }

    #[Test]
    public function is_file_image_recognizes_image_files(): void
    {
        // Test various image extensions using the test model
        $imageFiles = [
            'test.jpg',
            'test.jpeg', 
            'test.png',
            'test.gif',
            'test.webp',
            'test.svg',
            'test.bmp',
            'test.JPG', // uppercase
            'test.PNG'  // uppercase
        ];

        foreach ($imageFiles as $filename) {
            $testModel = new TestModelWithFileUploads(['image' => $filename]);
            $result = $testModel->testIsFileImage('image');
            $this->assertTrue($result, "File {$filename} should be recognized as image");
        }
    }

    #[Test]
    public function is_file_image_rejects_non_image_files(): void
    {
        // Test non-image extensions
        $nonImageFiles = [
            'test.pdf',
            'test.doc',
            'test.txt',
            'test.mp4',
            'test.mp3',
            'test.zip',
            'test.exe'
        ];

        foreach ($nonImageFiles as $filename) {
            $model = new TestModelWithFileUploads(['file' => $filename]);
            $result = $model->testIsFileImage('file');
            $this->assertFalse($result, "File {$filename} should not be recognized as image");
        }
    }

    #[Test]
    public function is_file_image_handles_empty_values(): void
    {
        // Test empty string
        $model1 = new TestModelWithFileUploads(['image' => '']);
        $result1 = $model1->testIsFileImage('image');
        $this->assertFalse($result1);

        // Test null
        $model2 = new TestModelWithFileUploads(['image' => null]);
        $result2 = $model2->testIsFileImage('image');
        $this->assertFalse($result2);

        // Test undefined attribute
        $model3 = new TestModelWithFileUploads([]);
        $result3 = $model3->testIsFileImage('image');
        $this->assertFalse($result3);
    }

    #[Test]
    public function get_attribute_file_url_integrates_with_file_upload_service(): void
    {
        // Arrange
        $this->attributes = ['file' => 'test-file.pdf'];
        Storage::disk('public')->put('test-file.pdf', 'fake content');

        // Act
        $url = $this->getAttributeFileUrl('file');

        // Assert
        $this->assertNotNull($url);
        $this->assertStringContainsString('test-file.pdf', $url);
    }

    #[Test]
    public function get_attribute_file_url_handles_array_attributes(): void
    {
        // Arrange
        $this->attributes = ['files' => ['file1.pdf', 'file2.pdf', 'file3.pdf']];
        Storage::disk('public')->put('file2.pdf', 'fake content');

        // Act
        $url = $this->getAttributeFileUrl('files', 1);

        // Assert
        $this->assertNotNull($url);
        $this->assertStringContainsString('file2.pdf', $url);
    }

    #[Test]
    public function get_attribute_file_url_returns_null_for_invalid_index(): void
    {
        // Arrange
        $this->attributes = ['files' => ['file1.pdf', 'file2.pdf']];

        // Act
        $url = $this->getAttributeFileUrl('files', 5);

        // Assert
        $this->assertNull($url);
    }

    #[Test]
    public function get_attribute_file_url_uses_custom_disk(): void
    {
        // Arrange
        Storage::fake('custom');
        $this->attributes = ['file' => 'test-file.pdf'];
        Storage::disk('custom')->put('test-file.pdf', 'fake content');

        // Act
        $url = $this->getAttributeFileUrl('file', null, 'custom');

        // Assert
        $this->assertNotNull($url);
    }

    #[Test]
    public function trait_handles_file_upload_workflow_correctly(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('upload.jpg', 100, 100);
        $model = new TestModelWithFileUploads();

        // Act - Upload file
        $model->callHandleFileUpload('profile_image', $file, 'uploads/profiles');
        
        // Verify file was uploaded
        $this->assertNotNull($model->profile_image);
        
        // Get URL
        $url = $model->callGetFileUrl('profile_image');
        $this->assertNotNull($url);
        
        // Check if it's an image
        $isImage = $model->testIsFileImage('profile_image');
        $this->assertTrue($isImage);
        
        // Get thumbnail URL
        $thumbnailUrl = $model->callGetThumbnailUrl('profile_image');
        $this->assertNotNull($thumbnailUrl);
    }

    #[Test]
    public function trait_methods_work_together_seamlessly(): void
    {
        // Arrange
        $files = [
            UploadedFile::fake()->image('image1.jpg'),
            UploadedFile::fake()->create('document.pdf', 100),
            UploadedFile::fake()->image('image2.png')
        ];

        $model = new TestModelWithFileUploads();
        $uploadedFiles = [];
        foreach ($files as $index => $file) {
            $model->callHandleFileUpload("file_{$index}", $file, 'uploads/mixed');
            $uploadedFiles[] = $model->{"file_{$index}"};
        }

        // Act & Assert
        foreach ($uploadedFiles as $index => $filename) {
            $model->current_file = $filename;
            
            $url = $model->callGetFileUrl('current_file');
            $this->assertNotNull($url);
            
            $attributeUrl = $model->getAttributeFileUrl('current_file');
            $this->assertEquals($url, $attributeUrl);
            
            $isImage = $model->testIsFileImage('current_file');
            if ($index === 1) { // PDF file
                $this->assertFalse($isImage);
            } else { // Image files
                $this->assertTrue($isImage);
            }
        }
    }

    // Magic method to simulate accessing attributes like in Eloquent models
    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }
}
