<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Observers;

use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\User\Events\UserDataChanged;
use App\Models\Invoice;

final class InvoiceObserver
{
    public function __construct(private readonly EventPublisherInterface $events) {}

    public function created(Invoice $invoice): void
    {
        $this->publishUserDataChanged($invoice);
    }

    public function updated(Invoice $invoice): void
    {
        if ($invoice->isDirty([
            'payment_amount',
            'payment_status_id',
            'issue_date',
            'due_in',
        ])) {
            $this->publishUserDataChanged($invoice);
        }
    }

    public function deleted(Invoice $invoice): void
    {
        $this->publishUserDataChanged($invoice);
    }

    private function publishUserDataChanged(Invoice $invoice): void
    {
        if ($invoice->user_id) {
            $this->events->publish(new UserDataChanged((int) $invoice->user_id, 'invoice'));
        }
    }
}
