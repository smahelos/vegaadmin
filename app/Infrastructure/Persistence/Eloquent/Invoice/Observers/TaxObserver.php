<?php

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Observers;

use App\Models\Tax;
use App\Domain\Shared\Events\FormDataChanged;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;

class TaxObserver
{
    public function created(Tax $tax): void
    {
        $userId = auth()->id() ?? 0;
        app(EventPublisherInterface::class)->publish(new FormDataChanged($userId, 'taxes'));
    }
    public function updated(Tax $tax): void
    {
        if ($tax->isDirty(['name', 'rate'])) {
            $userId = auth()->id() ?? 0;
            app(EventPublisherInterface::class)->publish(new FormDataChanged($userId, 'taxes'));
        }
    }
    public function deleted(Tax $tax): void
    {
        $userId = auth()->id() ?? 0;
        app(EventPublisherInterface::class)->publish(new FormDataChanged($userId, 'taxes'));
    }
}
