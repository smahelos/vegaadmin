<?php

namespace App\Domain\Invoice\Events\Handlers;

use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\Shared\Events\DomainEventHandler;
use App\Domain\Invoice\Events\InvoiceCreated;
use App\Domain\User\Contracts\UniversalLimitServiceInterface;
use App\Domain\Shared\Log\Contracts\LogInterface;

/**
 * Handler for recording entity usage when invoices are created.
 * 
 * This replaces the old Observer pattern for usage tracking.
 * 
 * Handles:
 * - InvoiceCreated: Record invoice usage
 */
class UsageRecordingHandler implements DomainEventHandler
{
    public function __construct(
        private readonly UniversalLimitServiceInterface $limitService,
        private readonly LogInterface $logger
    ) {}

    public function handle(DomainEvent $event): void
    {
        $success = match ($event->getEventName()) {
            InvoiceCreated::class => $this->limitService->recordUsage(
                $event->getPayload()['user_id'],
                'invoice',
                'count',
                null, // Let service determine best period type
                'web', // Use web guard for client/supplier creation
                1
            ),
            default => true // Return true for events we don't handle
        };

        if (!$success) {
            $this->logger->log(
                'warning',
                'Failed to record entity usage',
                [
                    'event_name' => $event->getEventName(),
                    'event_id' => $event->getEventId(),
                    'payload' => $event->getPayload(),
                ]
            );
        }
    }

    public function canHandle(DomainEvent $event): bool
    {
        return $event instanceof InvoiceCreated;
    }

    public function getPriority(): int
    {
        return 100; // High priority - usage recording should happen first
    }
}
