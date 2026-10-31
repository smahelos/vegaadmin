<?php

namespace Tests\Feature\Models;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceFeatureTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_factory(): void
    {
        $invoice = Invoice::factory()->create();

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
        ]);
    }

    #[Test]
    public function it_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user->id]);

        $relation = $invoice->user();
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals($user->id, $invoice->user->id);
    }

    #[Test]
    public function it_belongs_to_payment_method(): void
    {
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
        $invoice = Invoice::factory()->create(['payment_method_id' => $paymentMethod->id]);

        $relation = $invoice->paymentMethod();
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals($paymentMethod->id, $invoice->paymentMethod->id);
    }

    #[Test]
    public function it_belongs_to_payment_status(): void
    {
        $status = Status::factory()->create();
        $invoice = Invoice::factory()->create(['payment_status_id' => $status->id]);

        $relation = $invoice->paymentStatus();
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals($status->id, $invoice->paymentStatus->id);
    }

    #[Test]
    public function it_belongs_to_many_products(): void
    {
        $invoice = Invoice::factory()->create();

        $relation = $invoice->products();
        $this->assertInstanceOf(BelongsToMany::class, $relation);
    }

    #[Test]
    public function it_has_many_invoice_products(): void
    {
        $invoice = Invoice::factory()->create();

        $relation = $invoice->invoiceProducts();
        $this->assertInstanceOf(HasMany::class, $relation);
    }

    #[Test]
    public function get_payment_status_name_attribute_returns_translated_name(): void
    {
        $status = Status::factory()->create(['name' => 'Paid']);
        $invoice = Invoice::factory()->create(['payment_status_id' => $status->id]);

        $statusName = $invoice->getPaymentStatusNameAttribute();

        $this->assertEquals('Paid', $statusName);
    }

    #[Test]
    public function payment_status_name_is_translated_by_status_slug(): void
    {
        $status = Status::where('slug', 'partially-paid')->first()
            ?? Status::factory()->create(['slug' => 'partially-paid', 'name' => 'Částečně uhrazená']);
        $invoice = Invoice::factory()->create(['payment_status_id' => $status->id]);

        $this->assertSame(__('statuses.statuses.partially-paid'), $invoice->payment_status_name);
    }

    #[Test]
    public function get_payment_status_name_attribute_returns_fallback_when_no_status(): void
    {
        $invoice = Invoice::factory()->create(['payment_status_id' => null]);

        $statusName = $invoice->getPaymentStatusNameAttribute();

        $this->assertIsString($statusName);
    }

    #[Test]
    public function get_status_color_class_attribute_returns_status_color(): void
    {
        $status = Status::factory()->create(['color' => 'green']);
        $invoice = Invoice::factory()->create(['payment_status_id' => $status->id]);

        $colorClass = $invoice->getStatusColorClassAttribute();

        $this->assertEquals('green', $colorClass);
    }

    #[Test]
    public function get_status_color_class_attribute_returns_gray_when_status_has_no_color(): void
    {
        $status = Status::factory()->create(['color' => '']);
        $invoice = Invoice::factory()->create(['payment_status_id' => $status->id]);

        $this->assertSame('gray', $invoice->status_color_class);
    }

    #[Test]
    public function get_status_color_class_attribute_returns_gray_when_no_status(): void
    {
        $invoice = Invoice::factory()->create(['payment_status_id' => null]);

        $colorClass = $invoice->getStatusColorClassAttribute();

        $this->assertEquals('gray', $colorClass);
    }

    #[Test]
    public function due_date_is_the_issue_date_plus_due_days(): void
    {
        $invoice = Invoice::factory()->create([
            'issue_date' => '2025-01-01',
            'due_in' => 30,
        ]);

        $this->assertSame('2025-01-31', $invoice->due_date->format('Y-m-d'));
    }

    #[Test]
    public function due_date_is_the_issue_date_with_zero_due_days(): void
    {
        $invoice = Invoice::factory()->create([
            'issue_date' => '2025-01-01',
            'due_in' => 0,
        ]);

        $this->assertSame('2025-01-01', $invoice->due_date->format('Y-m-d'));
    }

    #[Test]
    public function paid_reflects_the_paid_status(): void
    {
        $this->assertTrue($this->invoiceDueDaysAgo(InvoiceStatus::PAID, 0)->paid);
        $this->assertFalse($this->invoiceDueDaysAgo(InvoiceStatus::PARTIALLY_PAID, 0)->paid);
    }

    #[Test]
    public function unpaid_invoice_is_overdue_the_day_after_its_due_date(): void
    {
        $invoice = $this->invoiceDueDaysAgo(InvoiceStatus::PARTIALLY_PAID, 1);

        $this->assertTrue($invoice->overdue);
    }

    #[Test]
    public function unpaid_invoice_is_not_overdue_on_its_due_date(): void
    {
        $invoice = $this->invoiceDueDaysAgo(InvoiceStatus::PENDING, 0);

        $this->assertFalse($invoice->overdue);
    }

    #[Test]
    public function paid_invoice_is_never_overdue(): void
    {
        $invoice = $this->invoiceDueDaysAgo(InvoiceStatus::PAID, 10);

        $this->assertFalse($invoice->overdue);
    }

    #[Test]
    public function get_invoice_products_data_attribute_returns_array(): void
    {
        $invoice = Invoice::factory()->create();

        InvoiceProduct::factory()->create([
            'invoice_id' => $invoice->id,
            'name' => 'Test Product',
            'quantity' => 2,
            'price' => 100.00,
        ]);

        $invoiceProductsData = $invoice->getInvoiceProductsDataAttribute();

        $this->assertIsArray($invoiceProductsData);
        $this->assertCount(1, $invoiceProductsData);
        $this->assertEquals('Test Product', $invoiceProductsData[0]['name']);
        $this->assertEquals(2, $invoiceProductsData[0]['quantity']);
        $this->assertEquals(100.00, $invoiceProductsData[0]['price']);
    }

    #[Test]
    public function payment_amount_money_attribute_returns_money_value_object(): void
    {
        $invoice = Invoice::factory()->create([
            'payment_amount' => 1234.56,
            'payment_currency' => 'eur',
        ]);

        $money = $invoice->payment_amount_money; // Money VO
        $this->assertEquals('1234.56', $money->getAmount());
        $this->assertEquals('EUR', $money->getCurrency());
    }

    #[Test]
    public function subtotal_money_attribute_aggregates_products_correctly(): void
    {
        $invoice = Invoice::factory()->create([
            'payment_currency' => 'czk',
        ]);

        // Two products: (100 * 2) + (50 * 3) = 350.00
        InvoiceProduct::factory()->create([
            'invoice_id' => $invoice->id,
            'price' => 100.00,
            'quantity' => 2,
            'currency' => 'CZK',
        ]);
        InvoiceProduct::factory()->create([
            'invoice_id' => $invoice->id,
            'price' => 50.00,
            'quantity' => 3,
            'currency' => 'CZK',
        ]);

        $money = $invoice->subtotal_money; // Money VO
        $this->assertEquals('350', $money->getAmount());
        $this->assertEquals('CZK', $money->getCurrency());
    }

    #[Test]
    public function total_tax_money_attribute_sums_tax_correctly(): void
    {
        $invoice = Invoice::factory()->create([
            'payment_currency' => 'usd',
        ]);

        // The stored tax of each line is summed as it is
        InvoiceProduct::create([
            'invoice_id' => $invoice->id,
            'product_id' => Product::factory()->create()->id,
            'name' => 'P1',
            'quantity' => 1,
            'price' => 100.00,
            'currency' => 'USD',
            'unit' => 'pieces',
            'is_custom_product' => false,
            'tax_rate' => 21,
            'tax_amount' => '21.00',
            'total_price' => '121.00',
        ]);
        InvoiceProduct::create([
            'invoice_id' => $invoice->id,
            'product_id' => Product::factory()->create()->id,
            'name' => 'P2',
            'quantity' => 2,
            'price' => 50.00,
            'currency' => 'USD',
            'unit' => 'pieces',
            'is_custom_product' => false,
            'tax_rate' => 12,
            'tax_amount' => '12.00',
            'total_price' => '112.00',
        ]);

        $money = $invoice->total_tax_money;
        $this->assertEquals('33', $money->getAmount());
        $this->assertEquals('USD', $money->getCurrency());
    }

    private function invoiceDueDaysAgo(InvoiceStatus $status, int $days): Invoice
    {
        return Invoice::factory()->create([
            'payment_status_id' => Status::where('slug', $status->value)->firstOrFail()->id,
            'issue_date' => now()->subDays(14 + $days)->toDateString(),
            'due_in' => 14,
        ]);
    }
}
