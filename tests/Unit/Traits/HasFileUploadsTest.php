<?php

namespace Tests\Unit\Traits;

use App\Traits\HasFileUploads;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HasFileUploadsTest extends TestCase
{
    #[Test]
    public function trait_exists_and_can_be_used(): void
    {
        $this->assertTrue(trait_exists('App\Traits\HasFileUploads'));
    }

    #[Test]
    public function trait_has_required_methods(): void
    {
        $reflection = new \ReflectionClass('App\Traits\HasFileUploads');
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn($method) => $method->getName(), $methods);

        $expectedMethods = [
            'handleFileUpload',
            'getFileUrl', 
            'getThumbnailUrl',
            'isFileImage',
            'getAttributeFileUrl'
        ];

        foreach ($expectedMethods as $expectedMethod) {
            $this->assertContains($expectedMethod, $methodNames);
        }
    }

    #[Test]
    public function handle_file_upload_method_signature(): void
    {
        $reflection = new \ReflectionClass('App\Traits\HasFileUploads');
        $method = $reflection->getMethod('handleFileUpload');
        $parameters = $method->getParameters();

        $this->assertGreaterThanOrEqual(2, count($parameters)); // At least attribute and value parameters
        $this->assertEquals('attribute', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
    }

    #[Test]
    public function get_file_url_method_signature(): void
    {
        $reflection = new \ReflectionClass('App\Traits\HasFileUploads');
        $method = $reflection->getMethod('getFileUrl');
        $parameters = $method->getParameters();

        $this->assertGreaterThanOrEqual(1, count($parameters));
        $this->assertEquals('attribute', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
    }

    #[Test]
    public function get_thumbnail_url_method_signature(): void
    {
        $reflection = new \ReflectionClass('App\Traits\HasFileUploads');
        $method = $reflection->getMethod('getThumbnailUrl');
        $parameters = $method->getParameters();

        $this->assertGreaterThanOrEqual(1, count($parameters));
        $this->assertEquals('attribute', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
    }

    #[Test]
    public function is_file_image_method_signature(): void
    {
        $reflection = new \ReflectionClass('App\Traits\HasFileUploads');
        $method = $reflection->getMethod('isFileImage');
        $parameters = $method->getParameters();
        $returnType = $method->getReturnType();

        $this->assertCount(1, $parameters);
        $this->assertEquals('attribute', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    #[Test]
    public function get_attribute_file_url_method_signature(): void
    {
        $reflection = new \ReflectionClass('App\Traits\HasFileUploads');
        $method = $reflection->getMethod('getAttributeFileUrl');
        $parameters = $method->getParameters();

        $this->assertGreaterThanOrEqual(1, count($parameters));
        $this->assertEquals('attribute', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
    }

    #[Test]
    public function trait_methods_have_proper_visibility(): void
    {
        $reflection = new \ReflectionClass('App\Traits\HasFileUploads');
        
        // Check method visibility
        $handleFileUploadMethod = $reflection->getMethod('handleFileUpload');
        $this->assertTrue($handleFileUploadMethod->isProtected());
        
        $getFileUrlMethod = $reflection->getMethod('getFileUrl');
        $this->assertTrue($getFileUrlMethod->isProtected());
        
        $getThumbnailUrlMethod = $reflection->getMethod('getThumbnailUrl');
        $this->assertTrue($getThumbnailUrlMethod->isProtected());
        
        $isFileImageMethod = $reflection->getMethod('isFileImage');
        $this->assertTrue($isFileImageMethod->isProtected());
        
        $getAttributeFileUrlMethod = $reflection->getMethod('getAttributeFileUrl');
        $this->assertTrue($getAttributeFileUrlMethod->isPublic());
    }

    #[Test]
    public function trait_has_correct_namespace(): void
    {
        $reflection = new \ReflectionClass('App\Traits\HasFileUploads');
        $this->assertEquals('App\Traits', $reflection->getNamespaceName());
    }

    #[Test]
    public function trait_methods_count(): void
    {
        $reflection = new \ReflectionClass('App\Traits\HasFileUploads');
        $methods = $reflection->getMethods();
        
        // Should have exactly 5 methods
        $this->assertCount(5, $methods);
    }

    #[Test]
    public function trait_has_no_properties(): void
    {
        $reflection = new \ReflectionClass('App\Traits\HasFileUploads');
        $properties = $reflection->getProperties();
        
        // Trait should not define any properties
        $this->assertEmpty($properties);
    }

    #[Test]
    public function trait_is_actually_a_trait(): void
    {
        $reflection = new \ReflectionClass('App\Traits\HasFileUploads');
        $this->assertTrue($reflection->isTrait());
    }
}
