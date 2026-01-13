<?php

namespace Tests\Unit\Http\Requests\Admin;

use App\Http\Requests\Admin\SubscriptionPlanRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SubscriptionPlanRequestTest extends TestCase
{
    private SubscriptionPlanRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new SubscriptionPlanRequest();
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
    public function rules_returns_array_with_required_fields(): void
    {
        $rules = $this->request->rules();
        
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('price', $rules);
        $this->assertArrayHasKey('currency', $rules);
        $this->assertArrayHasKey('billing_period', $rules);
        $this->assertArrayHasKey('billing_interval', $rules);
        $this->assertArrayHasKey('trial_days', $rules);
        $this->assertArrayHasKey('features', $rules);
        $this->assertArrayHasKey('features.*', $rules);
    }

    #[Test]
    public function rules_contains_correct_validation_patterns(): void
    {
        $rules = $this->request->rules();
        
        $this->assertStringContainsString('required', $rules['name']);
        $this->assertStringContainsString('required', $rules['price']);
        $this->assertStringContainsString('required', $rules['currency']);
        $this->assertStringContainsString('numeric', $rules['price']);
        $this->assertStringContainsString('min:0', $rules['price']);
        $this->assertStringContainsString('in:CZK,EUR,USD', $rules['currency']);
        $this->assertStringContainsString('in:monthly,yearly', $rules['billing_period']);
        $this->assertStringContainsString('array', $rules['features']);
        $this->assertStringContainsString('exists:subscription_plan_features,id', $rules['features.*']);
    }
}
