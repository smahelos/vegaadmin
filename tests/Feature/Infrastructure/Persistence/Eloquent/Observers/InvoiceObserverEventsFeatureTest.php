<?php

namespace Tests\Feature\Infrastructure\Persistence\Eloquent\Observers;

use App\Infrastructure\Persistence\Eloquent\Invoice\Observers\InvoiceObserver;
use App\Domain\User\Events\UserDataChanged;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests verifying that InvoiceObserver dispatches UserDataChanged events
 * for create, update (only when specific fields are dirty) and delete operations.
 */
class InvoiceObserverEventsFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function created_dispatches_user_data_changed_event(): void
    {
        Event::fake([UserDataChanged::class]);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'payment_amount' => 100,
        ]);

        // Observer triggers on model event automatically
        Event::assertDispatched(UserDataChanged::class, function ($event) use ($invoice) {
            return $event->userId === $invoice->user_id && $event->changeType === 'invoice';
        });
    }

    #[Test]
    public function updated_dispatches_event_only_when_watched_fields_dirty(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'payment_amount' => 100,
            'due_in' => 14,
        ]);

        // Case 1: update non-watched field (invoice_text) should NOT dispatch
        Event::fake([UserDataChanged::class]);
        $invoice->invoice_text = 'Just a note';
        $invoice->save();
        Event::assertNotDispatched(UserDataChanged::class);

        // Case 2: update watched field (payment_amount)
//        Event::fake([UserDataChanged::class]);
//        $invoice->payment_amount = 150; // dirty field watched by observer
//        $invoice->save();
        Event::assertDispatched(UserDataChanged::class, function ($event) use ($invoice) {
            return $event->userId === $invoice->user_id && $event->changeType === 'invoice';
        });
    }

    #[Test]
    public function deleted_dispatches_user_data_changed_event(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'payment_amount' => 50,
        ]);

        Event::fake([UserDataChanged::class]);
        $invoice->delete();

        Event::assertDispatched(UserDataChanged::class, function ($event) use ($invoice) {
            return $event->userId === $invoice->user_id && $event->changeType === 'invoice';
        });
    }
}
