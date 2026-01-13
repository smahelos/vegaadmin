<?php

namespace Tests\Unit\Http\Requests\Admin;

use App\Http\Requests\Admin\ExpenseRequest;
use App\Http\Requests\Admin\BaseEntityRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Admin\\ExpenseRequest focusing on structure only.
 */
class ExpenseRequestTest extends TestCase
{
    private ExpenseRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new ExpenseRequest();
    }

    #[Test]
    public function request_extends_form_request(): void
    {
        $this->assertInstanceOf(FormRequest::class, $this->request);
    }

    #[Test]
    public function request_extends_base_entity_request(): void
    {
        $this->assertInstanceOf(BaseEntityRequest::class, $this->request);
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
        $requiredMethods = ['rules', 'attributes', 'messages', 'prepareForValidation'];
        foreach ($requiredMethods as $method) {
            $this->assertTrue(method_exists($this->request, $method), "Method {$method} does not exist in Admin\\ExpenseRequest class");
        }
    }

    #[Test]
    public function all_public_methods_are_public(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $methods = ['rules', 'attributes', 'messages', 'prepareForValidation'];
        foreach ($methods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "Method {$methodName} should be public");
        }
    }
}
