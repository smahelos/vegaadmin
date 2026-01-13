<?php

namespace App\Domain\Product\Events;

use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\Product\DTO\ProductDTO;

/**
 * Domain event fired when a product is set as default.
 * 
 * Triggered by:
 * - ProductService::createProduct() when first product is automatically set as default
 * - ProductService::setDefaultProduct() when explicitly setting product as default
 * 
 * Used for:
 * - Audit logging
 * - User preference tracking
 * - Cache invalidation
 * - Analytics tracking
 */
class ProductSetAsDefault implements DomainEvent
{
    /**
     * The type of change that occurred
     *
     * @var string
     */
    public readonly string $changeType;

    /** Occurrence timestamp for potential auditing. */
    public readonly \DateTimeImmutable $occurredOn;

    /** The ID of the user whose data changed. */
    public readonly int $userId;

    /** Event ID for tracking purposes */
    private readonly string $eventId;

    public function __construct(
        private readonly ProductDTO $product,
        private readonly ?ProductDTO $previousDefault = null,
        ?int $userId = null,
        private readonly array $metadata = [],
        string $changeType = 'general'
    ) {
        $this->userId = (int) $userId;
        $this->changeType = $changeType;
        $this->occurredOn = new \DateTimeImmutable('now');
        $this->eventId = uniqid('user_', true);
    }

    /** @inheritDoc */
    public function getEventName(): string
    {
        return static::class;
    }

    /** @inheritDoc */
    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getProduct(): ProductDTO
    {
        return $this->product;
    }

    public function getPreviousDefault(): ?ProductDTO
    {
        return $this->previousDefault;
    }

    public function getAggregateId(): int
    {
        return $this->product->id;
    }

    public function getDomain(): string
    {
        return 'product';
    }

    public function getPayload(): array
    {
        return [
            'user_id' => $this->userId,
            'change_type' => $this->changeType,
            'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s'),
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'previous_default_id' => $this->previousDefault?->id,
            'previous_default_name' => $this->previousDefault?->name,
            'is_first_product' => $this->previousDefault === null,
        ];
    }

    /** @inheritDoc */
    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    /**
     * Get any additional metadata associated with the event.
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
