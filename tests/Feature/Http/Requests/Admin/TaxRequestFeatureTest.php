<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\TaxRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature test for TaxRequest class.
 * Tests validation rules, authorization logic, and custom attributes/messages.
 */
class TaxRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /**
     * Test successful validation with valid data.
     */
    #[Test]
    public function validation_passes_with_valid_data(): void
    {
        $request = new TaxRequest();

        $validData = [
            'name' => 'VAT 21%',
            'rate' => 21.0,
        ];

        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test validation fails when required fields are missing.
     */
    #[Test]
    public function validation_fails_with_missing_required_fields(): void
    {
        $request = new TaxRequest();

        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('rate', $validator->errors()->toArray());
    }

    /**
     * Test validation fails when name exceeds maximum length.
     */
    #[Test]
    public function validation_fails_when_name_too_long(): void
    {
        $request = new TaxRequest();

        $invalidData = [
            'name' => str_repeat('a', 256), // Exceeds max length of 255
            'rate' => 21.0,
        ];

        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /**
     * Test validation fails when rate is not numeric.
     */
    #[Test]
    public function validation_fails_when_rate_not_numeric(): void
    {
        $request = new TaxRequest();

        $invalidData = [
            'name' => 'VAT',
            'rate' => 'not-a-number',
        ];

        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('rate', $validator->errors()->toArray());
    }

    /**
     * Test validation fails when rate is negative.
     */
    #[Test]
    public function validation_fails_when_rate_negative(): void
    {
        $request = new TaxRequest();

        $invalidData = [
            'name' => 'VAT',
            'rate' => -5.0,
        ];

        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('rate', $validator->errors()->toArray());
    }

    /**
     * Test validation accepts zero rate.
     */
    #[Test]
    public function validation_accepts_zero_rate(): void
    {
        $request = new TaxRequest();

        $validData = [
            'name' => 'Tax Free',
            'rate' => 0,
        ];

        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test validation accepts decimal rates.
     */
    #[Test]
    public function validation_accepts_decimal_rates(): void
    {
        $request = new TaxRequest();

        $validData = [
            'name' => 'Reduced VAT',
            'rate' => 10.5,
        ];

        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test validation accepts integer rates.
     */
    #[Test]
    public function validation_accepts_integer_rates(): void
    {
        $request = new TaxRequest();

        $validData = [
            'name' => 'Standard VAT',
            'rate' => 21,
        ];

        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test authorization passes when user is authenticated.
     */
    #[Test]
    public function authorization_passes_when_authenticated(): void
    {
        $this->actingAs($this->user, 'backpack');

        $request = new TaxRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * Test authorization fails when user is not authenticated.
     */
    #[Test]
    public function authorization_fails_when_not_authenticated(): void
    {
        $request = new TaxRequest();
        $this->assertFalse($request->authorize());
    }

    /**
     * Test custom attributes are correctly defined.
     */
    #[Test]
    public function custom_attributes_are_defined(): void
    {
        $request = new TaxRequest();
        $attributes = $request->attributes();

        $expectedAttributes = [
            'name' => __('tax.name'),
            'rate' => __('tax.rate'),
        ];

        $this->assertEquals($expectedAttributes, $attributes);
    }

    /**
     * Test custom messages are correctly defined.
     */
    #[Test]
    public function custom_messages_are_defined(): void
    {
        $request = new TaxRequest();
        $messages = $request->messages();

        $expectedMessages = [
            'name.required' => __('tax.name_required'),
            'rate.required' => __('tax.rate_required'),
            'rate.numeric' => __('tax.rate_numeric'),
            'rate.min' => __('tax.rate_min'),
        ];

        $this->assertEquals($expectedMessages, $messages);
    }

    /**
     * Test validation with edge case values.
     */
    #[Test]
    public function validation_with_edge_case_values(): void
    {
        $request = new TaxRequest();

        // Test with very high rate
        $validData = [
            'name' => 'High Tax',
            'rate' => 100.0,
        ];

        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());

        // Test with very precise decimal
        $validData = [
            'name' => 'Precise Tax',
            'rate' => 15.123456,
        ];

        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());
    }

    /**
     * Test name field accepts various valid string formats.
     */
    #[Test]
    public function name_accepts_various_valid_formats(): void
    {
        $request = new TaxRequest();

        $validNames = [
            'VAT',
            'Value Added Tax',
            'Tax-with-dashes',
            'Tax_with_underscores',
            'Tax (with parentheses)',
            'Tax 21%',
            'Tax & Co.',
        ];

        foreach ($validNames as $name) {
            $validData = [
                'name' => $name,
                'rate' => 20.0,
            ];

            $validator = Validator::make($validData, $request->rules());
            $this->assertTrue($validator->passes(), "Failed for name: {$name}");
        }
    }

    /**
     * Test validation with custom messages.
     */
    #[Test]
    public function validation_with_custom_messages(): void
    {
        $request = new TaxRequest();

        $invalidData = [
            'name' => '',
            'rate' => '',
        ];

        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        
        $errors = $validator->errors();
        $this->assertStringContainsString('tax.', $errors->first('name'));
        $this->assertStringContainsString('tax.', $errors->first('rate'));
    }
}
