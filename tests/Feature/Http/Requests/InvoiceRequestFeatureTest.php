<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\InvoiceRequest;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\Supplier;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for InvoiceRequest
 * 
 * Tests complete validation flow with HTTP context and database interactions
 * Tests invoice validation scenarios, authorization, and validation with database constraints
 */
class InvoiceRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected PaymentMethod $paymentMethod;
    protected Status $paymentStatus;
    protected Supplier $supplier;
    protected Client $client;
    protected array $validInvoiceData;

    /**
     * Set up the test environment.
     * Creates related models and valid invoice data for request testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create related models
        $this->createRelatedModels();
        
        // Set up valid invoice data
        $this->setupValidInvoiceData();
    }

    /**
     * Create related models for testing
     */
    private function createRelatedModels(): void
    {
        $this->paymentMethod = PaymentMethod::factory()->create();
        $this->paymentStatus = Status::factory()->create();
        $this->supplier = Supplier::factory()->create();
        $this->client = Client::factory()->create();
    }

    /**
     * Setup valid invoice data for testing
     */
    private function setupValidInvoiceData(): void
    {
        $this->validInvoiceData = [
            'invoice_vs' => $this->faker->numerify('INV-####'),
            'invoice_ks' => $this->faker->numerify('####'),
            'invoice_ss' => $this->faker->numerify('####'),
            'payment_method_id' => $this->paymentMethod->id,
            'payment_amount' => $this->faker->randomFloat(2, 100, 10000),
            'payment_currency' => 'CZK',
            'issue_date' => $this->faker->date(),
            'tax_point_date' => $this->faker->date(),
            'due_in' => $this->faker->numberBetween(1, 30),
            'payment_status_id' => $this->paymentStatus->id,
            
            // Using supplier
            'supplier_id' => $this->supplier->id,
            'email' => $this->faker->email,
            'phone' => $this->faker->phoneNumber,
            
            // Bank details
            'account_number' => $this->faker->numerify('##########'),
            'bank_code' => $this->faker->numerify('####'),
            'bank_name' => $this->faker->company . ' Bank',
            'iban' => 'CZ' . $this->faker->numerify('####################'),
            'swift' => $this->faker->lexify('????????'),
            
            // Using client
            'client_id' => $this->client->id,
            'client_email' => $this->faker->email,
            'client_phone' => $this->faker->phoneNumber,
            
            'invoice_text' => $this->faker->paragraph,
        ];
    }

    #[Test]
    public function validation_passes_with_valid_data_using_existing_supplier_and_client()
    {
        $request = new InvoiceRequest();
        $validator = Validator::make($this->validInvoiceData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_passes_with_manual_supplier_and_client_data()
    {
        $manualData = [
            'invoice_vs' => $this->faker->numerify('INV-####'),
            'payment_method_id' => $this->paymentMethod->id,
            'payment_amount' => 1000.50,
            'payment_currency' => 'EUR',
            'issue_date' => $this->faker->date(),
            'due_in' => 14,
            'payment_status_id' => $this->paymentStatus->id,
            
            // Manual supplier data (no supplier_id)
            'name' => $this->faker->company,
            'street' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'zip' => $this->faker->postcode,
            'country' => $this->faker->country,
            'ico' => $this->faker->numerify('########'),
            'dic' => $this->faker->numerify('CZ########'),
            
            // Manual client data (no client_id)
            'client_name' => $this->faker->company,
            'client_street' => $this->faker->streetAddress,
            'client_city' => $this->faker->city,
            'client_zip' => $this->faker->postcode,
            'client_country' => $this->faker->country,
            'client_ico' => $this->faker->numerify('########'),
            'client_dic' => $this->faker->numerify('CZ########'),
        ];

        $request = new InvoiceRequest();
        $validator = Validator::make($manualData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_when_required_fields_missing()
    {
        $requiredFields = [
            'invoice_vs', 'payment_method_id', 'payment_amount', 
            'payment_currency', 'issue_date', 'due_in', 'payment_status_id'
        ];
        
        foreach ($requiredFields as $field) {
            $invalidData = $this->validInvoiceData;
            unset($invalidData[$field]);

            $request = new InvoiceRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail when {$field} is missing");
            $this->assertArrayHasKey($field, $validator->errors()->toArray(), "Should have error for missing {$field}");
        }
    }

    #[Test]
    public function validation_fails_when_neither_supplier_id_nor_manual_data_provided()
    {
        $invalidData = $this->validInvoiceData;
        unset($invalidData['supplier_id']);
        // Don't provide manual supplier data either

        $request = new InvoiceRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        
        // Should fail for required fields when supplier_id is missing
        $requiredWhenNoSupplierId = ['name', 'street', 'city', 'zip', 'country'];
        foreach ($requiredWhenNoSupplierId as $field) {
            $this->assertArrayHasKey($field, $validator->errors()->toArray(), 
                "Should have error for {$field} when supplier_id is missing");
        }
    }

    #[Test]
    public function validation_fails_when_neither_client_id_nor_manual_data_provided()
    {
        $invalidData = $this->validInvoiceData;
        unset($invalidData['client_id']);
        // Don't provide manual client data either

        $request = new InvoiceRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        
        // Should fail for required fields when client_id is missing
        $requiredWhenNoClientId = ['client_name', 'client_street', 'client_city', 'client_zip', 'client_country'];
        foreach ($requiredWhenNoClientId as $field) {
            $this->assertArrayHasKey($field, $validator->errors()->toArray(), 
                "Should have error for {$field} when client_id is missing");
        }
    }

    #[Test]
    public function validation_fails_with_negative_payment_amount()
    {
        $invalidData = $this->validInvoiceData;
        $invalidData['payment_amount'] = -100;

        $request = new InvoiceRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('payment_amount', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_zero_or_negative_due_in()
    {
        $invalidValues = [0, -1, -10];
        
        foreach ($invalidValues as $value) {
            $invalidData = $this->validInvoiceData;
            $invalidData['due_in'] = $value;

            $request = new InvoiceRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail for due_in value: {$value}");
            $this->assertArrayHasKey('due_in', $validator->errors()->toArray());
        }
    }

    #[Test]
    public function validation_fails_with_invalid_currency_format()
    {
        $invalidData = $this->validInvoiceData;
        $invalidData['payment_currency'] = 'INVALID'; // Too long (max 3)

        $request = new InvoiceRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('payment_currency', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_invalid_email_formats()
    {
        $invalidEmails = ['invalid-email', 'test@', '@test.com', 'test.com'];
        
        foreach ($invalidEmails as $email) {
            $invalidData = $this->validInvoiceData;
            $invalidData['email'] = $email;

            $request = new InvoiceRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail for email: {$email}");
            $this->assertArrayHasKey('email', $validator->errors()->toArray());
        }
    }

    #[Test]
    public function validation_fails_with_nonexistent_foreign_keys()
    {
        $foreignKeyFields = [
            'payment_method_id' => 99999,
            'payment_status_id' => 99999,
            'supplier_id' => 99999,
            'client_id' => 99999,
        ];
        
        foreach ($foreignKeyFields as $field => $invalidId) {
            $invalidData = $this->validInvoiceData;
            $invalidData[$field] = $invalidId;

            $request = new InvoiceRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail for {$field} with invalid ID: {$invalidId}");
            $this->assertArrayHasKey($field, $validator->errors()->toArray());
        }
    }

    #[Test]
    public function validation_fails_with_too_short_names()
    {
        $shortNameFields = [
            'name' => 'AB', // min 3
            'client_name' => 'AB', // min 3
        ];
        
        foreach ($shortNameFields as $field => $shortValue) {
            $invalidData = $this->validInvoiceData;
            
            // Remove IDs so manual data is required
            if ($field === 'name') {
                unset($invalidData['supplier_id']);
                $invalidData['street'] = 'Test Street';
                $invalidData['city'] = 'Test City';
                $invalidData['zip'] = '12345';
                $invalidData['country'] = 'Test Country';
            } else {
                unset($invalidData['client_id']);
                $invalidData['client_street'] = 'Test Street';
                $invalidData['client_city'] = 'Test City';
                $invalidData['client_zip'] = '12345';
                $invalidData['client_country'] = 'Test Country';
            }
            
            $invalidData[$field] = $shortValue;

            $request = new InvoiceRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail for short {$field}: {$shortValue}");
            $this->assertArrayHasKey($field, $validator->errors()->toArray());
        }
    }

    #[Test]
    public function validation_passes_with_nullable_fields_set_to_null()
    {
        $dataWithNulls = $this->validInvoiceData;
        $nullableFields = [
            'invoice_ks', 'invoice_ss', 'tax_point_date', 'email', 'phone',
            'ico', 'dic', 'supplier_shortcut', 'account_number', 'bank_code',
            'bank_name', 'iban', 'swift', 'client_email', 'client_phone',
            'client_ico', 'client_dic', 'client_shortcut', 'invoice_text'
        ];
        
        foreach ($nullableFields as $field) {
            $dataWithNulls[$field] = null;
        }

        $request = new InvoiceRequest();
        $validator = Validator::make($dataWithNulls, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function authorization_always_returns_true()
    {
        $request = new InvoiceRequest();
        
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function validation_passes_with_valid_currencies()
    {
        $validCurrencies = ['CZK', 'EUR', 'USD', 'GBP'];
        
        foreach ($validCurrencies as $currency) {
            $validData = $this->validInvoiceData;
            $validData['payment_currency'] = $currency;
            $validData['invoice_vs'] = 'INV-' . $currency; // Make unique

            $request = new InvoiceRequest();
            $validator = Validator::make($validData, $request->rules(), $request->messages());

            $this->assertFalse($validator->fails(), "Validation should pass for currency: {$currency}");
        }
    }

    #[Test]
    public function validation_handles_fallback_fields_in_prepare_for_validation()
    {
        // Test that validation passes with minimal but complete data 
        $minimalData = [
            'invoice_vs' => 'INV-MINIMAL',
            'payment_method_id' => $this->paymentMethod->id,
            'payment_amount' => 1000,
            'payment_currency' => 'CZK',
            'issue_date' => $this->faker->date(),
            'due_in' => 14,
            'payment_status_id' => $this->paymentStatus->id,
            
            // Provide supplier_id so supplier fields are not required
            'supplier_id' => $this->supplier->id,
            // Provide client_id so client fields are not required
            'client_id' => $this->client->id,
        ];

        $request = new InvoiceRequest();
        
        // Test that validation works with minimal data
        $validator = Validator::make($minimalData, $request->rules());
        
        // This should pass validation as all required fields have values
        $this->assertFalse($validator->fails());
        if ($validator->fails()) {
            $this->fail('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }
    }

    #[Test]
    public function request_has_custom_validation_messages()
    {
        $request = new InvoiceRequest();
        $messages = $request->messages();

        $expectedKeys = [
            'invoice_vs.required',
            'payment_method_id.required',
            'payment_amount.required',
            'payment_amount.numeric',
            'payment_amount.min',
            'supplier_id.required_without',
            'name.required_without',
            'client_id.required_without',
            'client_name.required_without',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $messages, "Should have custom message for {$key}");
        }
    }
}
