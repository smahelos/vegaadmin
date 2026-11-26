<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Domain\User\Enums\Permission;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Permission as PermissionModel;
use App\Models\Tax;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsInvoiceInput;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;

/**
 * Items travel from the invoice form as one JSON field; the stored lines and the invoice amount
 * are computed on the server.
 */
final class InvoiceItemsFeatureTest extends TestCase
{
    use BuildsInvoiceInput, CreatesFrontendTestEnvironment;

    private Business $business;

    private BankAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFrontendTestEnvironment();
        $permissionQuery = PermissionModel::where('name', Permission::INVOICE_CREATE->value);
        $permissionQuery->where('guard_name', 'web');
        $permission = $permissionQuery->first();
        if ($permission) {
            $this->user->givePermissionTo($permission);
        }
        $taxFactory = Tax::factory();
        foreach (['12.00', '21.00'] as $rate) {
            $taxFactory->create(
                [
                    'name' => 'DPH ' . $rate . ' ' . uniqid(),
                    'slug' => 'dph',
                    'rate' => $rate,
                ],
            );
        }
        [$this->business, $this->account] = $this->issuerOf($this->user);
        $this->actingAs($this->user, 'web');
    }

    #[Test]
    public function a_stored_invoice_amounts_to_the_sum_of_its_lines_in_cents(): void
    {
        $number = $this->invoiceNumber();
        $response = $this->post(
            route('frontend.invoice.store', ['locale' => 'cs']),
            $this->invoiceData(
                [
                    $this->item(
                        [
                            'quantity' => '1.5',
                            'price' => '249.30',
                            'tax_rate' => '12',
                        ],
                    ),
                    $this->item(
                        [
                            'quantity' => '3',
                            'price' => '74.67',
                            'tax_rate' => '21.00',
                        ],
                    ),
                ],
                number: $number,
            ),
        );

        $response->assertSessionHas('success');
        $invoiceQuery = Invoice::where('invoice_vs', $number);
        $invoice = $invoiceQuery->firstOrFail();
        // 373.95 + 44.87 and 224.01 + 47.04; the typed payment amount 1 is ignored
        $this->assertSame('689.87', $invoice->fresh()->payment_amount);
        $invoiceProductQuery = InvoiceProduct::where('invoice_id', $invoice->id);
        $invoiceProductQuery->orderBy('id');
        $invoiceProductValues = $invoiceProductQuery->pluck('quantity');
        $this->assertSame(
            ['1.500', '3.000'],
            $invoiceProductValues->all(),
        );
    }

    #[Test]
    public function updating_an_invoice_replaces_its_lines_and_recomputes_the_amount(): void
    {
        $number = $this->invoiceNumber();
        $this->post(
            route('frontend.invoice.store', ['locale' => 'cs']),
            $this->invoiceData([
                $this->item(), $this->item(),
            ], number: $number),
        );
        $invoiceQuery = Invoice::where('invoice_vs', $number);
        $invoice = $invoiceQuery->firstOrFail();

        $response = $this->put(
            route('frontend.invoice.update', ['locale' => 'cs', 'id' => $invoice->id]),
            $this->invoiceData(
                [$this->item(
                    [
                        'quantity' => '2',
                        'price' => '0.50',
                        'tax_rate' => '21',
                    ],
                )],
                number: $number,
            ),
        );

        $response->assertSessionHasNoErrors();
        $invoiceProductQuery = InvoiceProduct::where('invoice_id', $invoice->id);
        $lines = $invoiceProductQuery->get();
        $this->assertCount(1, $lines);
        $this->assertSame('0.50', $lines[0]->price);
        $this->assertSame('1.21', $invoice->fresh()->payment_amount);
    }

    #[Test]
    public function lines_of_one_rate_are_rounded_once_for_the_rate(): void
    {
        $number = $this->invoiceNumber();
        $line = $this->item(
            [
                'quantity' => '1.5',
                'price' => '0.03',
            ],
        );
        $response = $this->post(
            route('frontend.invoice.store', ['locale' => 'cs']),
            $this->invoiceData([$line, $line], number: $number),
        );

        $response->assertSessionHas('success');
        $invoiceQuery = Invoice::where('invoice_vs', $number);
        // Base 2 x 0.045 = 0.09 and VAT 0.0189 of the rate; rounding each line would give 0.10 + 0.02
        $this->assertSame(
            '0.11',
            $invoiceQuery->value('payment_amount'),
        );
    }

    #[Test]
    public function a_business_that_is_not_a_vat_payer_charges_no_vat(): void
    {
        $this->business->update(['is_vat_payer' => false]);
        $number = $this->invoiceNumber();
        $response = $this->post(
            route('frontend.invoice.store', ['locale' => 'cs']),
            $this->invoiceData([$this->item()], number: $number),
        );

        $response->assertSessionHasErrors(['invoice-products']);
        $this->assertDatabaseMissing('invoices', ['invoice_vs' => $number]);

        $response = $this->post(
            route('frontend.invoice.store', ['locale' => 'cs']),
            $this->invoiceData(
                [$this->item(['tax_rate' => '0'])],
                number: $number,
            ),
        );

        $response->assertSessionHas('success');
        $invoiceQuery = Invoice::where('invoice_vs', $number);
        $this->assertSame(
            '100.00',
            $invoiceQuery->value('payment_amount'),
        );
    }

    #[Test]
    public function an_invoice_with_an_invalid_item_is_not_stored(): void
    {
        $number = $this->invoiceNumber();
        $response = $this->post(
            route('frontend.invoice.store', ['locale' => 'cs']),
            $this->invoiceData(
                [
                    $this->item(['tax_rate' => '17']),
                ],
                number: $number,
            ),
        );

        $response->assertSessionHasErrors(['invoice-products.0.tax_rate']);
        $this->assertDatabaseMissing('invoices', ['invoice_vs' => $number]);
    }

    /**
     * @param  list<array<string, string>>  $items
     * @return array<string, mixed>
     */
    private function invoiceData(array $items, string $number): array
    {
        return $this->invoiceInput(
            $this->business,
            $this->account,
            [
                'invoice_vs' => $number,
                'payment_amount' => 1,
                'invoice-products' => json_encode($items),
            ],
        );
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function item(array $overrides = []): array
    {
        return $this->invoiceItem($overrides + ['tax_rate' => '21']);
    }
}
