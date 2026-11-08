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
}
