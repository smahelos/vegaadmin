<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Application\Invoice\Contracts\InvoiceCrudApplicationServiceInterface;
use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Application\Invoice\Services\InvoiceProductWriteDataAssembler;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Product;
use App\Models\Tax;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsInvoiceInput;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

final class InvoiceItemsControllerTest extends TestCase
{
    use BuildsInvoiceInput;
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();
        $this->actingAs($this->adminUser, 'backpack');
    }

    #[Test]
    public function the_catalog_is_scoped_to_the_invoice_owner_currency_search_and_active_products(): void
    {
        $product = Product::factory()->create([
            'user_id' => $this->regularUser->id,
            'name' => 'Shared consulting',
            'currency' => 'CZK',
            'price' => '123.45',
            'is_active' => true,
        ]);
        Product::factory()->create(['name' => 'Shared consulting foreign owner', 'currency' => 'CZK']);
        Product::factory()->create([
            'user_id' => $this->regularUser->id,
            'name' => 'Shared EUR',
            'currency' => 'EUR',
        ]);
        Product::factory()->create([
            'user_id' => $this->regularUser->id,
            'name' => 'Shared inactive',
            'currency' => 'CZK',
            'is_active' => false,
        ]);
        $this->getJson($this->catalogUrl('Shared'))->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product_id', $product->id)->assertJsonPath('data.0.price', '123.45');
        $this->getJson($this->catalogUrl('No match'))->assertOk()->assertJsonCount(0, 'data');
    }

    #[Test]
    public function preview_uses_the_same_per_line_rounding_as_saving_and_writes_nothing(): void
    {
        $tax = Tax::where('slug', 'dph')->where(
            'rate',
            '>',
            0,
        )->firstOrFail();
        $line = $this->invoiceItem([
            'price' => '0.03',
            'quantity' => '1.5',
            'tax_rate' => (string) $tax->rate,
        ]);
        $before = Invoice::count();
        $response = $this->postJson('/admin/invoice-items/preview', [
            'user_id' => $this->regularUser->id,
            'payment_currency' => 'CZK',
            'invoice-products' => [$line, $line],
        ])->assertOk();
        $this->assertSame($before, Invoice::count());

        [$business, $account] = $this->issuerOf($this->regularUser);
        $data = $this->invoiceInput(
            $business,
            $account,
            [
                'user_id' => $this->regularUser->id,
                'payment_amount' => '999999.99',
                'invoice-products' => json_encode([$line, $line]),
            ],
        );
        $this->post('/admin/invoice', $data)->assertSessionHasNoErrors()->assertRedirect();
        $invoice = Invoice::where('invoice_vs', $data['invoice_vs'])->sole();
        $this->assertEquals($response->json('data.total'), $invoice->payment_amount);
        $this->assertSame('0.1', $response->json('data.subtotal'));
        $this->assertSame(2, $invoice->invoiceProducts()->count());
    }

    #[Test]
    public function an_invoice_saves_catalog_and_custom_items_and_replaces_them_on_edit(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $product = Product::factory()->create([
            'user_id' => $this->regularUser->id,
            'price' => '100',
            'currency' => 'CZK',
        ]);
        $data = $this->invoiceInput(
            $business,
            $account,
            [
                'user_id' => $this->regularUser->id,
                'payment_amount' => '9999',
                'invoice-products' => [
                    $this->invoiceItem([
                        'product_id' => (string) $product->id,
                        'quantity' => '2',
                        'price' => '125.50',
                        'name' => 'Saved catalogue line',
                    ]),
                    $this->invoiceItem([
                        'quantity' => '0.5',
                        'price' => '100',
                        'description' => 'Custom description',
                    ]),
                ],
            ],
        );
        $this->post('/admin/invoice', $data)->assertSessionHasNoErrors()->assertRedirect();
        $invoice = Invoice::where('invoice_vs', $data['invoice_vs'])->sole();
        $this->assertSame('301.00', $invoice->payment_amount);
        $this->assertDatabaseHas('invoice_products', [
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'price' => '125.50',
            'is_custom_product' => false,
        ]);
        $this->assertDatabaseHas('invoice_products', [
            'invoice_id' => $invoice->id,
            'description' => 'Custom description',
            'is_custom_product' => true,
        ]);
        $this->assertSame('100.00', $product->fresh()->price);
        $data['invoice-products'] = [$this->invoiceItem(['price' => '45.67', 'quantity' => '3'])];
        $this->put('/admin/invoice/' . $invoice->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('137.01', $invoice->fresh()->payment_amount);
        $this->assertSame(1, $invoice->invoiceProducts()->count());
        $data['invoice-products'] = [];
        $data['payment_amount'] = '88.88';
        $this->put('/admin/invoice/' . $invoice->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(0, $invoice->invoiceProducts()->count());
        $this->assertSame('88.88', $invoice->fresh()->payment_amount);
    }

    #[Test]
    public function a_product_can_be_used_by_two_businesses_of_the_same_user(): void
    {
        $product = Product::factory()->create(['user_id' => $this->regularUser->id]);
        foreach ([1, 2] as $index) {
            [$business, $account] = $this->issuerOf($this->regularUser);
            $data = $this->invoiceInput(
                $business,
                $account,
                [
                    'user_id' => $this->regularUser->id,
                    'payment_amount' => '0',
                    'invoice-products' => [$this->invoiceItem(['product_id' => (string) $product->id])],
                ],
            );
            $this->post('/admin/invoice', $data)->assertSessionHasNoErrors()->assertRedirect();
            $this->assertDatabaseHas('invoices', [
                'invoice_vs' => $data['invoice_vs'],
                'business_id' => $business->id,
                'payment_amount' => '100.00',
            ]);
        }
        $this->assertSame(2, InvoiceProduct::where('product_id', $product->id)->count());
    }

    #[Test]
    public function opening_edit_preserves_the_saved_item_instead_of_reloading_the_catalog(): void
    {
        $product = Product::factory()->create(['name' => 'Current catalogue name', 'price' => '999.99']);
        $invoice = Invoice::factory()->create(['user_id' => $product->user_id]);
        InvoiceProduct::factory()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'name' => 'Saved name',
            'price' => '17.23',
            'quantity' => '2.500',
        ]);
        $this->get('/admin/invoice/' . $invoice->id . '/edit')->assertOk()
            ->assertSee('Saved name')->assertSee('17.23')->assertSee('2.500')->assertDontSee('Current catalogue name');
    }

    #[Test]
    public function invalid_items_are_rejected_and_leave_existing_lines_untouched(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $invoice = Invoice::factory()->create(['user_id' => $this->regularUser->id, 'business_id' => $business->id]);
        $existing = InvoiceProduct::factory()->create(['invoice_id' => $invoice->id]);
        $data = $this->invoiceInput(
            $business,
            $account,
            [
                'user_id' => $this->regularUser->id,
                'payment_amount' => '0',
                'invoice-products' => [$this->invoiceItem([
                    'quantity' => '-1',
                    'currency' => 'EUR',
                    'tax_rate' => '19.876',
                ])],
            ],
        );
        $this->put('/admin/invoice/' . $invoice->id, $data)->assertSessionHasErrors([
            'invoice-products.0.quantity',
            'invoice-products.0.currency',
            'invoice-products.0.tax_rate',
        ]);
        $this->assertSame([$existing->id], $invoice->invoiceProducts()->pluck('id')->all());
    }

    #[Test]
    public function another_owners_product_is_rejected_by_both_save_and_preview(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $product = Product::factory()->create();
        $data = $this->invoiceInput(
            $business,
            $account,
            [
                'user_id' => $this->regularUser->id,
                'payment_amount' => '0',
                'invoice-products' => [$this->invoiceItem(['product_id' => (string) $product->id])],
            ],
        );
        $this->post('/admin/invoice', $data)->assertSessionHasErrors('invoice-products.0.product_id');
        $this->postJson('/admin/invoice-items/preview', $data)->assertUnprocessable()->assertJsonValidationErrors('invoice-products.0.product_id');
        $this->assertDatabaseMissing('invoices', ['invoice_vs' => $data['invoice_vs']]);
    }

    #[Test]
    public function application_creation_also_checks_catalog_ownership(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $product = Product::factory()->create();
        $items = app(InvoiceProductWriteDataAssembler::class)->fromValidated([$this->invoiceItem(['product_id' => (string) $product->id])]);
        $this->expectException(InvoiceNotFoundException::class);
        app(InvoiceCrudApplicationServiceInterface::class)->create(
            UserId::fromInt($this->regularUser->id),
            [],
            new InvoicePartySelection(
                BusinessId::fromInt($business->id),
                BankAccountId::fromInt($account->id),
                null,
                new DocumentPartyDTO(
                    'Customer',
                    null,
                    null,
                    null,
                    null,
                    null,
                ),
                false,
                false,
            ),
            $items,
        );
    }

    #[Test]
    public function catalog_and_preview_require_invoice_permission(): void
    {
        $this->actingAs($this->regularUser, 'backpack');
        $this->getJson($this->catalogUrl())->assertForbidden();
        $this->postJson('/admin/invoice-items/preview', [])->assertForbidden();
        auth('backpack')->logout();
        $this->getJson($this->catalogUrl())->assertUnauthorized();
        $this->postJson('/admin/invoice-items/preview', [])->assertUnauthorized();
    }

    private function catalogUrl(string $search = ''): string
    {
        return '/admin/invoice-items/catalog?' . http_build_query([
            'user_id' => $this->regularUser->id,
            'payment_currency' => 'CZK',
            'q' => $search,
        ]);
    }
}
