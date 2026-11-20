<?php

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Observers;

use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Shared\Events\FormDataChanged;
use App\Models\Tax;

class TaxObserver
{
    public function __construct(private readonly EventPublisherInterface $events) {}

    public function created(Tax $tax): void
    {
        $auth = auth();
        $userId = $auth->id() ?? 0;
        $this->events->publish(
            new FormDataChanged($userId, 'taxes'),
        );
    }

    public function updated(Tax $tax): void
    {
        if ($tax->isDirty(['name', 'rate'])) {
            $auth = auth();
            $userId = $auth->id() ?? 0;
            $this->events->publish(
                new FormDataChanged($userId, 'taxes'),
            );
        }
    }

    public function deleted(Tax $tax): void
    {
        $auth = auth();
        $userId = $auth->id() ?? 0;
        $this->events->publish(
            new FormDataChanged($userId, 'taxes'),
        );
    }
}
