<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\ExpenseRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ExpenseRequest (frontend) focusing on structure & signatures only.
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
    public function authorize_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('authorize');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        if ($returnType instanceof \ReflectionNamedType) {
            $this->assertEquals('bool', $returnType->getName());
        } else {
            $this->fail('authorize return type is not a named type');
        }
    }

    #[Test]
    public function rules_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('rules');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        if ($returnType instanceof \ReflectionNamedType) {
            $this->assertEquals('array', $returnType->getName());
        } else {
            $this->fail('rules return type is not a named type');
        }
    }

    #[Test]
    public function attributes_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('attributes');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        if ($returnType instanceof \ReflectionNamedType) {
            $this->assertEquals('array', $returnType->getName());
        } else {
            $this->fail('attributes return type is not a named type');
        }
    }

    #[Test]
    public function messages_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('messages');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        if ($returnType instanceof \ReflectionNamedType) {
            $this->assertEquals('array', $returnType->getName());
        } else {
            $this->fail('messages return type is not a named type');
        }
    }

    #[Test]
    public function prepare_for_validation_exists_and_is_protected(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $this->assertTrue($reflection->hasMethod('prepareForValidation'));
        $method = $reflection->getMethod('prepareForValidation');
        $this->assertTrue($method->isProtected());
    }

    #[Test]
    public function request_has_expected_class_structure(): void
    {
        $reflection = new \ReflectionClass($this->request);
        foreach (['authorize','rules','attributes','messages'] as $method) {
            $this->assertTrue($reflection->hasMethod($method));
        }
        $this->assertEquals('App\\Http\\Requests', $reflection->getNamespaceName());
    }
}
