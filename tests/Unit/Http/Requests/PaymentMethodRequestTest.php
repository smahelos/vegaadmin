<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\PaymentMethodRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PaymentMethodRequest (frontend)
 * Focuses on structure, method signatures, return types (no framework side effects)
 */
class PaymentMethodRequestTest extends TestCase
{
    private PaymentMethodRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new PaymentMethodRequest();
    }

    #[Test]
    public function request_extends_form_request(): void
    {
        $this->assertInstanceOf(FormRequest::class, $this->request);
    }

    #[Test]
    public function authorize_method_has_bool_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('authorize');
    $returnType = $method->getReturnType(); $this->assertNotNull($returnType); $this->assertEquals('bool', (string)$returnType);
    }

    #[Test]
    public function rules_method_has_array_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('rules');
    $returnType = $method->getReturnType(); $this->assertNotNull($returnType); $this->assertEquals('array', (string)$returnType);
    }

    #[Test]
    public function attributes_method_has_array_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('attributes');
    $returnType = $method->getReturnType(); $this->assertNotNull($returnType); $this->assertEquals('array', (string)$returnType);
    }

    #[Test]
    public function messages_method_has_array_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('messages');
    $returnType = $method->getReturnType(); $this->assertNotNull($returnType); $this->assertEquals('array', (string)$returnType);
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
    public function class_has_expected_structure(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $this->assertTrue($reflection->hasMethod('authorize'));
        $this->assertTrue($reflection->hasMethod('rules'));
        $this->assertTrue($reflection->hasMethod('attributes'));
        $this->assertTrue($reflection->hasMethod('messages'));
        $this->assertTrue($reflection->hasMethod('prepareForValidation'));
    }
}
