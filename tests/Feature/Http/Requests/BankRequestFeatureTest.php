<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\BankRequest;
use App\Models\Bank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for BankRequest
 * 
 * Tests complete validation flow with HTTP context and database interactions
 * Tests bank validation scenarios, authorization, and validation with database constraints
 */
class BankRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected array $validBankData;

    /**
     * Set up the test environment.
     * Creates test user and valid bank data for request testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();
        
        // Set up valid bank data
        $this->setupValidBankData();
    }

    /**
     * Setup valid bank data for testing
     */
    private function setupValidBankData(): void
    {
        $this->validBankData = [
            'name' => $this->faker->company . ' Bank',
            'code' => $this->faker->numerify('####'),
            'swift' => $this->faker->lexify('????????'),
            'country' => $this->faker->country,
            'active' => true,
            'description' => $this->faker->sentence,
        ];
    }

    #[Test]
    public function validation_passes_with_valid_data()
    {
        $request = new BankRequest();
        $validator = Validator::make($this->validBankData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_when_required_fields_missing()
    {
        $requiredFields = ['name', 'code', 'country'];
        
        foreach ($requiredFields as $field) {
            $invalidData = $this->validBankData;
            unset($invalidData[$field]);

            $request = new BankRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail when {$field} is missing");
            $this->assertArrayHasKey($field, $validator->errors()->toArray(), "Should have error for missing {$field}");
        }
    }

    #[Test]
    public function validation_fails_with_short_name()
    {
        $invalidData = $this->validBankData;
        $invalidData['name'] = 'A'; // Too short (min 2)

        $request = new BankRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_long_name()
    {
        $invalidData = $this->validBankData;
        $invalidData['name'] = str_repeat('a', 256); // Too long (max 255)

        $request = new BankRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_duplicate_code()
    {
        // Create existing bank
        Bank::factory()->create(['code' => 'TESTCODE']);

        $invalidData = $this->validBankData;
        $invalidData['code'] = 'TESTCODE';

        $request = new BankRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('code', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_same_code_for_update()
    {
        // Create existing bank
        $existingBank = Bank::factory()->create(['code' => 'EXISTCODE']);

        $updateData = $this->validBankData;
        $updateData['code'] = 'EXISTCODE';
        $updateData['id'] = $existingBank->id;

        $request = new BankRequest();
        $request->merge(['id' => $existingBank->id]);
        $validator = Validator::make($updateData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_with_long_code()
    {
        $invalidData = $this->validBankData;
        $invalidData['code'] = str_repeat('a', 11); // Too long (max 10)

        $request = new BankRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('code', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_long_swift()
    {
        $invalidData = $this->validBankData;
        $invalidData['swift'] = str_repeat('a', 21); // Too long (max 20)

        $request = new BankRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('swift', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_long_country()
    {
        $invalidData = $this->validBankData;
        $invalidData['country'] = str_repeat('a', 101); // Too long (max 100)

        $request = new BankRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('country', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_long_description()
    {
        $invalidData = $this->validBankData;
        $invalidData['description'] = str_repeat('a', 1001); // Too long (max 1000)

        $request = new BankRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_minimal_required_data()
    {
        $minimalData = [
            'name' => 'Test Bank',
            'code' => '1234',
            'country' => 'Czech Republic',
        ];

        $request = new BankRequest();
        $validator = Validator::make($minimalData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_passes_with_nullable_fields_set_to_null()
    {
        $dataWithNulls = $this->validBankData;
        $dataWithNulls['swift'] = null;
        $dataWithNulls['description'] = null;

        $request = new BankRequest();
        $validator = Validator::make($dataWithNulls, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_with_invalid_boolean_active()
    {
        $invalidData = $this->validBankData;
        $invalidData['active'] = 'invalid-boolean';

        $request = new BankRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('active', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_boolean_values_for_active()
    {
        $booleanValues = [true, false, 1, 0, '1', '0'];
        
        foreach ($booleanValues as $value) {
            $validData = $this->validBankData;
            $validData['active'] = $value;
            $validData['code'] = 'CODE' . $value; // Make code unique

            $request = new BankRequest();
            $validator = Validator::make($validData, $request->rules(), $request->messages());

            $this->assertFalse($validator->fails(), "Validation should pass for active value: " . json_encode($value));
        }
    }

    #[Test]
    public function authorization_passes_when_authenticated()
    {
        $this->actingAs($this->user);

        $request = new BankRequest();
        
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function authorization_fails_when_not_authenticated()
    {
        $request = new BankRequest();
        
        $this->assertFalse($request->authorize());
    }

    #[Test]
    public function request_has_custom_attributes()
    {
        $request = new BankRequest();
        $attributes = $request->attributes();

        $expectedKeys = ['name', 'code', 'swift', 'country', 'active', 'description'];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $attributes, "Should have custom attribute for {$key}");
        }
    }

    #[Test]
    public function request_has_custom_validation_messages()
    {
        $request = new BankRequest();
        $messages = $request->messages();

        $expectedKeys = [
            'name.required',
            'name.min',
            'code.required',
            'code.unique',
            'country.required',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $messages, "Should have custom message for {$key}");
        }
    }

    #[Test]
    public function validation_fails_with_short_code()
    {
        $invalidData = $this->validBankData;
        $invalidData['code'] = 'A'; // Too short (min 2)

        $request = new BankRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('code', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_without_optional_fields()
    {
        $dataWithoutOptional = [
            'name' => 'Test Bank',
            'code' => '5678',
            'country' => 'Slovakia',
            'active' => false,
        ];

        $request = new BankRequest();
        $validator = Validator::make($dataWithoutOptional, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }
}
