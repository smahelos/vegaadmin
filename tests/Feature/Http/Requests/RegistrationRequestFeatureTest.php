<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\RegistrationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for RegistrationRequest
 * 
 * Tests complete validation flow with HTTP context and database interactions
 * Tests user registration validation scenarios, authorization, and validation with database constraints
 */
class RegistrationRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected array $validRegistrationData;

    /**
     * Set up the test environment.
     * Creates valid registration data for request testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up valid registration data
        $this->setupValidRegistrationData();
    }

    /**
     * Setup valid registration data for testing
     */
    private function setupValidRegistrationData(): void
    {
        $this->validRegistrationData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'street' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'zip' => $this->faker->postcode,
            'country' => $this->faker->country,
            'phone' => $this->faker->phoneNumber,
            'ico' => $this->faker->numerify('########'),
            'dic' => $this->faker->numerify('CZ########'),
            'description' => $this->faker->sentence,
            'account_number' => $this->faker->numerify('##########'),
            'bank_code' => $this->faker->numerify('####'),
            'iban' => 'CZ' . $this->faker->numerify('################'),
            'swift' => $this->faker->lexify('????????'),
            'bank_name' => $this->faker->company . ' Bank',
        ];
    }

    #[Test]
    public function validation_passes_with_valid_data()
    {
        $request = new RegistrationRequest();
        $validator = Validator::make($this->validRegistrationData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_when_required_fields_missing()
    {
        $requiredFields = ['name', 'email', 'password', 'street', 'city', 'zip', 'country'];
        
        foreach ($requiredFields as $field) {
            $invalidData = $this->validRegistrationData;
            unset($invalidData[$field]);

            $request = new RegistrationRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail when {$field} is missing");
            $this->assertArrayHasKey($field, $validator->errors()->toArray(), "Should have error for missing {$field}");
        }
    }

    #[Test]
    public function validation_fails_with_invalid_email_format()
    {
        $invalidData = $this->validRegistrationData;
        $invalidData['email'] = 'invalid-email-format';

        $request = new RegistrationRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_duplicate_email()
    {
        // Create existing user
        User::factory()->create(['email' => 'existing@example.com']);

        $invalidData = $this->validRegistrationData;
        $invalidData['email'] = 'existing@example.com';

        $request = new RegistrationRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        // Check if error message is translated or contains key parts
        $errorMessage = $validator->errors()->first('email');
        $this->assertTrue(
            str_contains($errorMessage, 'unique') || 
            str_contains($errorMessage, 'již používán') ||
            str_contains($errorMessage, 'already taken')
        );
    }

    #[Test]
    public function validation_fails_with_short_password()
    {
        $invalidData = $this->validRegistrationData;
        $invalidData['password'] = '123';
        $invalidData['password_confirmation'] = '123';

        $request = new RegistrationRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_unconfirmed_password()
    {
        $invalidData = $this->validRegistrationData;
        $invalidData['password'] = 'password123';
        $invalidData['password_confirmation'] = 'different123';

        $request = new RegistrationRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_minimal_required_data()
    {
        $minimalData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'street' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'zip' => $this->faker->postcode,
            'country' => $this->faker->country,
        ];

        $request = new RegistrationRequest();
        $validator = Validator::make($minimalData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_when_bank_code_missing_with_account_number()
    {
        $invalidData = $this->validRegistrationData;
        $invalidData['account_number'] = '1234567890';
        unset($invalidData['bank_code']);

        $request = new RegistrationRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('bank_code', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_swift_missing_with_iban()
    {
        $invalidData = $this->validRegistrationData;
        $invalidData['iban'] = 'CZ1234567890123456';
        unset($invalidData['swift']);

        $request = new RegistrationRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('swift', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_without_optional_bank_fields()
    {
        $dataWithoutBank = $this->validRegistrationData;
        unset($dataWithoutBank['account_number']);
        unset($dataWithoutBank['bank_code']);
        unset($dataWithoutBank['iban']);
        unset($dataWithoutBank['swift']);
        unset($dataWithoutBank['bank_name']);

        $request = new RegistrationRequest();
        $validator = Validator::make($dataWithoutBank, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_with_too_long_strings()
    {
        $fieldsWithLimits = [
            'name' => str_repeat('a', 256), // max 255
            'email' => str_repeat('a', 250) . '@test.com', // max 255
            'street' => str_repeat('a', 256), // max 255
            'city' => str_repeat('a', 256), // max 255
            'zip' => str_repeat('a', 21), // max 20
            'country' => str_repeat('a', 101), // max 100
            'ico' => str_repeat('a', 21), // max 20
            'dic' => str_repeat('a', 31), // max 30
            'description' => str_repeat('a', 1001), // max 1000
        ];

        foreach ($fieldsWithLimits as $field => $value) {
            $invalidData = $this->validRegistrationData;
            $invalidData[$field] = $value;

            $request = new RegistrationRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail when {$field} exceeds max length");
            $this->assertArrayHasKey($field, $validator->errors()->toArray(), "Should have error for {$field} max length");
        }
    }

    #[Test]
    public function authorize_always_returns_true()
    {
        $request = new RegistrationRequest();
        
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function request_has_custom_validation_messages()
    {
        $request = new RegistrationRequest();
        $messages = $request->messages();

        $expectedKeys = [
            'password.required',
            'street.required',
            'city.required',
            'zip.required',
            'country.required',
            'name.required',
            'email.required',
            'email.email',
            'email.unique',
            'password.min',
            'password.confirmed',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $messages, "Should have custom message for {$key}");
        }
    }
}
