<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Events;

use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Shared\Events\DomainEventDispatcher;
use App\Domain\Shared\Log\Contracts\LogInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseTransactionsManager;
use Throwable;

/**
 * Domain event handlers write to the same database (usage counters), so they run inside the surrounding
 * transaction and commit or roll back with it. Laravel listeners have effects outside the database (cache),
 * so they run once the transaction commits, or at once outside a transaction, and not at all on a rollback.
 */
final class LaravelEventPublisher implements EventPublisherInterface
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly DomainEventDispatcher $domainDispatcher,
        private readonly DatabaseTransactionsManager $transactions,
        private readonly LogInterface $log,
    ) {}

    public function publish(DomainEvent $event): void
    {
        $this->domainDispatcher->dispatch($event);

        $this->transactions->addCallback(function () use ($event): void {
            // The use case is committed by now: a failing listener must not turn it into an error the user
            // retries, so it is logged like a failing domain handler in DomainEventDispatcher
            try {
                $this->dispatcher->dispatch($event);
            } catch (Throwable $e) {
                $this->log->log(
                    'error',
                    'Domain event listener failed',
                    [
                        'event' => $event->getEventName(),
                        'exception' => $e,
                    ],
                );
            }
        });
    }
}
