<?php

namespace Tests\Unit\Http\Requests\Admin;

use App\Http\Requests\Admin\SubscriptionPlanFeatureRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionPlanFeatureRequestTest extends TestCase
{
    private SubscriptionPlanFeatureRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new SubscriptionPlanFeatureRequest();
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
        $this->assertArrayHasKey('slug', $rules);
        $this->assertArrayHasKey('is_active', $rules);
        $this->assertArrayHasKey('sort_order', $rules);
    }

    #[Test]
    public function rules_contains_correct_validation_patterns(): void
    {
        $rules = $this->request->rules();
        
        $this->assertStringContainsString('required', $rules['name']);
        $this->assertStringContainsString('string', $rules['name']);
        $this->assertStringContainsString('max:255', $rules['name']);
        
        $this->assertStringContainsString('required', $rules['slug']);
        $this->assertStringContainsString('string', $rules['slug']);
        $this->assertStringContainsString('unique:subscription_plan_features,slug', $rules['slug']);
        
        $this->assertStringContainsString('boolean', $rules['is_active']);
        
        $this->assertStringContainsString('integer', $rules['sort_order']);
        $this->assertStringContainsString('min:0', $rules['sort_order']);
    }

    #[Test]
    public function attributes_returns_array_with_expected_keys(): void
    {
        $attributes = $this->request->attributes();
        
        $this->assertIsArray($attributes);
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('description', $attributes);
        $this->assertArrayHasKey('slug', $attributes);
        $this->assertArrayHasKey('is_active', $attributes);
        $this->assertArrayHasKey('sort_order', $attributes);
    }

    #[Test]
    public function messages_returns_array_with_validation_messages(): void
    {
        $messages = $this->request->messages();
        
        $this->assertIsArray($messages);
        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('slug.required', $messages);
        $this->assertArrayHasKey('slug.unique', $messages);
    }

    #[Test]
    public function all_required_validation_messages_are_present(): void
    {
        $messages = $this->request->messages();
        
        $requiredMessages = [
            'name.required',
            'name.string',
            'name.max',
            'slug.required',
            'slug.string',
            'slug.unique',
            'sort_order.integer',
            'sort_order.min',
        ];

        foreach ($requiredMessages as $messageKey) {
            $this->assertArrayHasKey($messageKey, $messages, "Missing validation message: {$messageKey}");
        }
    }
}
