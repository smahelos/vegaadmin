<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\SupplierRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SupplierRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private array $validSupplierData;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permissions and roles
        $this->createPermissionsAndRoles();
        
        // Create authenticated user
        $this->user = $this->createAuthenticatedUser();
        
        // Set up valid supplier data using faker
        $this->validSupplierData = [
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'street' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'zip' => $this->faker->postcode,
            'country' => $this->faker->country,
            'shortcut' => $this->faker->lexify('???'),
            'ico' => $this->faker->numerify('########'),
            'dic' => $this->faker->numerify('CZ########'),
            'description' => $this->faker->paragraph,
            'is_default' => false,
            
            // Bank account details
            'account_number' => $this->faker->bankAccountNumber,
            'bank_code' => $this->faker->numerify('####'),
            'iban' => 'CZ' . $this->faker->numerify('####################'),
            'swift' => $this->faker->lexify('????????'),
            'bank_name' => $this->faker->company . ' Bank',
        ];
    }

    /**
     * Create permissions and roles for testing.
     *
     * @return void
     */
    private function createPermissionsAndRoles(): void
    {
        // Create basic permissions
        Permission::create(['name' => 'suppliers.list', 'guard_name' => 'web']);
        Permission::create(['name' => 'suppliers.create', 'guard_name' => 'web']);
        Permission::create(['name' => 'suppliers.show', 'guard_name' => 'web']);
        Permission::create(['name' => 'suppliers.update', 'guard_name' => 'web']);
        Permission::create(['name' => 'suppliers.delete', 'guard_name' => 'web']);

        // Create role with permissions
        $role = Role::create(['name' => 'supplier_manager', 'guard_name' => 'web']);
        $role->givePermissionTo(['suppliers.list', 'suppliers.create', 'suppliers.show', 'suppliers.update', 'suppliers.delete']);
    }

    /**
     * Create authenticated user for testing.
     *
     * @return User
     */
    private function createAuthenticatedUser(): User
    {
        $user = User::factory()->create([
            'email' => $this->faker->unique()->safeEmail,
            'name' => $this->faker->name,
        ]);

        $user->assignRole('supplier_manager');
        
        return $user;
    }
    
    #[Test]
    public function validation_passes_with_valid_data()
    {
        $request = new SupplierRequest();
        $validator = Validator::make($this->validSupplierData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }
    
    #[Test]
    public function validation_fails_when_required_fields_missing()
    {
        $requiredFields = ['name', 'email', 'phone', 'street', 'city', 'zip', 'country'];
        
        foreach ($requiredFields as $field) {
            $data = $this->validSupplierData;
            unset($data[$field]);

            $request = new SupplierRequest();
            $validator = Validator::make($data, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail when {$field} is missing");
            $this->assertTrue($validator->errors()->has($field), "Should have error for missing {$field}");
        }
    }
    
    #[Test]
    public function validation_fails_with_invalid_email()
    {
        $invalidEmails = ['not-an-email', 'invalid@', '@invalid.com', 'invalid..email@test.com'];

        foreach ($invalidEmails as $invalidEmail) {
            $data = $this->validSupplierData;
            $data['email'] = $invalidEmail;

            $request = new SupplierRequest();
            $validator = Validator::make($data, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail for invalid email: {$invalidEmail}");
            $this->assertTrue($validator->errors()->has('email'));
        }
    }
    
    #[Test]
    public function validation_fails_when_string_fields_exceed_max_length()
    {
        $fieldsWithMaxLength = [
            'name' => 256,          // max:255
            'shortcut' => 51,       // max:50
            'phone' => 256,         // max:255
            'street' => 256,        // max:255
            'city' => 256,          // max:255
            'zip' => 21,            // max:20
            'country' => 101,       // max:100
            'ico' => 21,            // max:20
            'dic' => 31,            // max:30
            'account_number' => 51, // max:50
            'bank_code' => 11,      // max:10
            'iban' => 51,           // max:50
            'swift' => 21,          // max:20
            'bank_name' => 256,     // max:255
        ];

        foreach ($fieldsWithMaxLength as $field => $length) {
            $data = $this->validSupplierData;
            $data[$field] = str_repeat('a', $length);

            $request = new SupplierRequest();
            $validator = Validator::make($data, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail when {$field} exceeds max length");
            $this->assertTrue($validator->errors()->has($field));
        }
    }
    
    #[Test]
    public function validation_passes_with_nullable_fields_empty()
    {
        // Note: bank_code and swift are conditional, not truly nullable
        $nullableFields = ['shortcut', 'ico', 'dic', 'description', 'is_default', 
                          'account_number', 'iban', 'bank_name'];

        foreach ($nullableFields as $field) {
            $data = $this->validSupplierData;
            $data[$field] = null;
            
            // For conditional fields, also null the related field
            if ($field === 'account_number') {
                $data['bank_code'] = null;
            }
            if ($field === 'iban') {
                $data['swift'] = null;
            }

            $request = new SupplierRequest();
            $validator = Validator::make($data, $request->rules(), $request->messages());

            $this->assertFalse($validator->fails(), "Validation should pass when nullable field {$field} is null");
        }
    }
    
    #[Test]
    public function validation_fails_with_invalid_boolean_values()
    {
        $invalidBooleans = ['invalid', 'yes', 'no', 2, -1];

        foreach ($invalidBooleans as $invalidBoolean) {
            $data = $this->validSupplierData;
            $data['is_default'] = $invalidBoolean;

            $request = new SupplierRequest();
            $validator = Validator::make($data, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail for invalid boolean: " . json_encode($invalidBoolean));
            $this->assertTrue($validator->errors()->has('is_default'));
        }
    }
    
    #[Test]
    public function validation_passes_with_valid_boolean_values()
    {
        $validBooleans = [true, false, 1, 0, '1', '0'];

        foreach ($validBooleans as $validBoolean) {
            $data = $this->validSupplierData;
            $data['is_default'] = $validBoolean;

            $request = new SupplierRequest();
            $validator = Validator::make($data, $request->rules(), $request->messages());

            $this->assertFalse($validator->fails(), "Validation should pass for valid boolean: " . json_encode($validBoolean));
        }
    }
    
    #[Test]
    public function conditional_bank_validation_rules()
    {
        // Test bank_code is required when account_number is provided
        $data = $this->validSupplierData;
        $data['account_number'] = '123456789';
        $data['bank_code'] = null;

        $request = new SupplierRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('bank_code'));

        // Test swift is required when iban is provided
        $data = $this->validSupplierData;
        $data['iban'] = 'CZ1234567890123456789';
        $data['swift'] = null;

        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('swift'));
    }
    
    #[Test]
    public function authorization_with_authenticated_user_via_http()
    {
        $this->actingAs($this->user);

        $request = Request::create('/suppliers', 'POST', $this->validSupplierData);
        $supplierRequest = SupplierRequest::createFrom($request);

        $this->assertTrue($supplierRequest->authorize());
    }
    
    #[Test]
    public function authorization_fails_with_unauthenticated_user_via_http()
    {
        // Don't authenticate user
        $request = Request::create('/suppliers', 'POST', $this->validSupplierData);
        $supplierRequest = SupplierRequest::createFrom($request);

        $this->assertFalse($supplierRequest->authorize());
    }
    
    #[Test]
    public function custom_error_messages_are_displayed()
    {
        // Test data that will trigger specific required validation errors
        $data = [
            'name' => '',              // required
            'email' => '',             // required
            'phone' => '',             // required
            'street' => '',            // required
            'city' => '',              // required
            'zip' => '',               // required
            'country' => '',           // required
        ];

        $request = new SupplierRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();

        // Check specific required messages
        $expectedRequiredMessages = [
            'name' => __('suppliers.validation.name_required'),
            'email' => __('suppliers.validation.email_required'),
            'phone' => __('suppliers.validation.phone_required'),
            'street' => __('suppliers.validation.street_required'),
            'city' => __('suppliers.validation.city_required'),
            'zip' => __('suppliers.validation.zip_required'),
            'country' => __('suppliers.validation.country_required'),
        ];

        foreach ($expectedRequiredMessages as $field => $expectedMessage) {
            if ($errors->has($field)) {
                $actualMessages = $errors->get($field);
                $this->assertContains($expectedMessage, $actualMessages, 
                    "Custom required message for {$field} should be displayed");
            }
        }

        // Test email format validation separately
        $emailData = $this->validSupplierData;
        $emailData['email'] = 'invalid-email';

        $validator = Validator::make($emailData, $request->rules(), $request->messages());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));
        
        $emailErrors = $validator->errors()->get('email');
        $expectedEmailMessage = __('suppliers.validation.email_valid');
        $this->assertContains($expectedEmailMessage, $emailErrors, 
            'Custom email format message should be displayed');
    }
    
    #[Test]
    public function validation_with_actual_http_request()
    {
        $this->actingAs($this->user);

        $response = $this->post('/suppliers', $this->validSupplierData);

        // We expect either success or redirect (depending on controller implementation)
        // but not validation errors
        $this->assertNotEquals(422, $response->getStatusCode(), 
            'Request should not fail with validation errors');
    }
    
    #[Test]
    public function edge_cases_with_whitespace_and_special_characters()
    {
        // Test with whitespace in name (should be valid)
        $dataWithWhitespace = $this->validSupplierData;
        $dataWithWhitespace['name'] = '  ' . $this->validSupplierData['name'] . '  ';

        $request = new SupplierRequest();
        $validator = Validator::make($dataWithWhitespace, $request->rules(), $request->messages());

        // Name with whitespace should be valid
        $this->assertFalse($validator->fails());

        // Test with whitespace in email (should fail because email validation is strict)
        $dataWithEmailWhitespace = $this->validSupplierData;
        $dataWithEmailWhitespace['email'] = '  ' . $this->validSupplierData['email'] . '  ';

        $validator = Validator::make($dataWithEmailWhitespace, $request->rules(), $request->messages());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));

        // Test with special characters in text fields
        $dataWithSpecialChars = $this->validSupplierData;
        $dataWithSpecialChars['name'] = 'Company & Partners Ltd.';
        $dataWithSpecialChars['street'] = 'Wenceslas Square 123/45';
        $dataWithSpecialChars['bank_name'] = 'Česká spořitelna, a.s.';

        $validator = Validator::make($dataWithSpecialChars, $request->rules(), $request->messages());
        $this->assertFalse($validator->fails());
    }
    
    #[Test]
    public function maximum_boundary_values_for_length_constraints()
    {
        $fieldsWithExactMaxLength = [
            'name' => 255,
            'shortcut' => 50,
            'phone' => 255,
            'street' => 255,
            'city' => 255,
            'zip' => 20,
            'country' => 100,
            'ico' => 20,
            'dic' => 30,
            'account_number' => 50,
            'bank_code' => 10,
            'iban' => 50,
            'swift' => 20,
            'bank_name' => 255,
        ];

        foreach ($fieldsWithExactMaxLength as $field => $maxLength) {
            $data = $this->validSupplierData;
            $data[$field] = str_repeat('a', $maxLength);

            $request = new SupplierRequest();
            $validator = Validator::make($data, $request->rules(), $request->messages());

            $this->assertFalse($validator->fails(), 
                "Validation should pass when {$field} is exactly at max length ({$maxLength})");
        }
    }
    
    #[Test]
    public function bank_account_validation_combinations()
    {
        // Test 1: Both account_number and bank_code provided (should pass)
        $data = $this->validSupplierData;
        $data['account_number'] = '123456789';
        $data['bank_code'] = '0800';
        $data['iban'] = null;
        $data['swift'] = null;

        $request = new SupplierRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        $this->assertFalse($validator->fails(), 'Should pass with account_number and bank_code');

        // Test 2: Both iban and swift provided (should pass)
        $data = $this->validSupplierData;
        $data['account_number'] = null;
        $data['bank_code'] = null;
        $data['iban'] = 'CZ1234567890123456789';
        $data['swift'] = 'GIBACZPX';

        $validator = Validator::make($data, $request->rules(), $request->messages());
        $this->assertFalse($validator->fails(), 'Should pass with iban and swift');

        // Test 3: All bank fields null (should pass)
        $data = $this->validSupplierData;
        $data['account_number'] = null;
        $data['bank_code'] = null;
        $data['iban'] = null;
        $data['swift'] = null;
        $data['bank_name'] = null;

        $validator = Validator::make($data, $request->rules(), $request->messages());
        $this->assertFalse($validator->fails(), 'Should pass with all bank fields null');
    }
}
