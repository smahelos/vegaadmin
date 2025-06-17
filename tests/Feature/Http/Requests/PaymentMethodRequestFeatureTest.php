<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\PaymentMethodRequest;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for PaymentMethodRequest
 * 
 * Tests complete validation flow with HTTP context and database interactions
 * Tests payment method validation scenarios, authorization, and validation with database constraints
 */
class PaymentMethodRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected array $validPaymentMethodData;

    /**
     * Set up the test environment.
     * Creates test user and valid payment method data for request testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();
        
        // Set up valid payment method data
        $this->setupValidPaymentMethodData();
    }

    /**
     * Setup valid payment method data for testing
     */
    private function setupValidPaymentMethodData(): void
    {
        $this->validPaymentMethodData = [
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->slug,
            'description' => $this->faker->sentence,
            'is_active' => true,
        ];
    }

    #[Test]
    public function validation_passes_with_valid_data()
    {
        $request = new PaymentMethodRequest();
        $validator = Validator::make($this->validPaymentMethodData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_when_required_fields_missing()
    {
        $requiredFields = ['name', 'slug'];
        
        foreach ($requiredFields as $field) {
            $invalidData = $this->validPaymentMethodData;
            unset($invalidData[$field]);

            $request = new PaymentMethodRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail when {$field} is missing");
            $this->assertArrayHasKey($field, $validator->errors()->toArray(), "Should have error for missing {$field}");
        }
    }

    #[Test]
    public function validation_fails_with_long_name()
    {
        $invalidData = $this->validPaymentMethodData;
        $invalidData['name'] = str_repeat('a', 256); // Too long (max 255)

        $request = new PaymentMethodRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_long_slug()
    {
        $invalidData = $this->validPaymentMethodData;
        $invalidData['slug'] = str_repeat('a', 256); // Too long (max 255)

        $request = new PaymentMethodRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_duplicate_slug()
    {
        // Create existing payment method
        PaymentMethod::factory()->create(['slug' => 'existing-slug']);

        $invalidData = $this->validPaymentMethodData;
        $invalidData['slug'] = 'existing-slug';

        $request = new PaymentMethodRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_same_slug_for_update()
    {
        // Create existing payment method
        $existingPaymentMethod = PaymentMethod::factory()->create(['slug' => 'existing-slug']);

        $updateData = $this->validPaymentMethodData;
        $updateData['slug'] = 'existing-slug';

        $request = new PaymentMethodRequest();
        $request->merge(['id' => $existingPaymentMethod->id]);
        $validator = Validator::make($updateData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_passes_with_minimal_required_data()
    {
        $minimalData = [
            'name' => 'Test Payment Method',
            'slug' => 'test-payment-method',
        ];

        $request = new PaymentMethodRequest();
        $validator = Validator::make($minimalData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_passes_with_nullable_fields_set_to_null()
    {
        $dataWithNulls = $this->validPaymentMethodData;
        $dataWithNulls['description'] = null;

        $request = new PaymentMethodRequest();
        $validator = Validator::make($dataWithNulls, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_with_invalid_boolean_is_active()
    {
        $invalidData = $this->validPaymentMethodData;
        $invalidData['is_active'] = 'invalid-boolean';

        $request = new PaymentMethodRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('is_active', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_boolean_values_for_is_active()
    {
        $booleanValues = [true, false, 1, 0, '1', '0'];
        
        foreach ($booleanValues as $value) {
            $validData = $this->validPaymentMethodData;
            $validData['is_active'] = $value;
            $validData['slug'] = 'test-slug-' . $value; // Make slug unique

            $request = new PaymentMethodRequest();
            $validator = Validator::make($validData, $request->rules(), $request->messages());

            $this->assertFalse($validator->fails(), "Validation should pass for is_active value: " . json_encode($value));
        }
    }

    #[Test]
    public function authorization_passes_when_authenticated()
    {
        $this->actingAs($this->user);

        $request = new PaymentMethodRequest();
        
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function authorization_fails_when_not_authenticated()
    {
        $request = new PaymentMethodRequest();
        
        $this->assertFalse($request->authorize());
    }

    #[Test]
    public function request_has_custom_attributes()
    {
        $request = new PaymentMethodRequest();
        $attributes = $request->attributes();

        $expectedKeys = ['name', 'slug', 'description', 'is_active'];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $attributes, "Should have custom attribute for {$key}");
        }
    }

    #[Test]
    public function request_has_custom_validation_messages()
    {
        $request = new PaymentMethodRequest();
        $messages = $request->messages();

        $expectedKeys = [
            'name.required',
            'slug.required',
            'slug.unique',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $messages, "Should have custom message for {$key}");
        }
    }

    #[Test]
    public function validation_passes_without_optional_fields()
    {
        $dataWithoutOptional = [
            'name' => 'Credit Card',
            'slug' => 'credit-card',
            'is_active' => true,
        ];

        $request = new PaymentMethodRequest();
        $validator = Validator::make($dataWithoutOptional, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_passes_with_different_string_types()
    {
        $stringTestCases = [
            ['name' => 'Simple Name', 'slug' => 'simple-name'],
            ['name' => 'Name with Numbers 123', 'slug' => 'name-with-numbers-123'],
            ['name' => 'Name-with-Dashes', 'slug' => 'name-with-dashes'],
            ['name' => 'Name_with_Underscores', 'slug' => 'name_with_underscores'],
        ];
        
        foreach ($stringTestCases as $index => $testCase) {
            $validData = array_merge($this->validPaymentMethodData, $testCase);
            $validData['slug'] = $testCase['slug'] . '-' . $index; // Make slug unique

            $request = new PaymentMethodRequest();
            $validator = Validator::make($validData, $request->rules(), $request->messages());

            $this->assertFalse($validator->fails(), "Validation should pass for name: {$testCase['name']}");
        }
    }

    #[Test]
    public function validation_passes_with_long_valid_description()
    {
        $validData = $this->validPaymentMethodData;
        $validData['description'] = str_repeat('This is a test description. ', 100); // Long but valid

        $request = new PaymentMethodRequest();
        $validator = Validator::make($validData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }
}
