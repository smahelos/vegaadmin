<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\SupplierRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class SupplierRequestTest extends TestCase
{
    use WithFaker;

    private SupplierRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new SupplierRequest();
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
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('phone', $rules);
        $this->assertArrayHasKey('street', $rules);
        $this->assertArrayHasKey('city', $rules);
        $this->assertArrayHasKey('zip', $rules);
        $this->assertArrayHasKey('country', $rules);
        $this->assertArrayHasKey('shortcut', $rules);
        $this->assertArrayHasKey('ico', $rules);
        $this->assertArrayHasKey('dic', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('is_default', $rules);
        
        // Bank account fields
        $this->assertArrayHasKey('account_number', $rules);
        $this->assertArrayHasKey('bank_code', $rules);
        $this->assertArrayHasKey('iban', $rules);
        $this->assertArrayHasKey('swift', $rules);
        $this->assertArrayHasKey('bank_name', $rules);
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
        $this->assertStringContainsString('required', $rules['email'][0]);
        $this->assertStringContainsString('required', $rules['phone']);
        $this->assertStringContainsString('required', $rules['street']);
        $this->assertStringContainsString('required', $rules['city']);
        $this->assertStringContainsString('required', $rules['zip']);
        $this->assertStringContainsString('required', $rules['country']);
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
        $this->assertStringContainsString('nullable', $rules['shortcut']);
        $this->assertStringContainsString('nullable', $rules['ico']);
        $this->assertStringContainsString('nullable', $rules['dic']);
        $this->assertStringContainsString('nullable', $rules['description']);
        $this->assertStringContainsString('nullable', $rules['is_default']);
        
        // Bank account fields
        $this->assertStringContainsString('nullable', $rules['account_number']);
        $this->assertStringContainsString('nullable', $rules['bank_code']);
        $this->assertStringContainsString('nullable', $rules['iban']);
        $this->assertStringContainsString('nullable', $rules['swift']);
        $this->assertStringContainsString('nullable', $rules['bank_name']);
    }

    /**
     * Test that string fields have correct max length.
     *
     * @return void
     */
    public function test_string_fields_have_correct_max_length()
    {
        $rules = $this->request->rules();

        $this->assertStringContainsString('max:255', $rules['name']);
        $this->assertStringContainsString('max:50', $rules['shortcut']);
        $this->assertStringContainsString('max:255', $rules['phone']);
        $this->assertStringContainsString('max:255', $rules['street']);
        $this->assertStringContainsString('max:255', $rules['city']);
        $this->assertStringContainsString('max:20', $rules['zip']);
        $this->assertStringContainsString('max:100', $rules['country']);
        $this->assertStringContainsString('max:20', $rules['ico']);
        $this->assertStringContainsString('max:30', $rules['dic']);
        
        // Bank account fields
        $this->assertStringContainsString('max:50', $rules['account_number']);
        $this->assertStringContainsString('max:10', $rules['bank_code']);
        $this->assertStringContainsString('max:50', $rules['iban']);
        $this->assertStringContainsString('max:20', $rules['swift']);
        $this->assertStringContainsString('max:255', $rules['bank_name']);
    }

    /**
     * Test that email field has proper validation.
     *
     * @return void
     */
    public function test_email_field_has_proper_validation()
    {
        $rules = $this->request->rules();

        $this->assertContains('required', $rules['email']);
        $this->assertContains('email', $rules['email']);
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
    }

    /**
     * Test that conditional bank validation rules work.
     *
     * @return void
     */
    public function test_conditional_bank_validation_rules()
    {
        $rules = $this->request->rules();

        // Bank code is required when account number is present
        $this->assertStringContainsString('required_with:account_number', $rules['bank_code']);
        
        // SWIFT is required when IBAN is present
        $this->assertStringContainsString('required_with:iban', $rules['swift']);
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
     * Test that custom validation messages are correctly defined.
     *
     * @return void
     */
    public function test_custom_validation_messages_are_correctly_defined()
    {
        $messages = $this->request->messages();

        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);
        
        // Check that basic validation messages exist
        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('email.required', $messages);
        $this->assertArrayHasKey('phone.required', $messages);
        $this->assertArrayHasKey('street.required', $messages);
        $this->assertArrayHasKey('city.required', $messages);
        $this->assertArrayHasKey('zip.required', $messages);
        $this->assertArrayHasKey('country.required', $messages);
        
        // Check bank validation messages
        $this->assertArrayHasKey('account_number.max', $messages);
        $this->assertArrayHasKey('bank_code.required_with', $messages);
        $this->assertArrayHasKey('iban.max', $messages);
        $this->assertArrayHasKey('swift.required_with', $messages);
    }

    /**
     * Test that all required fields have custom messages.
     *
     * @return void
     */
    public function test_all_required_fields_have_custom_messages()
    {
        $messages = $this->request->messages();
        $requiredFields = ['name', 'email', 'phone', 'street', 'city', 'zip', 'country'];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field . '.required', $messages, 
                "Missing required validation message for field: {$field}");
        }
    }

    /**
     * Test that attributes method returns empty array.
     *
     * @return void
     */
    public function test_attributes_method_returns_empty_array()
    {
        $attributes = $this->request->attributes();

        $this->assertIsArray($attributes);
        $this->assertEmpty($attributes);
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
     * Test that validation messages use translation keys.
     *
     * @return void
     */
    public function test_validation_messages_use_translation_keys()
    {
        $messages = $this->request->messages();
        
        // Expected translation keys
        $expectedKeys = [
            'name.required' => 'suppliers.validation.name_required',
            'email.required' => 'suppliers.validation.email_required',
            'email.email' => 'suppliers.validation.email_valid',
            'phone.required' => 'suppliers.validation.phone_required',
            'street.required' => 'suppliers.validation.street_required',
            'city.required' => 'suppliers.validation.city_required',
            'zip.required' => 'suppliers.validation.zip_required',
            'country.required' => 'suppliers.validation.country_required',
            'ico.max' => 'suppliers.validation.ico_format',
            'account_number.max' => 'suppliers.validation.account_number_format',
            'bank_code.required_with' => 'suppliers.validation.bank_code_required',
            'iban.max' => 'suppliers.validation.iban_format',
            'swift.required_with' => 'suppliers.validation.swift_required',
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
