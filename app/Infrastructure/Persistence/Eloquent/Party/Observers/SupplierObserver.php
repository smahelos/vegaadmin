<?php

namespace App\Infrastructure\Persistence\Eloquent\Party\Observers;

use App\Models\Supplier;
use App\Domain\User\Events\UserDataChanged;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;

class SupplierObserver
{
    public function created(Supplier $supplier): void
    {
        if ($supplier->user_id) {
            app(EventPublisherInterface::class)->publish(new UserDataChanged((int)$supplier->user_id, 'supplier'));
        }
    }
    
    public function updated(Supplier $supplier): void
    {
        if ($supplier->isDirty(['name', 'email'])) {
            if ($supplier->user_id) {
                app(EventPublisherInterface::class)->publish(new UserDataChanged((int)$supplier->user_id, 'supplier'));
            }
        }
    }
    
    public function deleted(Supplier $supplier): void
    {
        if ($supplier->user_id) {
            app(EventPublisherInterface::class)->publish(new UserDataChanged((int)$supplier->user_id, 'supplier'));
        }
    }
}
