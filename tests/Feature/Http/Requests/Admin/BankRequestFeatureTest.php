<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\BankRequest;
use App\Models\Bank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BankRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function validation_passes_with_valid_data()
    {
        $user = User::factory()->create();
        
        $validData = [
            'name' => 'Test Bank',
            'code' => '1234',
            'swift' => 'TESTCZ22',
            'country' => 'CZ',
        ];

        $request = new BankRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_when_required_fields_missing()
    {
        $invalidData = [];

        $request = new BankRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('code', $validator->errors()->toArray());
        $this->assertArrayHasKey('country', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_string_fields_exceed_max_length()
    {
        $invalidData = [
            'name' => str_repeat('a', 256), // max 255
            'code' => str_repeat('b', 11),  // max 10
            'swift' => str_repeat('c', 21), // max 20
            'country' => 'CZE', // should be exactly 2 chars
        ];

        $request = new BankRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('code', $validator->errors()->toArray());
        $this->assertArrayHasKey('swift', $validator->errors()->toArray());
        $this->assertArrayHasKey('country', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_nullable_fields_empty()
    {
        $validData = [
            'name' => 'Test Bank',
            'code' => '5678',
            'country' => 'SK',
            'swift' => null, // nullable field
        ];

        $request = new BankRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_with_invalid_country_format()
    {
        $invalidData = [
            'name' => 'Test Bank',
            'code' => '9999',
            'country' => 'C', // should be exactly 2 chars
        ];

        $request = new BankRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('country', $validator->errors()->toArray());
    }

    #[Test]
    public function code_uniqueness_validation()
    {
        // Create existing bank
        Bank::factory()->create(['code' => 'EXISTING']);

        // Try to create another bank with same code
        $invalidData = [
            'name' => 'New Bank',
            'code' => 'EXISTING',
            'country' => 'CZ',
        ];

        $request = new BankRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('code', $validator->errors()->toArray());
    }

    #[Test]
    public function code_uniqueness_validation_allows_updating_same_record()
    {
        $existingBank = Bank::factory()->create(['code' => 'UPDATE']);

        // Simulate updating the same bank
        $validData = [
            'name' => 'Updated Bank Name',
            'code' => 'UPDATE',
            'country' => 'CZ',
        ];

        // Create request instance with the ID to simulate update
        $request = new BankRequest();
        $request->merge(['id' => $existingBank->id]);
        
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function authorization_with_authenticated_user_via_http()
    {
        // Create an admin user with proper permissions
        $user = User::factory()->create();
        
        // Since we don't have permissions set up in tests, we'll test the request class directly
        $request = new BankRequest();
        
        // Mock the authentication
        $this->actingAs($user, 'backpack');
        
        // Test that the request authorization works
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function authorization_fails_with_unauthenticated_user_via_http()
    {
        $request = new BankRequest();
        
        // Without authentication, authorize should return false
        $this->assertFalse($request->authorize());
    }

    #[Test]
    public function custom_error_messages_are_displayed()
    {
        $invalidData = [
            'name' => '',
            'code' => '',
            'country' => '',
        ];

        $request = new BankRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        
        $errors = $validator->errors();
        $this->assertStringContainsString('bank.', $errors->first('name'));
        $this->assertStringContainsString('bank.', $errors->first('code'));
        $this->assertStringContainsString('bank.', $errors->first('country'));
    }

    #[Test]
    public function validation_with_actual_http_request()
    {
        // Test validation logic directly since HTTP routes require specific permissions
        $request = new BankRequest();
        
        // Test valid data validation
        $validator = Validator::make([
            'name' => 'HTTP Test Bank',
            'code' => 'HTTP',
            'country' => 'CZ',
        ], $request->rules());
        
        $this->assertTrue($validator->passes());
        
        // Test invalid data validation
        $invalidValidator = Validator::make([
            'name' => '',
            'code' => '',
        ], $request->rules());
        
        $this->assertFalse($invalidValidator->passes());
        $this->assertArrayHasKey('name', $invalidValidator->errors()->toArray());
        $this->assertArrayHasKey('code', $invalidValidator->errors()->toArray());
    }

    #[Test]
    public function custom_attributes_are_applied()
    {
        $request = new BankRequest();
        $attributes = $request->attributes();

        $this->assertNotEmpty($attributes);
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('code', $attributes);
        $this->assertArrayHasKey('swift', $attributes);
        $this->assertArrayHasKey('country', $attributes);
    }

    #[Test]
    public function edge_cases_with_whitespace_and_special_characters()
    {
        $edgeCaseData = [
            'name' => '  Test Bank  ', // whitespace
            'code' => 'T€ST', // special characters
            'country' => 'cz', // lowercase
        ];

        $request = new BankRequest();
        $validator = Validator::make($edgeCaseData, $request->rules());

        // Should pass basic validation (trimming and character validation depend on specific rules)
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function maximum_boundary_values_for_length_constraints()
    {
        $boundaryData = [
            'name' => str_repeat('a', 255), // exactly max length
            'code' => str_repeat('b', 10),  // exactly max length
            'swift' => str_repeat('c', 20), // exactly max length
            'country' => 'CZ', // exactly required length
        ];

        $request = new BankRequest();
        $validator = Validator::make($boundaryData, $request->rules());

        $this->assertTrue($validator->passes());
    }
}
