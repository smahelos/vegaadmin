<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Notifications\Contracts\InvoiceReminderNotifierInterface;
use App\Domain\Invoice\Notifications\DTO\InvoiceReminderPayload;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use App\Domain\Shared\Notifications\DTO\Recipient;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\Status;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\Fakes\CapturingInvoiceReminderNotifier;
use Tests\TestCase;

final class CheckInvoicePaymentStatusFeatureTest extends TestCase
{
    /** @return array<string, array{int, InvoiceReminderType}> */
    public static function dueDates(): array
    {
        return [
            'upcoming' => [3, InvoiceReminderType::UPCOMING_DUE],
            'today' => [0, InvoiceReminderType::DUE_TODAY],
            'overdue' => [-1, InvoiceReminderType::OVERDUE],
        ];
    }

    #[Test]
    #[DataProvider('dueDates')]
    public function sends_both_roles_from_the_snapshot_without_a_contact(int $days, InvoiceReminderType $type): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(17, 45));
        $notifier = $this->captureReminders();
        Invoice::factory()->create([
            'invoice_vs' => '20260001',
            'issue_date' => now()->startOfDay()->subDays(10)->addDays($days),
            'due_in' => 10,
            'payment_status_id' => Status::where('slug', InvoiceStatus::PENDING->value)->sole()->id,
            'contact_id' => null,
            'supplier_name' => 'Saved supplier',
            'supplier_email' => 'saved-supplier@example.test',
            'supplier_country' => 'DE',
            'customer_name' => 'Saved customer',
            'customer_email' => 'saved-customer@example.test',
            'customer_country' => 'SK',
        ]);

        $this->assertSame(0, Artisan::call('invoices:check-payment-status'));
        $this->assertCount(2, $notifier->sent);
        foreach ($notifier->sent as [$sentType, $payload, $recipient]) {
            $this->assertSame($type, $sentType);
            $this->assertSame('20260001', $payload->invoiceNumber);
            $this->assertSame(now()->addDays($days)->toDateString(), $payload->dueDate->format('Y-m-d'));
            $supplier = $payload->recipientType === 'supplier';
            $this->assertSame($supplier ? 'Saved supplier' : 'Saved customer', $recipient->name);
            $this->assertSame($supplier ? 'saved-supplier@example.test' : 'saved-customer@example.test', $recipient->email);
            $this->assertSame($supplier ? 'de' : 'sk', $payload->locale);
            $this->assertSame($type === InvoiceReminderType::UPCOMING_DUE ? $days : null, $payload->daysLeft);
            $this->assertSame($type === InvoiceReminderType::OVERDUE ? -$days : null, $payload->daysOverdue);
        }
    }

    #[Test]
    public function skips_empty_snapshot_emails_even_when_the_business_has_an_email(): void
    {
        $notifier = $this->captureReminders();
        $invoice = $this->dueInvoice(['supplier_email' => null, 'customer_email' => null]);
        Business::whereKey($invoice->business_id)->update(['email' => 'live-business@example.test']);

        $this->assertSame(0, Artisan::call('invoices:check-payment-status'));
        $this->assertSame([], $notifier->sent);
        $this->assertStringContainsString('Notifications sent: 0 upcoming, 0 today, 0 overdue.', Artisan::output());
    }

    #[Test]
    public function counts_only_sent_notifications_and_keeps_the_payment_status(): void
    {
        $notifier = $this->captureReminders();
        $invoice = $this->dueInvoice(['supplier_email' => null, 'customer_email' => 'one-off@example.test']);
        $status = $invoice->payment_status_id;

        $this->assertSame(0, Artisan::call('invoices:check-payment-status'));
        $this->assertCount(1, $notifier->sent);
        $this->assertStringContainsString('0 upcoming, 1 today, 0 overdue', Artisan::output());
        $this->assertSame($status, $invoice->fresh()->payment_status_id);
    }

    #[Test]
    public function skips_paid_cancelled_and_other_due_dates(): void
    {
        $notifier = $this->captureReminders();
        foreach ([InvoiceStatus::PAID, InvoiceStatus::CANCELLED] as $status) {
            $this->dueInvoice(['payment_status_id' => Status::where('slug', $status->value)->sole()->id]);
        }
        $this->dueInvoice(['due_in' => 12]);
        $this->assertSame(0, Artisan::call('invoices:check-payment-status'));
        $this->assertSame([], $notifier->sent);
    }

    #[Test]
    public function uses_custom_offsets(): void
    {
        $notifier = $this->captureReminders();
        $this->dueInvoice(['due_in' => 15, 'supplier_email' => null]);
        $this->dueInvoice(['due_in' => 8, 'supplier_email' => null]);
        $this->assertSame(0, Artisan::call('invoices:check-payment-status', ['--days-before' => 5, '--days-after' => 2]));
        $this->assertCount(2, $notifier->sent);
        $this->assertStringContainsString('1 upcoming, 0 today, 1 overdue', Artisan::output());
    }

    #[Test]
    public function rejects_invalid_offsets_without_sending(): void
    {
        $notifier = $this->captureReminders();
        $this->dueInvoice();
        foreach ([
            '0',
            '-1',
            '1.5',
            'three',
        ] as $offset) {
            $this->assertSame(2, Artisan::call('invoices:check-payment-status', ['--days-before' => $offset]));
        }
        $this->assertSame([], $notifier->sent);
    }

    #[Test]
    public function fails_when_a_reminder_cannot_be_queued(): void
    {
        $this->app->instance(InvoiceReminderNotifierInterface::class, new class implements InvoiceReminderNotifierInterface
        {
            public function send(
                InvoiceReminderType $type,
                InvoiceReminderPayload $payload,
                Recipient $recipient,
            ): void {
                throw new RuntimeException('The mail queue is unavailable');
            }
        });
        $this->dueInvoice();
        $this->assertSame(1, Artisan::call('invoices:check-payment-status'));
    }

    /** @param array<string, mixed> $overrides */
    private function dueInvoice(array $overrides = []): Invoice
    {
        return Invoice::factory()->create($overrides + [
            'issue_date' => now()->subDays(10),
            'due_in' => 10,
            'payment_status_id' => Status::where('slug', InvoiceStatus::PENDING->value)->sole()->id,
            'supplier_email' => 'supplier@example.test',
            'customer_email' => 'customer@example.test',
        ]);
    }

    private function captureReminders(): CapturingInvoiceReminderNotifier
    {
        $notifier = new CapturingInvoiceReminderNotifier;
        $this->app->instance(InvoiceReminderNotifierInterface::class, $notifier);

        return $notifier;
    }
}
