<?php

namespace Tests\Unit\Http\Requests\Admin;

use App\Http\Requests\Admin\StatusRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StatusRequestTest extends TestCase
{
    private StatusRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new StatusRequest();
    }

    #[Test]
    public function request_extends_form_request(): void
    {
        $this->assertInstanceOf(FormRequest::class, $this->request);
    }

    #[Test]
    public function validation_rules_are_correctly_defined(): void
    {
        $rules = $this->request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('slug', $rules);
        $this->assertArrayHasKey('category_id', $rules);
        $this->assertArrayHasKey('color', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('is_active', $rules);
    }

    #[Test]
    public function required_fields_have_required_rule(): void
    {
        $rules = $this->request->rules();

        $this->assertStringContainsString('required', $rules['name']);
        $this->assertContains('required', $rules['slug']);
        $this->assertStringContainsString('required', $rules['category_id']);
    }

    #[Test]
    public function category_id_has_exists_validation(): void
    {
        $rules = $this->request->rules();

        $this->assertStringContainsString('exists:status_categories,id', $rules['category_id']);
    }

    #[Test]
    public function string_fields_have_string_rule(): void
    {
        $rules = $this->request->rules();

        $this->assertStringContainsString('string', $rules['name']);
        $this->assertContains('string', $rules['slug']);
    }

    #[Test]
    public function fields_have_max_length_validation(): void
    {
        $rules = $this->request->rules();

        $this->assertStringContainsString('max:255', $rules['name']);
        $this->assertContains('max:255', $rules['slug']);
    }    #[Test]
    public function slug_has_unique_validation(): void
    {
        $rules = $this->request->rules();
        
        $this->assertIsArray($rules['slug']);
        
        // Check that some form of unique rule exists (Rule::unique creates an object)
        $hasUniqueRule = false;
        foreach ($rules['slug'] as $rule) {
            if (is_object($rule) && get_class($rule) === 'Illuminate\Validation\Rules\Unique') {
                $hasUniqueRule = true;
                break;
            }
        }
        
        $this->assertTrue($hasUniqueRule, 'Slug should have unique validation rule');
    }

    #[Test]
    public function optional_fields_are_nullable(): void
    {
        $rules = $this->request->rules();

        $this->assertStringContainsString('nullable', $rules['color']);
        $this->assertStringContainsString('nullable', $rules['description']);
    }

    #[Test]
    public function is_active_field_is_boolean(): void
    {
        $rules = $this->request->rules();

        $this->assertEquals('boolean', $rules['is_active']);
    }

    #[Test]
    public function custom_attributes_method_exists(): void
    {
        $this->assertTrue(method_exists($this->request, 'attributes'));
    }

    #[Test]
    public function custom_error_messages_method_exists(): void
    {
        $this->assertTrue(method_exists($this->request, 'messages'));
    }

    #[Test]
    public function authorize_method_exists(): void
    {
        $this->assertTrue(method_exists($this->request, 'authorize'));
    }

    #[Test]
    public function admin_request_requires_category_id_unlike_frontend(): void
    {
        $frontendRequest = new \App\Http\Requests\StatusRequest();
        $adminRequest = new StatusRequest();
        
        $frontendRules = $frontendRequest->rules();
        $adminRules = $adminRequest->rules();
        
        // Frontend should not require category_id
        $this->assertArrayNotHasKey('category_id', $frontendRules);
        
        // Admin should require category_id
        $this->assertArrayHasKey('category_id', $adminRules);
        $this->assertStringContainsString('required', $adminRules['category_id']);
    }
}
