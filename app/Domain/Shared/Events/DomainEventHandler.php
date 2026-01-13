<?php

namespace App\Domain\Shared\Events;

use App\Domain\Shared\Events\Contracts\DomainEvent;

/**
 * Interface for event handlers that process domain events.
 * 
 * Handlers should be lightweight and focused on a single responsibility.
 * Complex business logic should be delegated to domain services.
 */
interface DomainEventHandler
{
    /**
     * Handle the domain event.
     * 
     * @param DomainEvent $event The event to handle
     * @return void
     * @throws \Exception if handling fails (will be logged by dispatcher)
     */
    public function handle(DomainEvent $event): void;

    /**
     * Check if this handler can handle the given event.
     * By default, checks if event class matches handler's supported events.
     * 
     * @param DomainEvent $event
     * @return bool
     */
    public function canHandle(DomainEvent $event): bool;

    /**
     * Get priority for this handler (higher = runs first).
     * Used for ordering when multiple handlers listen to same event.
     * 
     * @return int Priority (default: 0)
     */
    public function getPriority(): int;
}
