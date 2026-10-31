<?php

namespace Tests\Feature\Traits;

use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Infrastructure\Forms\Invoice\InvoiceFormFields;
use App\Models\PaymentMethod;
use App\Models\Status;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceFormFieldsFeatureTest extends TestCase
{
    use InvoiceFormFields;

    #[Test]
    public function get_invoice_fields_works_with_real_data(): void
    {
        // Arrange
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::CASH->value)->firstOrFail();
        $status = Status::factory()->create(['name' => 'Paid']);

        $paymentMethods = [$paymentMethod->id => $paymentMethod->name];
        $statuses = [$status->id => $status->name];
        $currencies = ['EUR' => 'EUR', 'USD' => 'USD'];

        // Act
        $result = $this->getInvoiceFields(
            $paymentMethods,
            $statuses,
            $currencies,
        );

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Verify options are passed correctly
        $paymentMethodField = collect($result)->firstWhere('name', 'payment_method_id');
        $statusField = collect($result)->firstWhere('name', 'payment_status_id');
        $currencyField = collect($result)->firstWhere('name', 'payment_currency');

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
        $paymentMethodField = collect($result)->firstWhere('name', 'payment_method_id');
        $statusField = collect($result)->firstWhere('name', 'payment_status_id');

        $this->assertEquals([], $paymentMethodField['options']);
        $this->assertEquals([], $statusField['options']);
    }

    #[Test]
    public function get_invoice_fields_returns_all_expected_field_count(): void
    {
        // Act
        $result = $this->getInvoiceFields();

        // Assert: the parties and the account are chosen in the DocumentPartyPicker, not in this field set
        $this->assertSame([
            'invoice_logo',
            'template',
            'invoice_vs',
            'payment_status_id',
            'invoice_ks',
            'invoice_ss',
            'issue_date',
            'tax_point_date',
            'payment_method_id',
            'due_in',
            'payment_amount',
            'payment_currency',
            'invoice_text',
        ], array_column($result, 'name'));
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

        $this->assertEquals(date('Y-m-d'), $issueDateField['default']);
        $this->assertEquals(date('Y-m-d'), $taxPointDateField['default']);
        $this->assertEquals(0, $paymentAmountField['default']);
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
            [1 => 'Cash'],
            [1 => 'Paid'],
            ['EUR' => 'EUR'],
        ];

        // Act
        $result1 = $this->getInvoiceFields(...$testData);
        $result2 = $this->getInvoiceFields(...$testData);

        // Assert
        $this->assertEquals($result1, $result2);
        $this->assertCount(count($result1), $result2);
    }
}
