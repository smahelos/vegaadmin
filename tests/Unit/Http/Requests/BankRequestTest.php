<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\BankRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BankRequest - CRITICAL RULE: Only pure business logic, no Laravel dependencies
 * 
 * According to Unit Test Isolation rule, this class tests only:
 * - Class structure and inheritance
 * - Method signatures and return types
 * - Class introspection without executing Laravel-dependent methods
 * 
 * Authorization and validation business logic has been moved to Feature tests.
 */
class BankRequestTest extends TestCase
{
    private BankRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new BankRequest();
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
    public function rules_method_exists_and_is_callable(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('rules');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function attributes_method_exists_and_is_callable(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('attributes');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function messages_method_exists_and_is_callable(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('messages');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function request_has_expected_class_structure(): void
    {
        $reflection = new \ReflectionClass($this->request);
        
        $this->assertEquals('App\Http\Requests', $reflection->getNamespaceName());
        $this->assertTrue($reflection->hasMethod('authorize'));
        $this->assertTrue($reflection->hasMethod('rules'));
        $this->assertTrue($reflection->hasMethod('attributes'));
        $this->assertTrue($reflection->hasMethod('messages'));
    }
}
