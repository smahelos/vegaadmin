<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin;

use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Models\Contact;
use App\Models\Invoice;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsInvoiceInput;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

final class InvoiceRequestFeatureTest extends TestCase
{
    use BuildsInvoiceInput;
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();
        $this->actingAs($this->adminUser, 'backpack');
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidInput(): array
    {
        return [
            'missing business' => ['business_id', null],
            'missing owner' => ['user_id', null],
            'missing customer' => ['customer_name', ''],
            'missing street' => ['customer_street', ''],
            'missing city' => ['customer_city', ''],
            'missing postal code' => ['customer_postal_code', ''],
            'invalid country' => ['customer_country', 'invalid'],
            'missing account for transfer' => ['bank_account_id', null],
            'number outside the invoice number format' => ['invoice_vs', 'FV-2026/1'],
            'status from the expense category' => ['status', 'paid-expense'],
            'unknown template' => ['template', 'fancy'],
            'zero due days' => ['due_in', 0],
            'too many due days' => ['due_in', InvoiceDueDate::MAX_DAYS + 1],
            'unknown currency' => ['payment_currency', 'ABC'],
            'unknown payment method' => ['payment_method_id', 99999999],
            'invalid date' => ['issue_date', 'invalid'],
        ];
    }

    #[Test]
    #[DataProvider('invalidInput')]
    public function rejects_invalid_document_input(string $field, mixed $value): void
    {
        [$business, $account] = $this->issuerOf($this->adminUser);
        $httpResponse = $this->post(
            '/admin/invoice',
            $this->invoiceInput(
                $business,
                $account,
                [
                    'user_id' => $this->adminUser->id,
                    $field => $value,
                ],
            ),
        );
        $httpResponse->assertSessionHasErrors($field);
    }

    #[Test]
    public function a_correction_with_the_same_contact_still_requires_the_complete_address(): void
    {
        [$business, $account] = $this->issuerOf($this->adminUser);
        $invoiceFactory = Invoice::factory();
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->adminUser->id]);
        $invoice = $invoiceFactory->create(
            [
                'user_id' => $this->adminUser->id,
                'business_id' => $business->id,
                'contact_id' => $contact->id,
            ],
        );
        $httpResponse = $this->put(
            '/admin/invoice/' . $invoice->id,
            $this->invoiceInput(
                $business,
                $account,
                [
                    'user_id' => $this->adminUser->id,
                    'contact_id' => $invoice->contact_id,
                    'customer_street' => '',
                ],
            ),
        );
        $httpResponse->assertSessionHasErrors('customer_street');
    }
}
