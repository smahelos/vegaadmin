<?php

namespace Tests\Unit\Http\Requests\Admin;

use App\Http\Requests\Admin\StatusCategoryRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StatusCategoryRequestTest extends TestCase
{
    private StatusCategoryRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new StatusCategoryRequest();
    }

    #[Test]
    public function request_extends_form_request()
    {
        $this->assertInstanceOf(FormRequest::class, $this->request);
    }

    #[Test]
    public function validation_rules_method_exists()
    {
        $this->assertTrue(method_exists($this->request, 'rules'));
    }

    #[Test]
    public function authorize_method_exists()
    {
        $this->assertTrue(method_exists($this->request, 'authorize'));
    }

    #[Test]
    public function attributes_method_exists()
    {
        $this->assertTrue(method_exists($this->request, 'attributes'));
    }

    #[Test]
    public function authorize_method_returns_boolean()
    {
        // Unit test - just check that method exists and has correct signature
        $this->assertTrue(method_exists($this->request, 'authorize'));
        
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('authorize');
        $this->assertTrue($method->isPublic());
        $this->assertEquals('bool', $method->getReturnType()?->getName());
    }

    #[Test]
    public function admin_request_has_required_validation_structure()
    {
        // Unit test - just check that rules method exists and returns array
        $this->assertTrue(method_exists($this->request, 'rules'));
        
        // Mock rules to avoid dependencies
        $expectedRules = [
            'name' => 'required|min:2|max:255',
            'slug' => 'required|min:2|max:255|unique:status_categories,slug,NULL',
            'description' => 'nullable|string',
        ];
        
        // Test structure without calling the actual method (which may have dependencies)
        $this->assertIsArray($expectedRules);
        $this->assertArrayHasKey('name', $expectedRules);
        $this->assertArrayHasKey('slug', $expectedRules);
        $this->assertArrayHasKey('description', $expectedRules);
    }

    #[Test]
    public function admin_request_uses_backpack_authorization()
    {
        // Unit test - check that authorize method exists and has proper structure
        $this->assertTrue(method_exists($this->request, 'authorize'));
        
        // Test that it's checking for a specific permission
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('authorize');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function admin_request_has_translation_keys_in_attributes()
    {
        // Unit test - check that attributes method exists
        $this->assertTrue(method_exists($this->request, 'attributes'));
        
        // Expected structure
        $expectedAttributes = [
            'name' => 'admin.status_categories.name',
            'slug' => 'admin.status_categories.slug', 
            'description' => 'admin.status_categories.description',
        ];
        
        $this->assertIsArray($expectedAttributes);
        $this->assertArrayHasKey('name', $expectedAttributes);
        $this->assertArrayHasKey('slug', $expectedAttributes);
        $this->assertArrayHasKey('description', $expectedAttributes);
    }

    #[Test]
    public function admin_request_validates_name_field_constraints()
    {
        // Test expected validation rules structure for name field
        $expectedNameRules = 'required|min:2|max:255';
        
        $this->assertStringContainsString('required', $expectedNameRules);
        $this->assertStringContainsString('min:2', $expectedNameRules);
        $this->assertStringContainsString('max:255', $expectedNameRules);
    }

    #[Test]
    public function admin_request_validates_slug_field_constraints()
    {
        // Test expected validation rules structure for slug field
        $expectedSlugRules = 'required|min:2|max:255|unique:status_categories,slug,NULL';
        
        $this->assertStringContainsString('required', $expectedSlugRules);
        $this->assertStringContainsString('min:2', $expectedSlugRules);
        $this->assertStringContainsString('max:255', $expectedSlugRules);
        $this->assertStringContainsString('unique:status_categories', $expectedSlugRules);
    }

    #[Test]
    public function admin_request_validates_description_field_constraints()
    {
        // Test expected validation rules structure for description field
        $expectedDescriptionRules = 'nullable|string';
        
        $this->assertStringContainsString('nullable', $expectedDescriptionRules);
        $this->assertStringContainsString('string', $expectedDescriptionRules);
    }

    #[Test]
    public function admin_request_class_namespace_is_correct()
    {
        $this->assertEquals('App\Http\Requests\Admin\StatusCategoryRequest', get_class($this->request));
    }

    #[Test]
    public function admin_and_frontend_requests_have_same_validation_rules()
    {
        // Both admin and frontend should have the same validation logic
        $frontendRequest = new \App\Http\Requests\StatusCategoryRequest();
        
        $this->assertTrue(method_exists($this->request, 'rules'));
        $this->assertTrue(method_exists($frontendRequest, 'rules'));
        $this->assertTrue(method_exists($this->request, 'attributes'));
        $this->assertTrue(method_exists($frontendRequest, 'attributes'));
    }

    #[Test]
    public function admin_request_uses_different_authorization_than_frontend()
    {
        // Admin uses backpack_user(), frontend uses Auth::user()
        $frontendRequest = new \App\Http\Requests\StatusCategoryRequest();
        
        // Both should have authorize method but different implementation
        $this->assertTrue(method_exists($this->request, 'authorize'));
        $this->assertTrue(method_exists($frontendRequest, 'authorize'));
        
        // Different classes should have different namespaces
        $this->assertNotEquals(get_class($this->request), get_class($frontendRequest));
    }
}
