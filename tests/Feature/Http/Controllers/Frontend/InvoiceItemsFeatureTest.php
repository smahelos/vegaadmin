<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Models\BankAccount;
use App\Models\Business;
use App\Models\EntityLimit;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Tax;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
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
        $permission = Permission::where('name', 'frontend.can_create_edit_invoice')->where('guard_name', 'web')->first();
        if ($permission) {
            $this->user->givePermissionTo($permission);
        }
        EntityLimit::create([
            'permission_name' => 'frontend.can_create_edit_invoice',
            'entity_type' => 'invoice',
            'limit_value' => 10,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'description' => 'Invoice limit for item tests',
            'is_active' => true,
        ]);
        foreach (['12.00', '21.00'] as $rate) {
            Tax::factory()->create([
                'name' => 'DPH ' . $rate . ' ' . uniqid(),
                'slug' => 'dph',
                'rate' => $rate,
            ]);
        }
        [$this->business, $this->account] = $this->issuerOf($this->user);
        $this->actingAs($this->user, 'web');
    }

    #[Test]
    public function a_stored_invoice_amounts_to_the_sum_of_its_lines_in_cents(): void
    {
        $response = $this->post(route('frontend.invoice.store', ['locale' => 'cs']), $this->invoiceData([
            $this->item([
                'quantity' => '1.5',
                'price' => '249.30',
                'tax_rate' => '12',
            ]),
            $this->item([
                'quantity' => '3',
                'price' => '74.67',
                'tax_rate' => '21.00',
            ]),
        ], number: 'INV-ITEMS-1'));

        $response->assertSessionHas('success');
        $invoice = Invoice::where('invoice_vs', 'INV-ITEMS-1')->firstOrFail();
        // 373.95 + 44.87 and 224.01 + 47.04; the typed payment amount 1 is ignored
        $this->assertSame('689.87', $invoice->fresh()->payment_amount);
        $this->assertSame(
            ['1.500', '3.000'],
            InvoiceProduct::where('invoice_id', $invoice->id)->orderBy('id')->pluck('quantity')->all(),
        );
    }

    #[Test]
    public function updating_an_invoice_replaces_its_lines_and_recomputes_the_amount(): void
    {
        $this->post(route('frontend.invoice.store', ['locale' => 'cs']), $this->invoiceData([
            $this->item(), $this->item(),
        ], number: 'INV-ITEMS-2'));
        $invoice = Invoice::where('invoice_vs', 'INV-ITEMS-2')->firstOrFail();

        $response = $this->put(
            route('frontend.invoice.update', ['locale' => 'cs', 'id' => $invoice->id]),
            $this->invoiceData([$this->item([
                'quantity' => '2',
                'price' => '0.50',
                'tax_rate' => '21',
            ])], number: 'INV-ITEMS-2'),
        );

        $response->assertSessionHasNoErrors();
        $lines = InvoiceProduct::where('invoice_id', $invoice->id)->get();
        $this->assertCount(1, $lines);
        $this->assertSame('0.21', $lines[0]->tax_amount);
        $this->assertSame('1.21', $invoice->fresh()->payment_amount);
    }

    #[Test]
    public function an_invoice_with_an_invalid_item_is_not_stored(): void
    {
        $response = $this->post(route('frontend.invoice.store', ['locale' => 'cs']), $this->invoiceData([
            $this->item(['tax_rate' => '17']),
        ], number: 'INV-ITEMS-3'));

        $response->assertSessionHasErrors(['invoice-products.0.tax_rate']);
        $this->assertDatabaseMissing('invoices', ['invoice_vs' => 'INV-ITEMS-3']);
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
