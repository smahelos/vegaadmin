<?php

namespace App\Domain\Shared\Events;

use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\Shared\Log\Contracts\LogInterface;

/**
 * Centralized domain event dispatcher for all domains.
 * 
 * Features:
 * - Event registration and dispatching
 * - Handler priority ordering
 * - Error handling with logging
 * - Event filtering and debugging
 * - Performance monitoring
 */
class DomainEventDispatcher
{
    /** @var array<string, DomainEventHandler[]> */
    private array $handlers = [];
    
    /** @var DomainEvent[] */
    private array $dispatchedEvents = [];
    
    /** @var bool */
    private bool $isDispatching = false;

    public function __construct(
        private readonly LogInterface $logger
    ) {}

    /**
     * Register an event handler for specific event types.
     * 
     * @param string|string[] $eventTypes Event class names
     * @param DomainEventHandler $handler Handler instance
     */
    public function register($eventTypes, DomainEventHandler $handler): void
    {
        $eventTypes = is_array($eventTypes) ? $eventTypes : [$eventTypes];
        
        foreach ($eventTypes as $eventType) {
            if (!isset($this->handlers[$eventType])) {
                $this->handlers[$eventType] = [];
            }
            
            $this->handlers[$eventType][] = $handler;
            
            // Sort by priority (higher first)
            usort($this->handlers[$eventType], function ($a, $b) {
                return $b->getPriority() <=> $a->getPriority();
            });
        }
    }

    /**
     * Dispatch a domain event to all registered handlers.
     * 
     * @param DomainEvent $event
     */
    public function dispatch(DomainEvent $event): void
    {
        $startTime = microtime(true);
        $eventType = $event->getEventName();

        // Store event for debugging and testing
        $this->dispatchedEvents[] = $event;
        
        // Prevent infinite loops during event handling
        if ($this->isDispatching) {
            $this->logger->log(
                'warning', 
                'Domain event dispatching is already in progress, skipping nested dispatch', 
                [
                    'event_id' => $event->getEventId(),
                    'event_type' => $eventType,
                ]
            );
            return;
        }

        $this->isDispatching = true;
        $handlerCount = 0;
        $errorCount = 0;

        try {
            // Get handlers for this specific event type
            $handlers = $this->handlers[$eventType] ?? [];
            
            // Also get handlers registered for parent classes/interfaces
            foreach ($this->handlers as $registeredType => $registeredHandlers) {
                if ($registeredType !== $eventType && is_a($event, $registeredType)) {
                    $handlers = array_merge($handlers, $registeredHandlers);
                }
            }

            foreach ($handlers as $handler) {
                if (!$handler->canHandle($event)) {
                    continue;
                }

                // $handlerStartTime = microtime(true);
                
                try {
                    $handler->handle($event);
                    $handlerCount++;

                    // $this->logger->log(
                    //     'debug', 
                    //     'Domain event handler executed successfully', 
                    //     [
                    //         'event_id' => $event->getEventId(),
                    //         'handler' => get_class($handler),
                    //         'execution_time' => round((microtime(true) - $handlerStartTime) * 1000, 2) . 'ms',
                    //     ]
                    // );

                } catch (\Exception $e) {
                    $errorCount++;

                    $this->logger->log(
                        'error',
                        'Domain event handler failed',
                        [
                            'event_id' => $event->getEventId(),
                            'event_type' => $eventType,
                            'handler' => get_class($handler),
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]
                    );
                    
                    // Continue with other handlers despite this failure
                }
            }
            
        } finally {
            $this->isDispatching = false;
            
            $totalTime = round((microtime(true) - $startTime) * 1000, 2);

            // $this->logger->log(
            //     'info', 
            //     'Domain event dispatching completed', 
            //     [
            //         'event_id' => $event->getEventId(),
            //         'event_type' => $eventType,
            //         'handlers_executed' => $handlerCount,
            //         'handlers_failed' => $errorCount,
            //         'total_time' => $totalTime . 'ms',
            //     ]
            // );
        }
    }

    /**
     * Get all dispatched events (useful for testing).
     */
    public function getDispatchedEvents(): array
    {
        return $this->dispatchedEvents;
    }

    /**
     * Clear dispatched events history (useful for testing).
     */
    public function clearDispatchedEvents(): void
    {
        $this->dispatchedEvents = [];
    }

    /**
     * Get registered handlers for debugging.
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /**
     * Check if any handlers are registered for event type.
     */
    public function hasHandlers(string $eventType): bool
    {
        return !empty($this->handlers[$eventType]);
    }
}
