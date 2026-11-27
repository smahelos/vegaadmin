<?php

namespace Tests\Feature\Traits;

use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Infrastructure\Forms\Invoice\InvoiceFormFields;
use App\Models\PaymentMethod;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceFormFieldsFeatureTest extends TestCase
{
    use InvoiceFormFields;

    #[Test]
    public function get_invoice_fields_works_with_real_data(): void
    {
        // Arrange
        $paymentMethodQuery = PaymentMethod::where('slug', PaymentMethodCode::CASH->value);
        $paymentMethod = $paymentMethodQuery->firstOrFail();
        $paymentMethods = [$paymentMethod->id => $paymentMethod->name];

        // Act
        $result = $this->getInvoiceFields($paymentMethods);

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Verify options are passed correctly
        $resultList = collect($result);
        $paymentMethodField = $resultList->firstWhere('name', 'payment_method_id');

        $this->assertEquals($paymentMethods, $paymentMethodField['options']);
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
        $resultList = collect($result);
        $paymentMethodField = $resultList->firstWhere('name', 'payment_method_id');

        $this->assertEquals([], $paymentMethodField['options']);
    }

    #[Test]
    public function get_invoice_fields_returns_all_expected_field_count(): void
    {
        // Act
        $result = $this->getInvoiceFields();

        // Assert: the parties and the account are chosen in the DocumentPartyPicker, not in this field set; a draft
        // gets its number and dates at issue
        $this->assertSame(
            [
                'invoice_logo',
                'template',
                'invoice_ks',
                'invoice_ss',
                'document_language',
                'payment_method_id',
                'due_in',
                'payment_amount',
                'payment_currency',
                'invoice_text',
            ],
            array_column($result, 'name'),
        );
        $guestResult = $this->getInvoiceFields([], true);
        $this->assertSame(
            [
                'invoice_logo',
                'template',
                'invoice_vs',
                'invoice_ks',
                'invoice_ss',
                'issue_date',
                'tax_point_date',
                'payment_method_id',
                'due_in',
                'payment_amount',
                'payment_currency',
                'invoice_text',
            ],
            array_column($guestResult, 'name'),
        );
    }

    #[Test]
    public function get_invoice_fields_has_correct_validation_rules(): void
    {
        // Act
        $result = $this->getInvoiceFields();

        // Assert - Check required fields
        $collection = collect($result);
        $requiredFields = $collection->where('required', true);

        $requiredFieldNames = $requiredFields->pluck('name');
        $requiredFieldNames = $requiredFieldNames->toArray();

        // These fields should be required
        $this->assertContains('document_language', $requiredFieldNames);
        $this->assertContains('payment_method_id', $requiredFieldNames);
        $this->assertContains('due_in', $requiredFieldNames);
        $this->assertContains('payment_amount', $requiredFieldNames);
        $this->assertContains('payment_currency', $requiredFieldNames);
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
        $collection = collect($result);
        $languageField = $collection->firstWhere('name', 'document_language');
        $this->assertNotNull($languageField);
        $this->assertArrayHasKey('label', $languageField);
    }

    #[Test]
    public function get_invoice_fields_contains_hint_fields(): void
    {
        // Act
        $result = $this->getInvoiceFields();

        // Assert - Check that fields have hints
        $resultList = collect($result);
        $fieldsWithHints = $resultList->whereNotNull('hint');

        $this->assertGreaterThan(5, $fieldsWithHints->count());

        // Check specific hint exists
        $languageField = $resultList->firstWhere('name', 'document_language');
        $this->assertArrayHasKey('hint', $languageField);
        $this->assertNotNull($languageField['hint']);
    }

    #[Test]
    public function get_invoice_fields_due_in_options_are_correct(): void
    {
        // Act
        $result = $this->getInvoiceFields();

        // Assert
        $collection = collect($result);
        $dueInField = $collection->firstWhere('name', 'due_in');

        $this->assertArrayHasKey('options', $dueInField);
        $this->assertArrayHasKey(1, $dueInField['options']);
        $this->assertArrayHasKey(3, $dueInField['options']);
        $this->assertArrayHasKey(7, $dueInField['options']);
        $this->assertArrayHasKey(14, $dueInField['options']);
        $this->assertArrayHasKey(30, $dueInField['options']);

        // Check format includes days unit
        $this->assertTrue(
            str_contains($dueInField['options'][1], 'days') || str_contains($dueInField['options'][1], 'dní'),
        );
    }

    #[Test]
    public function get_invoice_fields_maintains_consistency_across_calls(): void
    {
        // Arrange
        $paymentMethods = [1 => 'Cash'];

        // Act
        $result1 = $this->getInvoiceFields($paymentMethods);
        $result2 = $this->getInvoiceFields($paymentMethods);

        // Assert
        $this->assertEquals($result1, $result2);
        $this->assertCount(
            count($result1),
            $result2,
        );
    }
}
