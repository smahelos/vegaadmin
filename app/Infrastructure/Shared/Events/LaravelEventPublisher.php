<?php

namespace App\Infrastructure\Shared\Events;

use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Shared\Events\DomainEventDispatcher;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Infrastructure adapter that publishes domain events via Laravel's event dispatcher.
 */
final class LaravelEventPublisher implements EventPublisherInterface
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly DomainEventDispatcher $domainDispatcher,
    ) {}

    public function publish(DomainEvent $event): void
    {
        // 1) Laravel listeners (InvalidateUserCache, FormDataChanged, etc.)
        $this->dispatcher->dispatch($event);
        // 2) Internal DomainEvent handlers (usage recording, audit, ...)
        // Dispatch all domain events that implement the DomainEvent interface
        $this->domainDispatcher->dispatch($event);
    }
}
