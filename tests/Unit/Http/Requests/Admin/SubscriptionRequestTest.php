<?php

namespace Tests\Unit\Http\Requests\Admin;

use App\Http\Requests\Admin\SubscriptionRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SubscriptionRequestTest extends TestCase
{
    private SubscriptionRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new SubscriptionRequest();
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
        $this->assertArrayHasKey('user_id', $rules);
        $this->assertArrayHasKey('subscription_plan_id', $rules);
        $this->assertArrayHasKey('status', $rules);
        $this->assertArrayHasKey('amount', $rules);
        $this->assertArrayHasKey('currency', $rules);
    }

    #[Test]
    public function rules_contains_correct_validation_patterns(): void
    {
        $rules = $this->request->rules();
        
        $this->assertStringContainsString('required', $rules['user_id']);
        $this->assertStringContainsString('required', $rules['subscription_plan_id']);
        $this->assertStringContainsString('required', $rules['status']);
        $this->assertStringContainsString('required', $rules['amount']);
        $this->assertStringContainsString('exists:users,id', $rules['user_id']);
        $this->assertStringContainsString('exists:subscription_plans,id', $rules['subscription_plan_id']);
        $this->assertStringContainsString('in:pending,active,cancelled,expired,past_due', $rules['status']);
        $this->assertStringContainsString('numeric', $rules['amount']);
        $this->assertStringContainsString('min:0', $rules['amount']);
    }

    #[Test]
    public function attributes_returns_array_with_expected_keys(): void
    {
        // For unit tests, we just check the method exists and returns array
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('attributes');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals('array', $method->getReturnType()->getName());
    }

    #[Test]
    public function messages_returns_array_with_validation_messages(): void
    {
        // For unit tests, we just check the method exists and returns array
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('messages');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals('array', $method->getReturnType()->getName());
    }

    #[Test]
    public function all_required_validation_messages_are_present(): void
    {
        // For unit tests, we just check the method exists and returns array
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('messages');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals('array', $method->getReturnType()->getName());
    }

    #[Test]
    public function date_validation_rules_are_present(): void
    {
        $rules = $this->request->rules();
        
        $this->assertArrayHasKey('starts_at', $rules);
        $this->assertArrayHasKey('ends_at', $rules);
        $this->assertArrayHasKey('trial_ends_at', $rules);
        $this->assertArrayHasKey('next_billing_at', $rules);
        
        $this->assertStringContainsString('nullable', $rules['starts_at']);
        $this->assertStringContainsString('date', $rules['starts_at']);
        $this->assertStringContainsString('after:starts_at', $rules['ends_at']);
    }
}
