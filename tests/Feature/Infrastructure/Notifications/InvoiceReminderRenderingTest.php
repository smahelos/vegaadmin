<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Notifications;

use App\Application\Invoice\Services\InvoiceReminderApplicationService;
use App\Domain\Invoice\Notifications\Enums\InvoiceReminderType;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Time\Contracts\DateFormatterInterface;
use App\Models\Business;
use App\Models\Invoice;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InvoiceReminderRenderingTest extends TestCase
{
    /** @return iterable<string, array{InvoiceReminderType, string}> */
    public static function reminders(): iterable
    {
        foreach (InvoiceReminderType::cases() as $type) {
            foreach ([
                'cs',
                'de',
                'en',
                'sk',
            ] as $locale) {
                yield $type->value . '-' . $locale => [$type, $locale];
            }
        }
    }

    #[Test]
    #[DataProvider('reminders')]
    public function renders_both_recipients_from_stored_document_data(InvoiceReminderType $type, string $locale): void
    {
        Notification::fake();
        $invoice = Invoice::factory()->create([
            'invoice_vs' => '20260042',
            'contact_id' => null,
            'supplier_name' => 'Stored issuing business',
            'supplier_email' => 'issuer@example.test',
            'customer_name' => 'Stored one-off customer',
            'customer_email' => 'customer@example.test',
            'payment_amount' => '1234.56',
            'payment_currency' => 'EUR',
        ]);
        Business::whereKey($invoice->business_id)->update(['name' => 'Changed source business', 'archived_at' => now()]);

        app(InvoiceReminderApplicationService::class)->sendForInvoice(InvoiceId::fromInt($invoice->id), [$type]);

        $notifications = collect(Notification::sentNotifications())->flatten(3)->all();
        $this->assertCount(2, $notifications);
        foreach ($notifications as $sent) {
            $notification = $sent['notification'];
            $notification->locale($locale);
            $message = $notification->toMail($sent['notifiable']);
            $html = (string) $message->render();
            $this->assertStringContainsString('20260042', $html);
            $this->assertStringContainsString(app(MoneyFormatterInterface::class)->formatWithCode(Money::fromString('1234.56', 'EUR'), $locale), html_entity_decode($html));
            $dueDate = $invoice->issue_date->toDateTimeImmutable()->modify('+' . $invoice->due_in . ' days');
            $this->assertStringContainsString(app(DateFormatterInterface::class)->formatDate($dueDate, $locale), $html);
            $this->assertStringContainsString('Stored issuing business', $html);
            $this->assertStringContainsString('Stored one-off customer', $html);
            $this->assertStringNotContainsString('Changed source business', $html);
            $this->assertStringNotContainsString('invoices.reminders.', $html);
        }
    }
}
