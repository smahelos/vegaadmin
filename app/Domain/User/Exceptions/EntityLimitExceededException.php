<?php

namespace App\Domain\User\Exceptions;

use Exception;
use Throwable;

/**
 * Entity Limit Exceeded Exception
 *
 * Thrown when user attempts to create entity but has reached their limit
 */
class EntityLimitExceededException extends Exception
{
    private array $limitData;
    private string $messageKey;
    /** @var array<string,mixed> */
    private array $context;

    public function __construct(
        array $limitData,
        ?string $messageKey = null,
        array $context = [],
        ?string $message = null,
        int $code = 403,
        ?Throwable $previous = null
    ) {
        $this->limitData = $limitData;
        $this->messageKey = $messageKey ?: $this->defaultMessageKey();
        $this->context = $context ?: $this->defaultContext();
        // Keep a generic English fallback message for logs/debugging without using framework translators.
        $message = $message ?: $this->generateMessage();
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the limit data array
     */
    public function getLimitData(): array
    {
        return $this->limitData;
    }

    /**
     * Get the translation message key for this exception.
     */
    public function getMessageKey(): string
    {
        return $this->messageKey;
    }

    /**
     * Get the translation context (placeholders).
     *
     * @return array<string,mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Provide a standard array representation for API responses.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'message_key' => $this->messageKey,
            'context' => $this->context,
            'limit' => $this->limitData,
        ];
    }

    /**
     * Generate a default message based on limit data (English only, framework-agnostic)
     */
    private function generateMessage(): string
    {
        $entity = (string)($this->limitData['entity'] ?? $this->limitData['entity_type'] ?? 'entity');
        $limit = (string)($this->limitData['limit'] ?? 'unknown');
        return "You have reached the limit for {$entity} (max: {$limit}).";
    }

    /**
     * Determine a default translation key based on entity type, with a generic fallback.
     */
    private function defaultMessageKey(): string
    {
        $entity = $this->limitData['entity_type'] ?? $this->limitData['entity'] ?? null;
        return match ($entity) {
            'supplier' => 'suppliers.messages.limit_exceeded',
            'product' => 'products.messages.limit_exceeded',
            'invoice' => 'invoices.messages.limit_exceeded',
            'client' => 'clients.messages.limit_exceeded',
            default => 'messages.entity_limit_exceeded',
        };
    }

    /**
     * Build a default translation context from limit data.
     *
     * @return array<string,mixed>
     */
    private function defaultContext(): array
    {
        return [
            'entity' => $this->limitData['entity'] ?? $this->limitData['entity_type'] ?? 'entity',
            'limit' => $this->limitData['limit'] ?? null,
        ];
    }
}
