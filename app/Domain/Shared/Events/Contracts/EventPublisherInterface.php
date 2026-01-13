<?php

namespace App\Domain\Shared\Events\Contracts;

/**
 * Publishes domain events. Domain depends on this interface only.
 */
interface EventPublisherInterface
{
    /**
     * Publish a domain event to the underlying event bus.
     */
    public function publish(DomainEvent $event): void;
}
