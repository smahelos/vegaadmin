<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\PasswordUpdateRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PasswordUpdateRequest - CRITICAL RULE: Only pure business logic, no Laravel dependencies
 * 
 * According to Unit Test Isolation rule, this class tests only:
 * - Class structure and inheritance
 * - Method signatures and return types
 * - Class introspection without executing Laravel-dependent methods
 * 
 * Authorization and validation business logic has been moved to Feature tests.
 */
class PasswordUpdateRequestTest extends TestCase
{
    private PasswordUpdateRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new PasswordUpdateRequest();
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
    public function has_required_methods(): void
    {
        $reflection = new \ReflectionClass($this->request);
        
        $this->assertTrue($reflection->hasMethod('authorize'));
        $this->assertTrue($reflection->hasMethod('rules'));
        $this->assertTrue($reflection->hasMethod('attributes'));
        $this->assertTrue($reflection->hasMethod('messages'));
    }

    #[Test]
    public function all_methods_are_public(): void
    {
        $reflection = new \ReflectionClass($this->request);
        
        $this->assertTrue($reflection->getMethod('authorize')->isPublic());
        $this->assertTrue($reflection->getMethod('rules')->isPublic());
        $this->assertTrue($reflection->getMethod('attributes')->isPublic());
        $this->assertTrue($reflection->getMethod('messages')->isPublic());
    }
}
