<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\PasswordUpdateRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for PasswordUpdateRequest
 * 
 * Tests complete validation flow with HTTP context and database interactions
 * Tests password update validation scenarios, authorization, and validation with user authentication
 */
class PasswordUpdateRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected array $validPasswordData;
    protected string $currentPassword = 'current-password-123';

    /**
     * Set up the test environment.
     * Creates test user and valid password data for request testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test user with known password
        $this->user = User::factory()->create([
            'password' => Hash::make($this->currentPassword),
        ]);
        
        // Set up valid password data
        $this->setupValidPasswordData();
    }

    /**
     * Setup valid password data for testing
     */
    private function setupValidPasswordData(): void
    {
        $this->validPasswordData = [
            'current_password' => $this->currentPassword,
            'password' => 'new-secure-password-123',
            'password_confirmation' => 'new-secure-password-123',
        ];
    }

    #[Test]
    public function validation_passes_with_valid_data()
    {
        $this->actingAs($this->user);
        
        $request = new PasswordUpdateRequest();
        $validator = Validator::make($this->validPasswordData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_when_required_fields_missing()
    {
        $this->actingAs($this->user);
        
        $requiredFields = ['current_password', 'password', 'password_confirmation'];
        
        foreach ($requiredFields as $field) {
            $invalidData = $this->validPasswordData;
            unset($invalidData[$field]);

            $request = new PasswordUpdateRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail when {$field} is missing");
            $this->assertArrayHasKey($field, $validator->errors()->toArray(), "Should have error for missing {$field}");
        }
    }

    #[Test]
    public function validation_fails_with_wrong_current_password()
    {
        $this->actingAs($this->user);
        
        $invalidData = $this->validPasswordData;
        $invalidData['current_password'] = 'wrong-password';

        $request = new PasswordUpdateRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('current_password', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_short_new_password()
    {
        $this->actingAs($this->user);
        
        $invalidData = $this->validPasswordData;
        $invalidData['password'] = '123'; // Too short (min 8)
        $invalidData['password_confirmation'] = '123';

        $request = new PasswordUpdateRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_unconfirmed_password()
    {
        $this->actingAs($this->user);
        
        $invalidData = $this->validPasswordData;
        $invalidData['password'] = 'new-password-123';
        $invalidData['password_confirmation'] = 'different-password-123';

        $request = new PasswordUpdateRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_password_confirmation_missing()
    {
        $this->actingAs($this->user);
        
        $invalidData = $this->validPasswordData;
        $invalidData['password'] = 'new-password-123';
        unset($invalidData['password_confirmation']);

        $request = new PasswordUpdateRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password_confirmation', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_minimum_password_length()
    {
        $this->actingAs($this->user);
        
        $validData = $this->validPasswordData;
        $validData['password'] = '12345678'; // Exactly 8 characters (minimum)
        $validData['password_confirmation'] = '12345678';

        $request = new PasswordUpdateRequest();
        $validator = Validator::make($validData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_passes_with_complex_passwords()
    {
        $this->actingAs($this->user);
        
        $complexPasswords = [
            'Complex123!',
            'Very-Secure-Password-2024',
            'P@ssw0rd!#$%',
            'MySecurePassword123',
        ];
        
        foreach ($complexPasswords as $password) {
            $validData = $this->validPasswordData;
            $validData['password'] = $password;
            $validData['password_confirmation'] = $password;

            $request = new PasswordUpdateRequest();
            $validator = Validator::make($validData, $request->rules(), $request->messages());

            $this->assertFalse($validator->fails(), "Validation should pass for complex password: {$password}");
        }
    }

    #[Test]
    public function authorization_passes_when_authenticated()
    {
        $this->actingAs($this->user);

        $request = new PasswordUpdateRequest();
        
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function authorization_fails_when_not_authenticated()
    {
        $request = new PasswordUpdateRequest();
        
        $this->assertFalse($request->authorize());
    }

    #[Test]
    public function validation_fails_when_not_authenticated_for_current_password_check()
    {
        // Don't authenticate user
        $request = new PasswordUpdateRequest();
        $validator = Validator::make($this->validPasswordData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('current_password', $validator->errors()->toArray());
    }

    #[Test]
    public function request_has_custom_attributes()
    {
        $request = new PasswordUpdateRequest();
        $attributes = $request->attributes();

        $expectedKeys = ['current_password', 'password', 'password_confirmation'];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $attributes, "Should have custom attribute for {$key}");
        }
    }

    #[Test]
    public function request_has_custom_validation_messages()
    {
        $request = new PasswordUpdateRequest();
        $messages = $request->messages();

        $expectedKeys = [
            'current_password.required',
            'current_password.current_password',
            'password.required',
            'password.min',
            'password.confirmed',
            'password_confirmation.required',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $messages, "Should have custom message for {$key}");
        }
    }

    #[Test]
    public function validation_passes_with_same_current_and_new_password()
    {
        $this->actingAs($this->user);
        
        // User wants to keep the same password (edge case but should be allowed)
        $validData = [
            'current_password' => $this->currentPassword,
            'password' => $this->currentPassword,
            'password_confirmation' => $this->currentPassword,
        ];

        $request = new PasswordUpdateRequest();
        $validator = Validator::make($validData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_with_different_user_current_password()
    {
        // Create another user with different password
        $otherUser = User::factory()->create([
            'password' => Hash::make('other-user-password'),
        ]);
        
        $this->actingAs($this->user); // Login as first user
        
        $invalidData = $this->validPasswordData;
        $invalidData['current_password'] = 'other-user-password'; // Try to use other user's password

        $request = new PasswordUpdateRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('current_password', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_handles_empty_string_passwords()
    {
        $this->actingAs($this->user);
        
        $invalidData = [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ];

        $request = new PasswordUpdateRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        
        // Should fail for all three fields
        $this->assertArrayHasKey('current_password', $validator->errors()->toArray());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
        $this->assertArrayHasKey('password_confirmation', $validator->errors()->toArray());
    }
}
