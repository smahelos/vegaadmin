<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Persistence\Eloquent\Observers;

use App\Domain\User\Events\UserDataChanged;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InvoiceObserverFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function changing_the_invoice_text_keeps_the_items(): void
    {
        $invoice = Invoice::factory()->create(['invoice_text' => null]);
        InvoiceProduct::factory()->count(2)->create(['invoice_id' => $invoice->id]);

        $invoice->update(['invoice_text' => 'Děkujeme za spolupráci']);

        $this->assertSame(2, InvoiceProduct::where('invoice_id', $invoice->id)->count());
    }

    #[Test]
    public function creating_an_invoice_publishes_user_data_changed(): void
    {
        Event::fake([UserDataChanged::class]);

        $invoice = Invoice::factory()->create();

        Event::assertDispatched(
            UserDataChanged::class,
            fn (UserDataChanged $event): bool => $event->userId === (int) $invoice->user_id,
        );
    }

    #[Test]
    public function changing_the_amount_publishes_user_data_changed(): void
    {
        $invoice = Invoice::factory()->create();
        Event::fake([UserDataChanged::class]);

        $invoice->update(['payment_amount' => 4321]);

        Event::assertDispatched(UserDataChanged::class);
    }

    #[Test]
    public function changing_only_the_invoice_text_publishes_nothing(): void
    {
        $invoice = Invoice::factory()->create();
        Event::fake([UserDataChanged::class]);

        $invoice->update(['invoice_text' => 'Poznámka']);

        Event::assertNotDispatched(UserDataChanged::class);
    }
}
