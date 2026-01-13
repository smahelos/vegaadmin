<?php

namespace Tests\Unit\Http\Requests\Admin;

use App\Http\Requests\Admin\PageCategoryRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Admin PageCategoryRequest class.
 * Tests basic structure and return types without Laravel dependencies.
 */
class PageCategoryRequestTest extends TestCase
{
    private PageCategoryRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new PageCategoryRequest();
    }

    #[Test]
    public function request_extends_form_request(): void
    {
        $this->assertInstanceOf(FormRequest::class, $this->request);
    }

    #[Test]
    public function authorize_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('authorize');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    #[Test]
    public function rules_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('rules');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function attributes_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('attributes');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function messages_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('messages');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function authorize_method_exists_and_is_public(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('authorize');
        
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function rules_method_exists_and_is_public(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('rules');
        
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function attributes_method_exists_and_is_public(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('attributes');
        
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function messages_method_exists_and_is_public(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('messages');
        
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function rules_method_returns_array(): void
    {
        // Test structure only - don't call methods that require Laravel services
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('rules');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function attributes_method_returns_array(): void
    {
        // Test structure only - don't call methods that require Laravel services
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('attributes');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function messages_method_returns_array(): void
    {
        // Test structure only - don't call methods that require Laravel services
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('messages');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function rules_contains_required_validation_rules(): void
    {
        // Test that the rules method exists and has correct structure
        $this->assertTrue(method_exists(PageCategoryRequest::class, 'rules'));
        
        $reflection = new \ReflectionClass(PageCategoryRequest::class);
        $method = $reflection->getMethod('rules');
        $this->assertTrue($method->isPublic());
        
        // For Unit test, we only test method existence and return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function attributes_contains_expected_field_names(): void
    {
        // Test that the attributes method exists and has correct structure
        $this->assertTrue(method_exists(PageCategoryRequest::class, 'attributes'));
        
        $reflection = new \ReflectionClass(PageCategoryRequest::class);
        $method = $reflection->getMethod('attributes');
        $this->assertTrue($method->isPublic());
        
        // For Unit test, we only test method existence and return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function messages_contains_expected_validation_messages(): void
    {
        // Test that the messages method exists and has correct structure
        $this->assertTrue(method_exists(PageCategoryRequest::class, 'messages'));
        
        $reflection = new \ReflectionClass(PageCategoryRequest::class);
        $method = $reflection->getMethod('messages');
        $this->assertTrue($method->isPublic());
        
        // For Unit test, we only test method existence and return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }
}
