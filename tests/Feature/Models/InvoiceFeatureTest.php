<?php

namespace Tests\Feature\Models;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\User;
use Carbon\CarbonImmutable;
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
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create();

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
        ]);
    }

    #[Test]
    public function it_belongs_to_user(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(['user_id' => $user->id]);

        $relation = $invoice->user();
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals($user->id, $invoice->user->id);
    }

    #[Test]
    public function it_belongs_to_payment_method(): void
    {
        $paymentMethodQuery = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value);
        $paymentMethod = $paymentMethodQuery->firstOrFail();
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(['payment_method_id' => $paymentMethod->id]);

        $relation = $invoice->paymentMethod();
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals($paymentMethod->id, $invoice->paymentMethod->id);
    }

    #[Test]
    public function it_belongs_to_many_products(): void
    {
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create();

        $relation = $invoice->products();
        $this->assertInstanceOf(BelongsToMany::class, $relation);
    }

    #[Test]
    public function it_has_many_invoice_products(): void
    {
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create();

        $relation = $invoice->invoiceProducts();
        $this->assertInstanceOf(HasMany::class, $relation);
    }

    #[Test]
    public function due_date_is_the_issue_date_plus_due_days(): void
    {
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create([
            'issue_date' => '2025-01-01',
            'due_in' => 30,
        ]);

        $this->assertSame(
            '2025-01-31',
            $invoice->due_date->format('Y-m-d'),
        );
    }

    #[Test]
    public function due_date_is_the_issue_date_with_zero_due_days(): void
    {
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create([
            'issue_date' => '2025-01-01',
            'due_in' => 0,
        ]);

        $this->assertSame(
            '2025-01-01',
            $invoice->due_date->format('Y-m-d'),
        );
    }

    #[Test]
    public function unpaid_invoice_is_overdue_the_day_after_its_due_date(): void
    {
        $invoice = $this->invoiceDueDaysAgo(InvoiceStatus::PENDING, 1);

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
    public function payment_amount_is_stored_and_read_back_without_a_float(): void
    {
        // A float holds this amount as 9007199254740992
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(
            [
                'payment_amount' => '9007199254740993.01',
                'payment_currency' => 'CZK',
            ],
        );

        $invoice->refresh();
        $this->assertSame('9007199254740993.01', $invoice->payment_amount);
    }

    private function invoiceDueDaysAgo(InvoiceStatus $status, int $days): Invoice
    {
        $invoiceFactory = Invoice::factory();

        $now = CarbonImmutable::now();
        $issueDate = $now->subDays(14 + $days);

        return $invoiceFactory->create(
            [
                'payment_status' => $status->value,
                'issue_date' => $issueDate->toDateString(),
                'due_in' => 14,
            ],
        );
    }
}
