<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\SupplierRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature test for SupplierRequest class.
 * Tests validation rules, authorization logic, and custom attributes/messages.
 */
class SupplierRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $supplierUser;
    private SupplierRequest $request;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->supplierUser = User::factory()->create();
        $this->request = new SupplierRequest();
    }

    /**
     * Test successful validation with valid data.
     */
    #[Test]
    public function validation_passes_with_valid_data(): void
    {
        $validData = [
            'name' => 'ABC Suppliers Ltd.',
            'shortcut' => 'ABC',
            'email' => 'contact@abc-suppliers.com',
            'phone' => '+420 123 456 789',
            'street' => '123 Business Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'ico' => '12345678',
            'dic' => 'CZ12345678',
            'description' => 'Major supplier of office equipment',
            'user_id' => $this->supplierUser->id,
            'account_number' => '123456789',
            'bank_code' => '0100',
            'iban' => 'CZ6508000000192000145399',
            'swift' => 'GIBACZPX',
            'bank_name' => 'Česká spořitelna',
        ];

        $validator = Validator::make($validData, $this->request->rules());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    /**
     * Test validation fails when required fields are missing.
     */
    #[Test]
    public function validation_fails_with_missing_required_fields(): void
    {
        $emptyData = [];

        $validator = Validator::make($emptyData, $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
        $this->assertArrayHasKey('street', $validator->errors()->toArray());
        $this->assertArrayHasKey('city', $validator->errors()->toArray());
        $this->assertArrayHasKey('zip', $validator->errors()->toArray());
        $this->assertArrayHasKey('country', $validator->errors()->toArray());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
    }

    /**
     * Test validation passes with minimal required data.
     */
    #[Test]
    public function validation_passes_with_minimal_required_data(): void
    {
        $minimalData = [
            'name' => 'Minimal Supplier',
            'email' => 'minimal@supplier.com',
            'phone' => '+420 123 456 789',
            'street' => '123 Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'user_id' => $this->supplierUser->id,
        ];

        $validator = Validator::make($minimalData, $this->request->rules());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    /**
     * Test validation fails when name exceeds maximum length.
     */
    #[Test]
    public function validation_fails_when_name_too_long(): void
    {
        $invalidData = [
            'name' => str_repeat('a', 256), // Exceeds max length of 255
            'email' => 'supplier@example.com',
            'phone' => '+420 123 456 789',
            'street' => '123 Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'user_id' => $this->supplierUser->id,
        ];

        $validator = Validator::make($invalidData, $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /**
     * Test validation fails when email format is invalid.
     */
    #[Test]
    public function validation_fails_when_email_invalid(): void
    {
        $invalidData = [
            'name' => 'Valid Supplier',
            'email' => 'invalid-email-format',
            'phone' => '+420 123 456 789',
            'street' => '123 Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'user_id' => $this->supplierUser->id,
        ];

        $validator = Validator::make($invalidData, $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /**
     * Test validation fails when user_id does not exist.
     */
    #[Test]
    public function validation_fails_with_invalid_user_id(): void
    {
        $invalidData = [
            'name' => 'Valid Supplier',
            'email' => 'supplier@example.com',
            'phone' => '+420 123 456 789',
            'street' => '123 Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'user_id' => 99999, // Non-existent user
        ];

        $validator = Validator::make($invalidData, $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
    }

    /**
     * Test bank_code is required when account_number is provided.
     */
    #[Test]
    public function bank_code_required_with_account_number(): void
    {
        $invalidData = [
            'name' => 'Valid Supplier',
            'email' => 'supplier@example.com',
            'phone' => '+420 123 456 789',
            'street' => '123 Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'user_id' => $this->supplierUser->id,
            'account_number' => '123456789',
            // Missing bank_code when account_number is provided
        ];

        $validator = Validator::make($invalidData, $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('bank_code', $validator->errors()->toArray());
    }

    /**
     * Test swift is required when iban is provided.
     */
    #[Test]
    public function swift_required_with_iban(): void
    {
        $invalidData = [
            'name' => 'Valid Supplier',
            'email' => 'supplier@example.com',
            'phone' => '+420 123 456 789',
            'street' => '123 Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'user_id' => $this->supplierUser->id,
            'iban' => 'CZ6508000000192000145399',
            // Missing swift when iban is provided
        ];

        $validator = Validator::make($invalidData, $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('swift', $validator->errors()->toArray());
    }

    /**
     * Test bank account fields accept valid data.
     */
    #[Test]
    public function bank_account_fields_accept_valid_data(): void
    {
        $validData = [
            'name' => 'Supplier with Bank',
            'email' => 'bank@supplier.com',
            'phone' => '+420 123 456 789',
            'street' => '123 Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'user_id' => $this->supplierUser->id,
            'account_number' => '123456789',
            'bank_code' => '0100',
        ];

        $validator = Validator::make($validData, $this->request->rules());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    /**
     * Test IBAN and SWIFT fields accept valid data.
     */
    #[Test]
    public function iban_swift_fields_accept_valid_data(): void
    {
        $validData = [
            'name' => 'Supplier with IBAN',
            'email' => 'iban@supplier.com',
            'phone' => '+420 123 456 789',
            'street' => '123 Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'user_id' => $this->supplierUser->id,
            'iban' => 'CZ6508000000192000145399',
            'swift' => 'GIBACZPX',
        ];

        $validator = Validator::make($validData, $this->request->rules());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    /**
     * Test field length validations.
     */
    #[Test]
    public function field_length_validations(): void
    {
        $invalidData = [
            'name' => str_repeat('a', 256), // Max 255
            'shortcut' => str_repeat('a', 51), // Max 50
            'email' => 'valid@example.com',
            'phone' => str_repeat('a', 256), // Max 255
            'street' => str_repeat('a', 256), // Max 255
            'city' => str_repeat('a', 256), // Max 255
            'zip' => str_repeat('a', 21), // Max 20
            'country' => str_repeat('a', 101), // Max 100
            'ico' => str_repeat('a', 21), // Max 20
            'dic' => str_repeat('a', 31), // Max 30
            'user_id' => $this->supplierUser->id,
            'account_number' => str_repeat('a', 51), // Max 50
            'bank_code' => str_repeat('a', 11), // Max 10
            'iban' => str_repeat('a', 51), // Max 50
            'swift' => str_repeat('a', 21), // Max 20
            'bank_name' => str_repeat('a', 256), // Max 255
        ];

        $validator = Validator::make($invalidData, $this->request->rules());

        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('shortcut', $errors);
        $this->assertArrayHasKey('phone', $errors);
        $this->assertArrayHasKey('street', $errors);
        $this->assertArrayHasKey('city', $errors);
        $this->assertArrayHasKey('zip', $errors);
        $this->assertArrayHasKey('country', $errors);
        $this->assertArrayHasKey('ico', $errors);
        $this->assertArrayHasKey('dic', $errors);
        $this->assertArrayHasKey('account_number', $errors);
        $this->assertArrayHasKey('bank_code', $errors);
        $this->assertArrayHasKey('iban', $errors);
        $this->assertArrayHasKey('swift', $errors);
        $this->assertArrayHasKey('bank_name', $errors);
    }

    /**
     * Test authorization passes when user is authenticated.
     */
    #[Test]
    public function authorization_passes_when_authenticated(): void
    {
        $this->actingAs($this->user, 'backpack');

        $request = new SupplierRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * Test authorization fails when user is not authenticated.
     */
    #[Test]
    public function authorization_fails_when_not_authenticated(): void
    {
        $request = new SupplierRequest();
        $this->assertFalse($request->authorize());
    }

    /**
     * Test custom attributes are correctly defined.
     */
    #[Test]
    public function custom_attributes_are_defined(): void
    {
        $attributes = $this->request->attributes();

        $this->assertIsArray($attributes);
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('email', $attributes);
        $this->assertArrayHasKey('user_id', $attributes);
    }

    /**
     * Test custom messages are correctly defined.
     */
    #[Test]
    public function custom_messages_are_defined(): void
    {
        $messages = $this->request->messages();

        $this->assertIsArray($messages);
        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('email.required', $messages);
        $this->assertArrayHasKey('street.required', $messages);
        $this->assertArrayHasKey('city.required', $messages);
        $this->assertArrayHasKey('zip.required', $messages);
        $this->assertArrayHasKey('country.required', $messages);
        $this->assertArrayHasKey('user_id.required', $messages);
        $this->assertArrayHasKey('account_number.max', $messages);
        $this->assertArrayHasKey('bank_code.required_with', $messages);
        $this->assertArrayHasKey('iban.max', $messages);
        $this->assertArrayHasKey('swift.required_with', $messages);
    }

    /**
     * Test nullable fields work correctly.
     */
    #[Test]
    public function nullable_fields_work_correctly(): void
    {
        $dataWithNulls = [
            'name' => 'Supplier with Nulls',
            'email' => 'nulls@supplier.com',
            'phone' => '+420 123 456 789',
            'street' => '123 Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'user_id' => $this->supplierUser->id,
            'shortcut' => null,
            'ico' => null,
            'dic' => null,
            'description' => null,
            'account_number' => null,
            'bank_code' => null,
            'iban' => null,
            'swift' => null,
            'bank_name' => null,
        ];

        $validator = Validator::make($dataWithNulls, $this->request->rules());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }
}
