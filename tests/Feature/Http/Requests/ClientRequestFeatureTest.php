<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\ClientRequest;
use App\Models\User;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Feature tests for ClientRequest
 * 
 * Tests complete validation flow with HTTP context and database interactions
 * Tests validation scenarios, authorization with real users, and validation with database constraints
 */
class ClientRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected array $validClientData;

    /**
     * Set up the test environment.
     * Creates test user and valid client data for request testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions and user
        $this->createPermissionsAndUser();
        
        // Set up valid client data
        $this->setupValidClientData();
    }

    /**
     * Create necessary permissions and test user
     */
    private function createPermissionsAndUser(): void
    {
        // Create permissions
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_client', 'guard_name' => 'web']);
        
        // Create role
        $frontendRole = Role::firstOrCreate(['name' => 'frontend_user', 'guard_name' => 'web']);
        $frontendRole->givePermissionTo('frontend.can_create_edit_client');
        
        // Create test user
        $this->user = User::factory()->create([
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ]);
        $this->user->assignRole($frontendRole);
    }

    /**
     * Setup valid client data for testing
     */
    private function setupValidClientData(): void
    {
        $this->validClientData = [
            'name' => $this->faker->company,
            'shortcut' => $this->faker->lexify('???'),
            'email' => $this->faker->unique()->companyEmail,
            'phone' => $this->faker->phoneNumber,
            'street' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'zip' => $this->faker->postcode,
            'country' => $this->faker->country,
            'ico' => $this->faker->numerify('########'),
            'dic' => $this->faker->numerify('CZ########'),
            'description' => $this->faker->sentence,
            'is_default' => false,
        ];
    }

    /**
     * Test validation passes with valid data.
     *
     * @return void
     */
    public function test_validation_passes_with_valid_data()
    {
        $request = new ClientRequest();
        $validator = Validator::make($this->validClientData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    /**
     * Test validation fails when required fields are missing.
     *
     * @return void
     */
    public function test_validation_fails_when_required_fields_missing()
    {
        $requiredFields = ['name', 'email', 'phone', 'street', 'city', 'zip', 'country'];
        
        foreach ($requiredFields as $field) {
            $invalidData = $this->validClientData;
            unset($invalidData[$field]);
            
            $request = new ClientRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail when {$field} is missing");
            $this->assertArrayHasKey($field, $validator->errors()->toArray(), 
                "Should have error for missing {$field}");
        }
    }

    /**
     * Test validation fails with invalid email format.
     *
     * @return void
     */
    public function test_validation_fails_with_invalid_email()
    {
        $invalidEmails = ['invalid-email', 'test@', '@example.com', 'test.example.com'];
        
        foreach ($invalidEmails as $invalidEmail) {
            $invalidData = $this->validClientData;
            $invalidData['email'] = $invalidEmail;
            
            $request = new ClientRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail for email: {$invalidEmail}");
            $this->assertArrayHasKey('email', $validator->errors()->toArray());
        }
    }

    /**
     * Test validation fails when string fields exceed maximum length.
     *
     * @return void
     */
    public function test_validation_fails_when_string_fields_exceed_max_length()
    {
        $fieldsWithMaxLength = [
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

        foreach ($fieldsWithMaxLength as $field => $maxLength) {
            $invalidData = $this->validClientData;
            $invalidData[$field] = str_repeat('a', $maxLength + 1);
            
            $request = new ClientRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), 
                "Validation should fail when {$field} exceeds {$maxLength} characters");
            $this->assertArrayHasKey($field, $validator->errors()->toArray());
        }
    }

    /**
     * Test validation passes with nullable fields empty.
     *
     * @return void
     */
    public function test_validation_passes_with_nullable_fields_empty()
    {
        $nullableFields = ['shortcut', 'ico', 'dic', 'description', 'is_default'];
        
        foreach ($nullableFields as $field) {
            $dataWithNullField = $this->validClientData;
            $dataWithNullField[$field] = null;
            
            $request = new ClientRequest();
            $validator = Validator::make($dataWithNullField, $request->rules(), $request->messages());

            $this->assertFalse($validator->fails(), 
                "Validation should pass when nullable field {$field} is null");
        }
    }

    /**
     * Test validation fails with invalid boolean values for is_default.
     *
     * @return void
     */
    public function test_validation_fails_with_invalid_boolean_values()
    {
        $invalidBooleanValues = ['invalid', 'yes', 'no', 2, -1, 'true_string'];
        
        foreach ($invalidBooleanValues as $invalidValue) {
            $invalidData = $this->validClientData;
            $invalidData['is_default'] = $invalidValue;
            
            $request = new ClientRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), 
                "Validation should fail for is_default value: " . json_encode($invalidValue));
            $this->assertArrayHasKey('is_default', $validator->errors()->toArray());
        }
    }

    /**
     * Test validation passes with valid boolean values for is_default.
     *
     * @return void
     */
    public function test_validation_passes_with_valid_boolean_values()
    {
        $validBooleanValues = [true, false, 1, 0, '1', '0'];
        
        foreach ($validBooleanValues as $validValue) {
            $validData = $this->validClientData;
            $validData['is_default'] = $validValue;
            
            $request = new ClientRequest();
            $validator = Validator::make($validData, $request->rules(), $request->messages());

            $this->assertFalse($validator->fails(), 
                "Validation should pass for is_default value: " . json_encode($validValue));
        }
    }

    /**
     * Test authorization with authenticated user via HTTP request.
     *
     * @return void
     */
    public function test_authorization_with_authenticated_user_via_http()
    {
        $response = $this->actingAs($this->user)
            ->post(route('frontend.client.store'), $this->validClientData);

        // Should not get 403 (unauthorized)
        $this->assertNotEquals(403, $response->getStatusCode());
        
        // Should either succeed or have validation errors, but not authorization errors
        $this->assertTrue(in_array($response->getStatusCode(), [200, 201, 302]));
    }

    /**
     * Test authorization fails with unauthenticated user via HTTP request.
     *
     * @return void
     */
    public function test_authorization_fails_with_unauthenticated_user_via_http()
    {
        $response = $this->post(route('frontend.client.store'), $this->validClientData);

        // Should redirect to login
        $response->assertRedirect(route('login'));
    }

    /**
     * Test custom error messages are displayed in validation.
     *
     * @return void
     */
    public function test_custom_error_messages_are_displayed()
    {
        $requiredFieldsWithMessages = [
            'name' => 'clients.validation.name_required',
            'email' => 'clients.validation.email_required',
            'street' => 'clients.validation.street_required',
            'city' => 'clients.validation.city_required',
            'zip' => 'clients.validation.zip_required',
            'country' => 'clients.validation.country_required',
        ];

        foreach ($requiredFieldsWithMessages as $field => $expectedMessageKey) {
            $invalidData = $this->validClientData;
            unset($invalidData[$field]);
            
            $request = new ClientRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails());
            $errors = $validator->errors();
            
            // Check that the error message contains the expected translation key content
            $fieldErrors = $errors->get($field);
            $this->assertNotEmpty($fieldErrors);
            
            // The actual message will be translated, but we can verify it's not the default Laravel message
            $errorMessage = $fieldErrors[0];
            $this->assertNotEquals("The {$field} field is required.", $errorMessage, 
                "Should use custom message, not default Laravel message");
        }
    }

    /**
     * Test validation with actual HTTP request and form data.
     *
     * @return void
     */
    public function test_validation_with_actual_http_request()
    {
        // Test with valid data
        $response = $this->actingAs($this->user)
            ->post(route('frontend.client.store'), $this->validClientData);

        $response->assertRedirect(); // Should redirect on success
        $response->assertSessionHasNoErrors();

        // Test with invalid data
        $invalidData = $this->validClientData;
        $invalidData['email'] = 'invalid-email';
        unset($invalidData['name']);

        $response = $this->actingAs($this->user)
            ->post(route('frontend.client.store'), $invalidData);

        $response->assertSessionHasErrors(['email', 'name']);
    }

    /**
     * Test edge cases with whitespace and special characters.
     *
     * @return void
     */
    public function test_edge_cases_with_whitespace_and_special_characters()
    {
        // Test with whitespace in name (should be valid)
        $dataWithWhitespace = $this->validClientData;
        $dataWithWhitespace['name'] = '  ' . $this->validClientData['name'] . '  ';

        $request = new ClientRequest();
        $validator = Validator::make($dataWithWhitespace, $request->rules(), $request->messages());

        // Name with whitespace should be valid
        $this->assertFalse($validator->fails());

        // Test with whitespace in email (should fail because email validation is strict)
        $dataWithEmailWhitespace = $this->validClientData;
        $dataWithEmailWhitespace['email'] = '  ' . $this->validClientData['email'] . '  ';

        $validator = Validator::make($dataWithEmailWhitespace, $request->rules(), $request->messages());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));

        // Test with special characters in text fields
        $dataWithSpecialChars = $this->validClientData;
        $dataWithSpecialChars['name'] = 'Company & Partners Ltd.';
        $dataWithSpecialChars['street'] = 'Wenceslas Square 123/45';

        $validator = Validator::make($dataWithSpecialChars, $request->rules(), $request->messages());
        $this->assertFalse($validator->fails());
    }

    /**
     * Test maximum boundary values for length constraints.
     *
     * @return void
     */
    public function test_maximum_boundary_values_for_length_constraints()
    {
        $fieldsWithExactMaxLength = [
            'name' => str_repeat('a', 255),
            'shortcut' => str_repeat('b', 50),
            'phone' => str_repeat('1', 255),
            'street' => str_repeat('c', 255),
            'city' => str_repeat('d', 255),
            'zip' => str_repeat('1', 20),
            'country' => str_repeat('e', 100),
            'ico' => str_repeat('1', 20),
            'dic' => str_repeat('1', 30),
        ];

        $validData = $this->validClientData;
        foreach ($fieldsWithExactMaxLength as $field => $value) {
            $validData[$field] = $value;
        }

        $request = new ClientRequest();
        $validator = Validator::make($validData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails(), 
            'Validation should pass with exact maximum length values');
    }
}
