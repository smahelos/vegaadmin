<?php

namespace App\Domain\Shared\Events\Contracts;

/**
 * Marker interface for Domain Events.
 * Keeps Domain independent of framework specifics.
 */
interface DomainEvent 
{
    /**
     * Get the event name (typically the class name)
     */
    public function getEventName(): string;

    /**
     * Get unique event ID for tracking
     */
    public function getEventId(): string;

    /**
     * Get event payload data
     */
    public function getPayload(): array;

    /**
     * Get when the event occurred
     */
    public function getOccurredOn(): \DateTimeImmutable;

    /**
     * Get event metadata for debugging and serialization
     */
    public function getMetadata(): array;
}
