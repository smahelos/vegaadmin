<?php

namespace App\Domain\Product\Events;

use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\Product\DTO\ProductDTO;

/**
 * Domain event fired when a product is updated.
 * 
 * Triggered by:
 * - ProductService::updateProduct() when updating existing product
 * 
 * Used for:
 * - Audit logging
 * - Product change tracking
 * - Cache invalidation
 * - Analytics tracking
 */
class ProductUpdated implements DomainEvent
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
        private readonly array $previousData,
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

    public function getPreviousData(): array
    {
        return $this->previousData;
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
            'previous_data' => $this->previousData,
            'changes' => $this->getChanges(),
        ];
    }

    /**
     * Get list of changed fields
     */
    private function getChanges(): array
    {
        $current = [
            'name' => $this->product->name,
            'price' => $this->product->price,
            'currency' => $this->product->currency,
            'category_id' => $this->product->category_id,
            'tax_id' => $this->product->tax_id,
            'is_active' => $this->product->is_active,
            'is_default' => $this->product->is_default,
        ];
        
        $changes = [];
        
        foreach ($current as $key => $value) {
            if (isset($this->previousData[$key]) && $this->previousData[$key] !== $value) {
                $changes[$key] = [
                    'from' => $this->previousData[$key],
                    'to' => $value
                ];
            }
        }
        
        return $changes;
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
