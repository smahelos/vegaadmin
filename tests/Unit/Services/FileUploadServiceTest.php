<?php

namespace Tests\Unit\Services;

use App\Services\FileUploadService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class FileUploadServiceTest extends TestCase
{
    private FileUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FileUploadService();
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(FileUploadService::class, $this->service);
    }

    #[Test]
    public function service_has_image_manager_property(): void
    {
        $reflection = new ReflectionClass($this->service);
        $this->assertTrue($reflection->hasProperty('imageManager'));
        
        $property = $reflection->getProperty('imageManager');
        $this->assertTrue($property->isProtected());
    }

    #[Test]
    public function handle_file_upload_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'handleFileUpload'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('handleFileUpload');
        
        // Note: This method doesn't have return type annotation in the original code
        // but based on documentation it should return string|null
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(5, $parameters);
        $this->assertEquals('value', $parameters[0]->getName());
        $this->assertEquals('attributeName', $parameters[1]->getName());
        $this->assertEquals('destinationPath', $parameters[2]->getName());
        $this->assertEquals('options', $parameters[3]->getName());
        $this->assertEquals('oldValue', $parameters[4]->getName());
    }

    #[Test]
    public function delete_file_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'deleteFile'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('deleteFile');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('path', $parameters[0]->getName());
        $this->assertEquals('disk', $parameters[1]->getName());
        $this->assertTrue($parameters[0]->getType()->allowsNull());
        $this->assertEquals('string', $parameters[1]->getType()->getName());
    }

    #[Test]
    public function get_file_url_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getFileUrl'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getFileUrl');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
        $this->assertEquals('string', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('path', $parameters[0]->getName());
        $this->assertEquals('disk', $parameters[1]->getName());
        $this->assertTrue($parameters[0]->getType()->allowsNull());
        $this->assertEquals('string', $parameters[1]->getType()->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertEquals('public', $parameters[1]->getDefaultValue());
    }

    #[Test]
    public function get_thumbnail_url_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getThumbnailUrl'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getThumbnailUrl');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
        $this->assertEquals('string', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(3, $parameters);
        $this->assertEquals('originalPath', $parameters[0]->getName());
        $this->assertEquals('options', $parameters[1]->getName());
        $this->assertEquals('disk', $parameters[2]->getName());
    }

    #[Test]
    public function get_file_type_icon_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getFileTypeIcon'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getFileTypeIcon');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('filePath', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
    }

    #[Test]
    public function sanitize_filename_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'sanitizeFilename'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('sanitizeFilename');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('filename', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
    }

    #[Test]
    public function generate_unique_filename_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'generateUniqueFilename'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('generateUniqueFilename');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(4, $parameters);
        $this->assertEquals('originalName', $parameters[0]->getName());
        $this->assertEquals('extension', $parameters[1]->getName());
        $this->assertEquals('destinationPath', $parameters[2]->getName());
        $this->assertEquals('disk', $parameters[3]->getName());
    }

    #[Test]
    public function get_file_url_from_attribute_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getFileUrlFromAttribute'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getFileUrlFromAttribute');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
        $this->assertEquals('string', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(3, $parameters);
        $this->assertEquals('value', $parameters[0]->getName());
        $this->assertEquals('index', $parameters[1]->getName());
        $this->assertEquals('disk', $parameters[2]->getName());
    }

    #[Test]
    public function protected_methods_exist(): void
    {
        $reflection = new ReflectionClass($this->service);
        
        // Check protected methods
        $this->assertTrue($reflection->hasMethod('createThumbnail'));
        $this->assertTrue($reflection->hasMethod('deleteAssociatedFiles'));
        $this->assertTrue($reflection->hasMethod('isImage'));
        
        $createThumbnailMethod = $reflection->getMethod('createThumbnail');
        $this->assertTrue($createThumbnailMethod->isProtected());
        
        $deleteAssociatedFilesMethod = $reflection->getMethod('deleteAssociatedFiles');
        $this->assertTrue($deleteAssociatedFilesMethod->isProtected());
        
        $isImageMethod = $reflection->getMethod('isImage');
        $this->assertTrue($isImageMethod->isProtected());
    }

    #[Test]
    public function service_has_correct_structure(): void
    {
        $reflection = new ReflectionClass($this->service);
        
        // Check that class is properly structured
        $this->assertEquals('App\Services', $reflection->getNamespaceName());
        $this->assertEquals('FileUploadService', $reflection->getShortName());
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
    }

    #[Test]
    public function all_public_methods_have_return_types(): void
    {
        $reflection = new ReflectionClass($this->service);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if ($method->getName() !== '__construct' && 
                $method->getName() !== 'handleFileUpload' && // This method doesn't have return type in original code
                $method->getDeclaringClass()->getName() === FileUploadService::class) {
                $this->assertNotNull(
                    $method->getReturnType(),
                    "Method {$method->getName()} should have a return type"
                );
            }
        }
    }

    #[Test]
    public function method_parameter_types_are_correct(): void
    {
        $reflection = new ReflectionClass($this->service);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if ($method->getName() !== '__construct' && 
                $method->getDeclaringClass()->getName() === FileUploadService::class) {
                foreach ($method->getParameters() as $parameter) {
                    // Most parameters should have types (some may be mixed)
                    if ($parameter->getName() !== 'value') { // 'value' param can be mixed
                        $this->assertNotNull(
                            $parameter->getType(),
                            "Parameter {$parameter->getName()} in method {$method->getName()} should have a type"
                        );
                    }
                }
            }
        }
    }
}
