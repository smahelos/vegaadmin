<?php

namespace Tests\Feature\Traits;

use App\Models\Client;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\Supplier;
use App\Services\CountryService;
use App\Traits\InvoiceFormFields;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceFormFieldsFeatureTest extends TestCase
{
    use RefreshDatabase, InvoiceFormFields;

    #[Test]
    public function get_invoice_fields_integrates_with_country_service(): void
    {
        // Act
        $result = $this->getInvoiceFields();

        // Assert
        $this->assertIsArray($result);
        
        // Find country fields
        $supplierCountryField = collect($result)->firstWhere('name', 'country');
        $clientCountryField = collect($result)->firstWhere('name', 'client_country');

        $this->assertNotNull($supplierCountryField);
        $this->assertNotNull($clientCountryField);
        $this->assertArrayHasKey('options', $supplierCountryField);
        $this->assertArrayHasKey('options', $clientCountryField);
    }

    #[Test]
    public function get_invoice_fields_works_with_real_data(): void
    {
        // Arrange
        $client = Client::factory()->create(['name' => 'Test Client']);
        $supplier = Supplier::factory()->create(['name' => 'Test Supplier']);
        $paymentMethod = PaymentMethod::factory()->create(['name' => 'Cash']);
        $status = Status::factory()->create(['name' => 'Paid']);

        $clients = [$client->id => $client->name];
        $suppliers = [$supplier->id => $supplier->name];
        $paymentMethods = [$paymentMethod->id => $paymentMethod->name];
        $statuses = [$status->id => $status->name];
        $currencies = ['EUR' => 'EUR', 'USD' => 'USD'];

        // Act
        $result = $this->getInvoiceFields($clients, $suppliers, $paymentMethods, $statuses, $currencies);

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Verify options are passed correctly
        $clientField = collect($result)->firstWhere('name', 'client_id');
        $supplierField = collect($result)->firstWhere('name', 'supplier_id');
        $paymentMethodField = collect($result)->firstWhere('name', 'payment_method_id');
        $statusField = collect($result)->firstWhere('name', 'payment_status_id');
        $currencyField = collect($result)->firstWhere('name', 'payment_currency');

        $this->assertEquals($clients, $clientField['options']);
        $this->assertEquals($suppliers, $supplierField['options']);
        $this->assertEquals($paymentMethods, $paymentMethodField['options']);
        $this->assertEquals($statuses, $statusField['options']);
        $this->assertEquals($currencies, $currencyField['options']);
    }

    #[Test]
    public function get_invoice_fields_handles_empty_parameters(): void
    {
        // Act
        $result = $this->getInvoiceFields();

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Check that empty arrays are handled correctly
        $clientField = collect($result)->firstWhere('name', 'client_id');
        $supplierField = collect($result)->firstWhere('name', 'supplier_id');
        $paymentMethodField = collect($result)->firstWhere('name', 'payment_method_id');
        $statusField = collect($result)->firstWhere('name', 'payment_status_id');

        $this->assertEquals([], $clientField['options']);
        $this->assertEquals([], $supplierField['options']);
        $this->assertEquals([], $paymentMethodField['options']);
        $this->assertEquals([], $statusField['options']);
    }

    #[Test]
    public function get_invoice_fields_returns_all_expected_field_count(): void
    {
        // Act
        $result = $this->getInvoiceFields();

        // Assert - Check that we have all expected fields
        // Based on the trait implementation, there should be multiple fields
        $this->assertGreaterThan(30, count($result)); // Expecting at least 30+ fields
        
        // Verify we have fields from all sections
        $fieldNames = collect($result)->pluck('name')->toArray();
        
        // Invoice section
        $this->assertContains('invoice_vs', $fieldNames);
        $this->assertContains('payment_amount', $fieldNames);
        
        // Supplier section  
        $this->assertContains('supplier_id', $fieldNames);
        $this->assertContains('name', $fieldNames);
        
        // Client section
        $this->assertContains('client_id', $fieldNames);
        $this->assertContains('client_name', $fieldNames);
        
        // Payment section
        $this->assertContains('account_number', $fieldNames);
        $this->assertContains('iban', $fieldNames);
    }

    #[Test]
    public function get_invoice_fields_has_correct_validation_rules(): void
    {
        // Act
        $result = $this->getInvoiceFields();

        // Assert - Check required fields
        $requiredFields = collect($result)->where('required', true);
        
        $requiredFieldNames = $requiredFields->pluck('name')->toArray();
        
        // These fields should be required
        $this->assertContains('invoice_vs', $requiredFieldNames);
        $this->assertContains('issue_date', $requiredFieldNames);
        $this->assertContains('payment_method_id', $requiredFieldNames);
        $this->assertContains('due_in', $requiredFieldNames);
        $this->assertContains('payment_amount', $requiredFieldNames);
        $this->assertContains('payment_currency', $requiredFieldNames);
        $this->assertContains('payment_status_id', $requiredFieldNames);
        $this->assertContains('supplier_id', $requiredFieldNames);
        $this->assertContains('client_id', $requiredFieldNames);
    }

    #[Test]
    public function get_invoice_fields_has_correct_default_values(): void
    {
        // Act
        $result = $this->getInvoiceFields();

        // Assert - Check default values
        $issueDateField = collect($result)->firstWhere('name', 'issue_date');
        $taxPointDateField = collect($result)->firstWhere('name', 'tax_point_date');
        $paymentAmountField = collect($result)->firstWhere('name', 'payment_amount');
        $countryField = collect($result)->firstWhere('name', 'country');
        $clientCountryField = collect($result)->firstWhere('name', 'client_country');

        $this->assertEquals(date('Y-m-d'), $issueDateField['default']);
        $this->assertEquals(date('Y-m-d'), $taxPointDateField['default']);
        $this->assertEquals(0, $paymentAmountField['default']);
        $this->assertEquals('Česká republika', $countryField['default']);
        $this->assertEquals('Česká republika', $clientCountryField['default']);
    }

    #[Test]
    public function get_invoice_fields_contains_translated_labels(): void
    {
        // Act
        $result = $this->getInvoiceFields();

        // Assert - Check that all fields have labels (translated or raw)
        foreach ($result as $field) {
            $this->assertArrayHasKey('label', $field);
            $this->assertNotEmpty($field['label']);
        }

        // Check specific field exists
        $invoiceVsField = collect($result)->firstWhere('name', 'invoice_vs');
        $this->assertNotNull($invoiceVsField);
        $this->assertArrayHasKey('label', $invoiceVsField);
    }

    #[Test]
    public function get_invoice_fields_contains_hint_fields(): void
    {
        // Act
        $result = $this->getInvoiceFields();

        // Assert - Check that fields have hints
        $fieldsWithHints = collect($result)->whereNotNull('hint');
        
        $this->assertGreaterThan(5, $fieldsWithHints->count());
        
        // Check specific hint exists
        $invoiceVsField = collect($result)->firstWhere('name', 'invoice_vs');
        $this->assertArrayHasKey('hint', $invoiceVsField);
        $this->assertNotNull($invoiceVsField['hint']);
    }

    #[Test]
    public function get_invoice_fields_due_in_options_are_correct(): void
    {
        // Act
        $result = $this->getInvoiceFields();

        // Assert
        $dueInField = collect($result)->firstWhere('name', 'due_in');
        
        $this->assertArrayHasKey('options', $dueInField);
        $this->assertArrayHasKey(1, $dueInField['options']);
        $this->assertArrayHasKey(3, $dueInField['options']);
        $this->assertArrayHasKey(7, $dueInField['options']);
        $this->assertArrayHasKey(14, $dueInField['options']);
        $this->assertArrayHasKey(30, $dueInField['options']);
        
        // Check format includes days unit
        $this->assertTrue(str_contains($dueInField['options'][1], 'days') || str_contains($dueInField['options'][1], 'dní'));
    }

    #[Test]
    public function get_invoice_fields_maintains_consistency_across_calls(): void
    {
        // Arrange
        $testData = [
            [1 => 'Client 1'],
            [1 => 'Supplier 1'],
            [1 => 'Cash'],
            [1 => 'Paid'],
            ['EUR' => 'EUR']
        ];

        // Act
        $result1 = $this->getInvoiceFields(...$testData);
        $result2 = $this->getInvoiceFields(...$testData);

        // Assert
        $this->assertEquals($result1, $result2);
        $this->assertCount(count($result1), $result2);
    }
}
