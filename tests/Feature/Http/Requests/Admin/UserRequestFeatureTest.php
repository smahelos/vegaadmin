<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

/**
 * Feature test for UserRequest class.
 * Tests validation rules, authorization logic, and custom attributes/messages.
 */
class UserRequestFeatureTest extends TestCase
{
    use RefreshDatabase, CreatesAdminTestEnvironment;

    protected User $adminUser;
    protected User $regularUser;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set up admin test environment with roles and permissions
        $this->setUpAdminTestEnvironment();
    }

    /**
     * Test successful validation with valid create data.
     */
    #[Test]
    public function validation_passes_with_valid_create_data(): void
    {
        $request = new UserRequest();
        $request->setMethod('POST'); // Simulate create operation

        $validData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'street' => '123 Main St',
            'city' => 'Prague',
            'zip' => '12000',
            'country' => 'Czech Republic',
            // Phone must be string to satisfy string + regex rule
            'phone' => '123456789',
            'ico' => '12345678',
            'dic' => 'CZ12345678',
        ];

        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test successful validation with valid update data (no password).
     */
    #[Test]
    public function validation_passes_with_valid_update_data_no_password(): void
    {
        $existingUser = User::factory()->create();

        $request = new UserRequest();
        $request->setMethod('PUT'); // Simulate update operation

        $validData = [
            'name' => 'Jane Doe Updated',
            'email' => 'jane.updated@example.com',
            'street' => '456 Updated St',
            'city' => 'Brno',
            'zip' => '60200',
            'country' => 'Czech Republic',
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
        $request = new UserRequest();
        $request->setMethod('POST'); // Simulate create operation

        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
        $this->assertArrayHasKey('password_confirmation', $validator->errors()->toArray());
        $this->assertArrayHasKey('street', $validator->errors()->toArray());
        $this->assertArrayHasKey('city', $validator->errors()->toArray());
        $this->assertArrayHasKey('zip', $validator->errors()->toArray());
        $this->assertArrayHasKey('country', $validator->errors()->toArray());
    }

    /**
     * Test validation fails when password is missing on create.
     */
    #[Test]
    public function validation_fails_when_password_missing_on_create(): void
    {
        $request = new UserRequest();
        $request->setMethod('POST'); // Simulate create operation

        $invalidData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'street' => '123 Main St',
            'city' => 'Prague',
            'zip' => '12000',
            'country' => 'Czech Republic',
            // Missing password and password_confirmation
        ];

        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
        $this->assertArrayHasKey('password_confirmation', $validator->errors()->toArray());
    }

    /**
     * Test validation fails when passwords don't match.
     */
    #[Test]
    public function validation_fails_when_passwords_dont_match(): void
    {
        $request = new UserRequest();
        $request->setMethod('POST'); // Simulate create operation

        $invalidData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
            'street' => '123 Main St',
            'city' => 'Prague',
            'zip' => '12000',
            'country' => 'Czech Republic',
        ];

        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /**
     * Test validation fails when password is too short.
     */
    #[Test]
    public function validation_fails_when_password_too_short(): void
    {
        $request = new UserRequest();
        $request->setMethod('POST'); // Simulate create operation

        $invalidData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '123', // Too short
            'password_confirmation' => '123',
            'street' => '123 Main St',
            'city' => 'Prague',
            'zip' => '12000',
            'country' => 'Czech Republic',
        ];

        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /**
     * Test validation fails when email format is invalid.
     */
    #[Test]
    public function validation_fails_when_email_invalid(): void
    {
        $request = new UserRequest();
        $request->setMethod('POST'); // Simulate create operation

        $invalidData = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'street' => '123 Main St',
            'city' => 'Prague',
            'zip' => '12000',
            'country' => 'Czech Republic',
        ];

        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /**
     * Test validation handles optional password on update.
     */
    #[Test]
    public function validation_handles_optional_password_on_update(): void
    {
        $request = new UserRequest();
        $request->setMethod('PUT'); // Simulate update operation

        // Update with password
        $updateDataWithPassword = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'street' => '456 Updated St',
            'city' => 'Brno',
            'zip' => '60200',
            'country' => 'Czech Republic',
        ];

        $validator = Validator::make($updateDataWithPassword, $request->rules());
        $this->assertTrue($validator->passes());

        // Update without password
        $updateDataWithoutPassword = [
            'name' => 'Updated Name Again',
            'email' => 'updated2@example.com',
            'street' => '789 Another St',
            'city' => 'Ostrava',
            'zip' => '70200',
            'country' => 'Czech Republic',
        ];

        $validator = Validator::make($updateDataWithoutPassword, $request->rules());
        $this->assertTrue($validator->passes());
    }

    /**
     * Test validation fails when phone is not numeric.
     */
    #[Test]
    public function validation_fails_when_phone_not_numeric(): void
    {
        $request = new UserRequest();
        $request->setMethod('POST'); // Simulate create operation

        $invalidData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'street' => '123 Main St',
            'city' => 'Prague',
            'zip' => '12000',
            'country' => 'Czech Republic',
            'phone' => 'not-a-number',
        ];

        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    /**
     * Test validation handles nullable fields correctly.
     */
    #[Test]
    public function validation_handles_nullable_fields(): void
    {
        $request = new UserRequest();
        $request->setMethod('POST'); // Simulate create operation

        $minimalData = [
            'name' => 'Minimal User',
            'email' => 'minimal@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'street' => '123 Main St',
            'city' => 'Prague',
            'zip' => '12000',
            'country' => 'Czech Republic',
            // phone, ico, dic are nullable
        ];

        $validator = Validator::make($minimalData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test authorization logic.
     */
    #[Test]
    public function authorization_passes_when_authenticated(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $request = new UserRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * Test authorization fails when user is not authenticated.
     */
    #[Test]
    public function authorization_fails_when_not_authenticated(): void
    {
        $request = new UserRequest();
        $this->assertFalse($request->authorize());
    }

    /**
     * Test custom attributes are correctly defined.
     */
    #[Test]
    public function custom_attributes_are_defined(): void
    {
        $request = new UserRequest();
        $attributes = $request->attributes();

        $expectedAttributes = [
            'name' => __('users.fields.name'),
            'email' => __('users.fields.email'),
            'password' => __('users.fields.password'),
            'password_confirmation' => __('users.fields.password_confirmation'),
            'street' => __('users.fields.street'),
            'city' => __('users.fields.city'),
            'zip' => __('users.fields.zip'),
            'country' => __('users.fields.country'),
            'phone' => __('users.fields.phone'),
            'ico' => __('users.fields.ico'),
            'dic' => __('users.fields.dic'),
        ];

        $this->assertEquals($expectedAttributes, $attributes);
    }

    /**
     * Test custom messages are correctly defined.
     */
    #[Test]
    public function custom_messages_are_defined(): void
    {
        $request = new UserRequest();
        $messages = $request->messages();

        $expectedMessages = [
            'name.required' => __('users.validation.name_required'),
            'email.required' => __('users.validation.email_required'),
            'email.email' => __('users.validation.email_email'),
            'email.unique' => __('users.validation.email_unique'),
            'password.required' => __('users.validation.password_required'),
            'password.min' => __('users.validation.password_min'),
            'password.confirmed' => __('users.validation.password_confirmed'),
            'password_confirmation.required' => __('users.validation.password_confirmation_required'),
            'password_confirmation.required_with' => __('users.validation.password_confirmation_required'),
            'street.required' => __('users.validation.street_required'),
            'city.required' => __('users.validation.city_required'),
            'zip.required' => __('users.validation.zip_required'),
            'country.required' => __('users.validation.country_required'),
            'phone.regex' => __('users.validation.phone_format'),
        ];

        $this->assertEquals($expectedMessages, $messages);
    }

    /**
     * Test isCreateOperation method works correctly.
     */
    #[Test]
    public function is_create_operation_method(): void
    {
        $request = new UserRequest();

        // Test POST request (create operation)
        $request->setMethod('POST');
        $this->assertTrue($request->isCreateOperation());

        // Test PUT request (update operation)
        $request->setMethod('PUT');
        $this->assertFalse($request->isCreateOperation());
    }

    /**
     * Test field max length validations.
     */
    #[Test]
    public function field_max_length_validations(): void
    {
        $request = new UserRequest();
        $request->setMethod('POST'); // Simulate create operation

        $invalidData = [
            'name' => str_repeat('a', 256), // Max 255
            'email' => str_repeat('a', 250) . '@example.com', // Will be too long
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'street' => str_repeat('a', 256), // Max 255
            'city' => str_repeat('a', 256), // Max 255
            'zip' => str_repeat('a', 21), // Max 20
            'country' => str_repeat('a', 101), // Max 100
            'ico' => str_repeat('a', 21), // Max 20
            'dic' => str_repeat('a', 31), // Max 30
        ];

        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('street', $validator->errors()->toArray());
        $this->assertArrayHasKey('city', $validator->errors()->toArray());
        $this->assertArrayHasKey('zip', $validator->errors()->toArray());
        $this->assertArrayHasKey('country', $validator->errors()->toArray());
        $this->assertArrayHasKey('ico', $validator->errors()->toArray());
        $this->assertArrayHasKey('dic', $validator->errors()->toArray());
    }

    /**
     * Test validation with custom messages.
     */
    #[Test]
    public function validation_with_custom_messages(): void
    {
        $request = new UserRequest();
        $request->setMethod('POST'); // Simulate create operation

        $invalidData = [
            'name' => '',
            'email' => '',
            'password' => '',
        ];

        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());

        $errors = $validator->errors();

        // Test that custom messages are defined (not empty)
        $this->assertNotEmpty($errors->first('name'));
        $this->assertNotEmpty($errors->first('email'));
        $this->assertNotEmpty($errors->first('password'));

        // Test that custom messages method returns expected structure
        $messages = $request->messages();
        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('email.required', $messages);
        $this->assertArrayHasKey('password.required', $messages);
    }

    /**
     * Test phone regex accepts various valid formats.
     */
    #[Test]
    public function validation_accepts_valid_phone_formats(): void
    {
        $request = new UserRequest();
        $request->setMethod('POST');

        $validFormats = [
            '+420 123 456 789',
            '+1-202-555-0178',
            '(202) 555 0178',
            '00420 123456789', // Still digits only, allowed
            '123-456-789'
        ];

        foreach ($validFormats as $phone) {
            $data = [
                'name' => 'Phone User',
                'email' => 'phone_' . uniqid() . '@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'street' => 'Street 1',
                'city' => 'City',
                'zip' => '10000',
                'country' => 'Czech Republic',
                'phone' => $phone,
            ];
            $validator = Validator::make($data, $request->rules());
            $this->assertTrue($validator->passes(), "Phone format should be valid: {$phone}");
        }
    }

    /**
     * Test phone regex rejects invalid formats (too short / letters / symbols).
     */
    #[Test]
    public function validation_fails_for_invalid_phone_formats(): void
    {
        $request = new UserRequest();
        $request->setMethod('POST');

        $invalidFormats = [
            '12',            // too short (<3)
            'abc123',        // letters
            '123**456',      // invalid symbols
            '+',             // only plus
            '()'             // only parentheses
        ];

        foreach ($invalidFormats as $phone) {
            $data = [
                'name' => 'Bad Phone User',
                'email' => 'badphone_' . uniqid() . '@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'street' => 'Street 1',
                'city' => 'City',
                'zip' => '10000',
                'country' => 'Czech Republic',
                'phone' => $phone,
            ];
            $validator = Validator::make($data, $request->rules());
            $this->assertFalse($validator->passes(), "Phone format should be invalid: {$phone}");
            $this->assertArrayHasKey('phone', $validator->errors()->toArray());
        }
    }

    /**
     * Test email uniqueness on create.
     */
    #[Test]
    public function validation_fails_when_email_not_unique_on_create(): void
    {
        $existing = User::factory()->create(['email' => 'duplicate@example.com']);

        $request = new UserRequest();
        $request->setMethod('POST');

        $data = [
            'name' => 'Dup User',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'street' => 'Street 1',
            'city' => 'City',
            'zip' => '10000',
            'country' => 'Czech Republic',
        ];

        $validator = Validator::make($data, $request->rules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /**
     * Test unique email rule ignores current record on update.
     */
    #[Test]
    public function validation_passes_when_email_unchanged_on_update(): void
    {
        $user = User::factory()->create(['email' => 'keep@example.com']);

        $request = new UserRequest();
        $request->setMethod('PUT');
        // Simulate route/model id presence
        $request->merge(['id' => $user->id]);

        $data = [
            'name' => 'Keep Email',
            'email' => 'keep@example.com',
            'street' => 'Street 1',
            'city' => 'City',
            'zip' => '10000',
            'country' => 'Czech Republic',
        ];

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->passes());
    }

    /**
     * Test password confirmation required when password provided on update.
     */
    #[Test]
    public function validation_fails_when_password_confirmation_missing_on_update(): void
    {
        $user = User::factory()->create();
        $request = new UserRequest();
        $request->setMethod('PUT');
        $request->merge(['id' => $user->id]);

        $data = [
            'name' => 'Pwd Update',
            'email' => 'pwdupdate_' . uniqid() . '@example.com',
            'password' => 'newpassword123',
            // missing password_confirmation
            'street' => 'Street 1',
            'city' => 'City',
            'zip' => '10000',
            'country' => 'Czech Republic',
        ];

        $validator = Validator::make($data, $request->rules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('password_confirmation', $validator->errors()->toArray());
    }

    /**
     * Test password confirmation required when password provided on create.
     */
    #[Test]
    public function validation_fails_when_password_confirmation_missing_on_create(): void
    {
        $request = new UserRequest();
        $request->setMethod('POST');

        $data = [
            'name' => 'Pwd Create',
            'email' => 'pwdcreate_' . uniqid() . '@example.com',
            'password' => 'password123',
            // missing password_confirmation
            'street' => 'Street 1',
            'city' => 'City',
            'zip' => '10000',
            'country' => 'Czech Republic',
        ];

        $validator = Validator::make($data, $request->rules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('password_confirmation', $validator->errors()->toArray());
    }
}
