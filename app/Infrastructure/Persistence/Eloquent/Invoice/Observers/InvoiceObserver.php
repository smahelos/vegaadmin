<?php

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Observers;

use App\Models\Invoice;
use App\Domain\Invoice\Contracts\InvoiceProductSyncServiceInterface;
use App\Domain\User\Events\UserDataChanged;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;

class InvoiceObserver
{
    public function __construct(private readonly InvoiceProductSyncServiceInterface $syncService) {}

    /** Handle the Invoice "created" event. */
    public function created(Invoice $invoice): void
    {
        $this->syncService->syncProductsFromJson(InvoiceId::fromInt($invoice->id));
        if ($invoice->user_id) {
            app(EventPublisherInterface::class)->publish(new UserDataChanged((int)$invoice->user_id, 'invoice'));
        }
    }

    /** Handle the Invoice "updated" event. */
    public function updated(Invoice $invoice): void
    {
        if ($invoice->isDirty('invoice_text')) {
            $this->syncService->syncProductsFromJson(InvoiceId::fromInt($invoice->id));
        }
        if ($invoice->isDirty(['payment_amount', 'payment_status_id', 'issue_date', 'due_in'])) {
            if ($invoice->user_id) {
                app(EventPublisherInterface::class)->publish(new UserDataChanged((int)$invoice->user_id, 'invoice'));
            }
        }
    }

    /** Handle the Invoice "deleted" event. */
    public function deleted(Invoice $invoice): void
    {
        if ($invoice->user_id) {
            app(EventPublisherInterface::class)->publish(new UserDataChanged((int)$invoice->user_id, 'invoice'));
        }
    }
}
