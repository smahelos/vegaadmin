<?php

namespace App\Domain\Product\Events;

use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\Product\DTO\ProductDTO;

/**
 * Domain event fired when a new product is created.
 * 
 * Triggered by:
 * - ProductService::createProduct() when creating new product
 * 
 * Used for:
 * - Usage limit recording
 * - Audit logging
 * - Product analytics tracking
 * - Cache invalidation
 */
class ProductCreated implements DomainEvent
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
        ?int $userId = null,
        private readonly array $metadata = [],
        string $changeType = 'general'
    ) {
        $this->userId = (int) $userId;
        $this->changeType = $changeType;
        $this->occurredOn = new \DateTimeImmutable('now');
        $this->eventId = uniqid('user_', true);
    }

    public function getProduct(): ProductDTO
    {
        return $this->product;
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
            'product_price' => $this->product->price,
            'product_currency' => $this->product->currency,
            'product_category_id' => $this->product->category_id,
            'product_tax_id' => $this->product->tax_id,
            'is_default' => $this->product->is_default,
            'is_active' => $this->product->is_active,
            'has_image' => !empty($this->product->image),
        ];
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
