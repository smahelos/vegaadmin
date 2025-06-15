<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\ClientRequest;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Unit tests for ClientRequest
 * 
 * Tests validation rules, authorization logic, custom messages, and attributes
 * These tests do not require HTTP context and focus on request configuration
 */
class ClientRequestTest extends TestCase
{
    protected ClientRequest $request;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new ClientRequest();
    }

    /**
     * Test that validation rules are correctly defined.
     *
     * @return void
     */
    public function test_validation_rules_are_correctly_defined()
    {
        $expectedRules = [
            'name' => 'required|string|max:255',
            'shortcut' => 'nullable|string|max:50',
            'phone' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'zip' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'ico' => 'nullable|string|max:20',
            'dic' => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'is_default' => 'nullable|boolean',
            'email' => [
                'required',
                'email',
            ],
        ];

        $actualRules = $this->request->rules();

        $this->assertEquals($expectedRules, $actualRules);
    }

    /**
     * Test required fields are properly identified.
     *
     * @return void
     */
    public function test_required_fields_are_properly_identified()
    {
        $rules = $this->request->rules();
        
        $requiredFields = [];
        foreach ($rules as $field => $rule) {
            if (is_string($rule) && str_contains($rule, 'required')) {
                $requiredFields[] = $field;
            } elseif (is_array($rule) && in_array('required', $rule)) {
                $requiredFields[] = $field;
            }
        }

        $expectedRequiredFields = ['name', 'phone', 'street', 'city', 'zip', 'country', 'email'];
        
        sort($requiredFields);
        sort($expectedRequiredFields);
        
        $this->assertEquals($expectedRequiredFields, $requiredFields);
    }

    /**
     * Test nullable fields are properly identified.
     *
     * @return void
     */
    public function test_nullable_fields_are_properly_identified()
    {
        $rules = $this->request->rules();
        
        $nullableFields = [];
        foreach ($rules as $field => $rule) {
            if (is_string($rule) && str_contains($rule, 'nullable')) {
                $nullableFields[] = $field;
            }
        }

        $expectedNullableFields = ['shortcut', 'ico', 'dic', 'description', 'is_default'];
        
        sort($nullableFields);
        sort($expectedNullableFields);
        
        $this->assertEquals($expectedNullableFields, $nullableFields);
    }

    /**
     * Test string fields have correct max length constraints.
     *
     * @return void
     */
    public function test_string_fields_have_correct_max_length()
    {
        $rules = $this->request->rules();
        
        $expectedMaxLengths = [
            'name' => 255,
            'shortcut' => 50,
            'phone' => 255,
            'street' => 255,
            'city' => 255,
            'zip' => 20,
            'country' => 100,
            'ico' => 20,
            'dic' => 30,
        ];

        foreach ($expectedMaxLengths as $field => $expectedLength) {
            $rule = $rules[$field];
            $this->assertStringContainsString("max:{$expectedLength}", $rule, 
                "Field {$field} should have max:{$expectedLength}");
        }
    }

    /**
     * Test email field has proper validation rules.
     *
     * @return void
     */
    public function test_email_field_has_proper_validation()
    {
        $rules = $this->request->rules();
        
        $this->assertArrayHasKey('email', $rules);
        $this->assertIsArray($rules['email']);
        $this->assertContains('required', $rules['email']);
        $this->assertContains('email', $rules['email']);
    }

    /**
     * Test boolean field validation.
     *
     * @return void
     */
    public function test_boolean_field_validation()
    {
        $rules = $this->request->rules();
        
        $this->assertArrayHasKey('is_default', $rules);
        $this->assertStringContainsString('boolean', $rules['is_default']);
        $this->assertStringContainsString('nullable', $rules['is_default']);
    }

    /**
     * Test authorize method returns true when user is authenticated.
     *
     * @return void
     */
    public function test_authorize_returns_true_when_authenticated()
    {
        // Mock Auth::check() to return true
        Auth::shouldReceive('check')
            ->once()
            ->andReturn(true);

        $this->assertTrue($this->request->authorize());
    }

    /**
     * Test authorize method returns false when user is not authenticated.
     *
     * @return void
     */
    public function test_authorize_returns_false_when_not_authenticated()
    {
        // Mock Auth::check() to return false
        Auth::shouldReceive('check')
            ->once()
            ->andReturn(false);

        $this->assertFalse($this->request->authorize());
    }

    /**
     * Test custom validation messages are correctly defined.
     *
     * @return void
     */
    public function test_custom_validation_messages_are_correctly_defined()
    {
        $expectedMessages = [
            'name.required' => __('clients.validation.name_required'),
            'email.required' => __('clients.validation.email_required'),
            'street.required' => __('clients.validation.street_required'),
            'city.required' => __('clients.validation.city_required'),
            'zip.required' => __('clients.validation.zip_required'),
            'country.required' => __('clients.validation.country_required'),
        ];

        $actualMessages = $this->request->messages();

        $this->assertEquals($expectedMessages, $actualMessages);
    }

    /**
     * Test that all required fields have custom error messages.
     *
     * @return void
     */
    public function test_all_required_fields_have_custom_messages()
    {
        $messages = $this->request->messages();
        $requiredFieldsWithCustomMessages = [];
        
        foreach ($messages as $key => $message) {
            if (str_ends_with($key, '.required')) {
                $field = str_replace('.required', '', $key);
                $requiredFieldsWithCustomMessages[] = $field;
            }
        }

        $expectedFieldsWithMessages = ['name', 'email', 'street', 'city', 'zip', 'country'];
        
        sort($requiredFieldsWithCustomMessages);
        sort($expectedFieldsWithMessages);
        
        $this->assertEquals($expectedFieldsWithMessages, $requiredFieldsWithCustomMessages);
    }

    /**
     * Test attributes method returns empty array (default implementation).
     *
     * @return void
     */
    public function test_attributes_method_returns_empty_array()
    {
        $this->assertEquals([], $this->request->attributes());
    }

    /**
     * Test that request extends FormRequest.
     *
     * @return void
     */
    public function test_request_extends_form_request()
    {
        $this->assertInstanceOf(\Illuminate\Foundation\Http\FormRequest::class, $this->request);
    }

    /**
     * Test that all validation messages use translation keys.
     *
     * @return void
     */
    public function test_validation_messages_use_translation_keys()
    {
        $messages = $this->request->messages();
        
        // Expected translation keys
        $expectedKeys = [
            'name.required' => 'clients.validation.name_required',
            'email.required' => 'clients.validation.email_required',
            'street.required' => 'clients.validation.street_required',
            'city.required' => 'clients.validation.city_required',
            'zip.required' => 'clients.validation.zip_required',
            'country.required' => 'clients.validation.country_required',
        ];
        
        // Check that each message resolves to the expected translation
        foreach ($expectedKeys as $rule => $translationKey) {
            $this->assertTrue(array_key_exists($rule, $messages), 
                "Message for {$rule} should be defined");
            
            // Check that the message matches what Laravel's __() function would return
            $expectedMessage = __($translationKey);
            $this->assertEquals($expectedMessage, $messages[$rule], 
                "Message for {$rule} should use translation key {$translationKey}");
        }
    }
}
