<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Events;

use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Shared\Events\DomainEventDispatcher;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseTransactionsManager;

/**
 * Delivers an event once the surrounding database transaction commits, or at once outside a transaction.
 * A rolled-back transaction delivers nothing.
 */
final class LaravelEventPublisher implements EventPublisherInterface
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly DomainEventDispatcher $domainDispatcher,
        private readonly DatabaseTransactionsManager $transactions,
    ) {}

    public function publish(DomainEvent $event): void
    {
        $this->transactions->addCallback(function () use ($event): void {
            // Laravel listeners (InvalidateUserCache, FormDataChanged, ...)
            $this->dispatcher->dispatch($event);
            // Domain event handlers (usage recording, audit, ...)
            $this->domainDispatcher->dispatch($event);
        });
    }
}
