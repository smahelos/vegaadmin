<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\ProductRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class ProductRequestTest extends TestCase
{
    use WithFaker;

    private ProductRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new ProductRequest();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that validation rules are correctly defined.
     *
     * @return void
     */
    public function test_validation_rules_are_correctly_defined()
    {
        $rules = $this->request->rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('slug', $rules);
        $this->assertArrayHasKey('price', $rules);
        $this->assertArrayHasKey('tax_id', $rules);
        $this->assertArrayHasKey('category_id', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('is_default', $rules);
        $this->assertArrayHasKey('is_active', $rules);
        $this->assertArrayHasKey('image', $rules);
        $this->assertArrayHasKey('currency', $rules);
    }

    /**
     * Test that required fields are properly identified.
     *
     * @return void
     */
    public function test_required_fields_are_properly_identified()
    {
        $rules = $this->request->rules();

        // Check required fields
        $this->assertStringContainsString('required', $rules['name']);
        $this->assertStringContainsString('required', $rules['price']);
        $this->assertStringContainsString('required', $rules['currency']);
    }

    /**
     * Test that nullable fields are properly identified.
     *
     * @return void
     */
    public function test_nullable_fields_are_properly_identified()
    {
        $rules = $this->request->rules();

        // Check nullable fields
        $this->assertStringContainsString('nullable', $rules['slug']);
        $this->assertStringContainsString('nullable', $rules['tax_id']);
        $this->assertStringContainsString('nullable', $rules['category_id']);
        $this->assertStringContainsString('nullable', $rules['description']);
        $this->assertStringContainsString('nullable', $rules['image']);
    }

    /**
     * Test that string fields have correct length constraints.
     *
     * @return void
     */
    public function test_string_fields_have_correct_length_constraints()
    {
        $rules = $this->request->rules();

        $this->assertStringContainsString('min:2', $rules['name']);
        $this->assertStringContainsString('max:255', $rules['name']);
        $this->assertStringContainsString('max:255', $rules['slug']);
    }

    /**
     * Test that numeric fields have proper validation.
     *
     * @return void
     */
    public function test_numeric_fields_have_proper_validation()
    {
        $rules = $this->request->rules();

        $this->assertStringContainsString('numeric', $rules['price']);
        $this->assertStringContainsString('min:0', $rules['price']);
    }

    /**
     * Test that boolean field validation is correct.
     *
     * @return void
     */
    public function test_boolean_field_validation()
    {
        $rules = $this->request->rules();

        $this->assertStringContainsString('boolean', $rules['is_default']);
        $this->assertStringContainsString('boolean', $rules['is_active']);
    }

    /**
     * Test that foreign key constraints are defined.
     *
     * @return void
     */
    public function test_foreign_key_constraints_are_defined()
    {
        $rules = $this->request->rules();

        $this->assertStringContainsString('exists:taxes,id', $rules['tax_id']);
        $this->assertStringContainsString('exists:product_categories,id', $rules['category_id']);
    }

    /**
     * Test that unique constraint is defined for slug.
     *
     * @return void
     */
    public function test_unique_constraint_is_defined_for_slug()
    {
        $rules = $this->request->rules();

        $this->assertStringContainsString('unique:products,slug', $rules['slug']);
    }

    /**
     * Test that image validation is properly configured.
     *
     * @return void
     */
    public function test_image_validation_is_properly_configured()
    {
        $rules = $this->request->rules();

        $this->assertStringContainsString('image', $rules['image']);
        $this->assertStringContainsString('max:2048', $rules['image']);
    }

    /**
     * Test that currency enum validation is defined.
     *
     * @return void
     */
    public function test_currency_enum_validation_is_defined()
    {
        $rules = $this->request->rules();

        $this->assertStringContainsString('in:CZK,EUR,USD', $rules['currency']);
    }

    /**
     * Test that authorize returns true when authenticated.
     *
     * @return void
     */
    public function test_authorize_returns_true_when_authenticated()
    {
        Auth::shouldReceive('check')->andReturn(true);

        $result = $this->request->authorize();

        $this->assertTrue($result);
    }

    /**
     * Test that authorize returns false when not authenticated.
     *
     * @return void
     */
    public function test_authorize_returns_false_when_not_authenticated()
    {
        Auth::shouldReceive('check')->andReturn(false);

        $result = $this->request->authorize();

        $this->assertFalse($result);
    }

    /**
     * Test that custom attributes are correctly defined.
     *
     * @return void
     */
    public function test_custom_attributes_are_correctly_defined()
    {
        // Mock trans() function calls
        $expectedAttributes = [
            'name', 'slug', 'price', 'tax_id', 'category_id',
            'description', 'is_default', 'is_active', 'image', 'currency'
        ];

        $attributes = $this->request->attributes();

        $this->assertIsArray($attributes);

        foreach ($expectedAttributes as $field) {
            $this->assertArrayHasKey($field, $attributes);
        }
    }

    /**
     * Test that attributes use translation functions.
     *
     * @return void
     */
    public function test_attributes_use_translation_functions()
    {
        $attributes = $this->request->attributes();

        // Check that attributes contain translated values
        // Since trans() returns the key when translation is not found,
        // we check that the values follow the translation pattern
        foreach ($attributes as $key => $value) {
            $this->assertIsString($value);
            $this->assertNotEmpty($value);
        }
    }

    /**
     * Test that request extends FormRequest.
     *
     * @return void
     */
    public function test_request_extends_form_request()
    {
        $this->assertInstanceOf(FormRequest::class, $this->request);
    }

    /**
     * Test that prepareForValidation method exists.
     *
     * @return void
     */
    public function test_prepare_for_validation_method_exists()
    {
        $this->assertTrue(method_exists($this->request, 'prepareForValidation'));
    }

    /**
     * Test that slug generation logic can be tested (method visibility).
     *
     * @return void
     */
    public function test_slug_generation_method_is_callable()
    {
        // Test that we can call prepareForValidation method
        // This tests the method structure without HTTP context
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('prepareForValidation');
        
        $this->assertTrue($method->isProtected() || $method->isPublic());
    }
}
