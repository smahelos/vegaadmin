<?php

namespace Tests\Unit\Http\Requests\Admin;

use App\Http\Requests\Admin\PaymentMethodRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Admin PaymentMethodRequest
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
    public function protected_methods_exist(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $this->assertTrue($reflection->hasMethod('getEntityType'));
        $this->assertTrue($reflection->hasMethod('getRequiredPermission'));
        $this->assertTrue($reflection->getMethod('getEntityType')->isProtected());
        $this->assertTrue($reflection->getMethod('getRequiredPermission')->isProtected());
    }

    #[Test]
    public function get_entity_type_returns_payment_method(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('getEntityType');
        $method->setAccessible(true);
        $this->assertEquals('payment_method', $method->invoke($this->request));
    }

    #[Test]
    public function get_required_permission_correct(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('getRequiredPermission');
        $method->setAccessible(true);
        $this->assertEquals('can_create_edit_payment_method', $method->invoke($this->request));
    }

    #[Test]
    public function rules_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('rules');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string)$returnType);
    }

    #[Test]
    public function attributes_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('attributes');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string)$returnType);
    }

    #[Test]
    public function messages_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('messages');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', (string)$returnType);
    }

    #[Test]
    public function authorize_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('authorize');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', (string)$returnType);
    }
}
