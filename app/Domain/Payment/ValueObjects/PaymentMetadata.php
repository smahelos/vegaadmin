<?php

namespace App\Domain\Payment\ValueObjects;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Value object representing payment metadata with validation
 */
class PaymentMetadata
{
    private array $data;
    private Carbon $createdAt;

    /**
     * Required fields for different payment types
     */
    private const REQUIRED_FIELDS = [
        'qr_payment' => ['variable_symbol', 'message'],
        'bank_transfer' => ['variable_symbol'],
        'card_payment' => ['return_url'],
        'gopay' => ['return_url', 'notify_url'],
    ];

    /**
     * Maximum lengths for text fields
     */
    private const FIELD_LIMITS = [
        'message' => 140,
        'variable_symbol' => 10,
        'constant_symbol' => 10,
        'specific_symbol' => 10,
        'reference' => 35,
    ];

    public function __construct(array $data, string $paymentType = 'qr_payment')
    {
        $this->validateRequiredFields($data, $paymentType);
        $this->validateFieldLimits($data);
        $this->data = $this->sanitizeData($data);
        $this->createdAt = Carbon::now();
    }

    /**
     * Create from array with payment type
     */
    public static function create(array $data, string $paymentType = 'qr_payment'): self
    {
        return new self($data, $paymentType);
    }

    /**
     * Create QR payment metadata
     */
    public static function forQrPayment(
        string $variableSymbol,
        string $message,
        ?string $constantSymbol = null,
        ?string $specificSymbol = null
    ): self {
        return new self([
            'variable_symbol' => $variableSymbol,
            'message' => $message,
            'constant_symbol' => $constantSymbol,
            'specific_symbol' => $specificSymbol,
        ], 'qr_payment');
    }

    /**
     * Create bank transfer metadata
     */
    public static function forBankTransfer(
        string $variableSymbol,
        ?string $message = null,
        ?string $reference = null
    ): self {
        return new self([
            'variable_symbol' => $variableSymbol,
            'message' => $message,
            'reference' => $reference,
        ], 'bank_transfer');
    }

    /**
     * Create card payment metadata
     */
    public static function forCardPayment(
        string $returnUrl,
        ?string $notifyUrl = null,
        array $additionalData = []
    ): self {
        return new self(array_merge([
            'return_url' => $returnUrl,
            'notify_url' => $notifyUrl,
        ], $additionalData), 'card_payment');
    }

    /**
     * Get specific field value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if field exists
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Get all data
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get variable symbol
     */
    public function getVariableSymbol(): ?string
    {
        return $this->get('variable_symbol');
    }

    /**
     * Get constant symbol
     */
    public function getConstantSymbol(): ?string
    {
        return $this->get('constant_symbol');
    }

    /**
     * Get specific symbol
     */
    public function getSpecificSymbol(): ?string
    {
        return $this->get('specific_symbol');
    }

    /**
     * Get message
     */
    public function getMessage(): ?string
    {
        return $this->get('message');
    }

    /**
     * Get reference
     */
    public function getReference(): ?string
    {
        return $this->get('reference');
    }

    /**
     * Get return URL
     */
    public function getReturnUrl(): ?string
    {
        return $this->get('return_url');
    }

    /**
     * Get notify URL
     */
    public function getNotifyUrl(): ?string
    {
        return $this->get('notify_url');
    }

    /**
     * Get creation timestamp
     */
    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    /**
     * Add or update field
     */
    public function with(string $key, mixed $value): PaymentMetadata
    {
        $newData = $this->data;
        $newData[$key] = $value;
        
        // Create new instance with updated data
        $new = clone $this;
        $new->data = $this->sanitizeData($newData);
        
        return $new;
    }

    /**
     * Remove field
     */
    public function without(string $key): PaymentMetadata
    {
        $newData = $this->data;
        unset($newData[$key]);
        
        $new = clone $this;
        $new->data = $newData;
        
        return $new;
    }

    /**
     * Merge with another metadata
     */
    public function merge(PaymentMetadata $other): PaymentMetadata
    {
        $newData = array_merge($this->data, $other->data);
        
        $new = clone $this;
        $new->data = $this->sanitizeData($newData);
        
        return $new;
    }

    /**
     * Check if metadata equals another
     */
    public function equals(PaymentMetadata $other): bool
    {
        return $this->data === $other->data;
    }

    /**
     * String representation (JSON)
     */
    public function __toString(): string
    {
        return json_encode($this->data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Validate required fields for payment type
     */
    private function validateRequiredFields(array $data, string $paymentType): void
    {
        if (!isset(self::REQUIRED_FIELDS[$paymentType])) {
            throw new InvalidArgumentException("Unknown payment type: {$paymentType}");
        }

        $requiredFields = self::REQUIRED_FIELDS[$paymentType];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new InvalidArgumentException(
                    "Required field '{$field}' is missing for payment type '{$paymentType}'"
                );
            }
        }
    }

    /**
     * Validate field length limits
     */
    private function validateFieldLimits(array $data): void
    {
        foreach (self::FIELD_LIMITS as $field => $maxLength) {
            if (isset($data[$field]) && strlen($data[$field]) > $maxLength) {
                throw new InvalidArgumentException(
                    "Field '{$field}' exceeds maximum length of {$maxLength} characters"
                );
            }
        }
    }

    /**
     * Sanitize and filter data
     */
    private function sanitizeData(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue; // Skip null values
            }
            
            if (is_string($value)) {
                $value = trim($value);
                if ($value === '') {
                    continue; // Skip empty strings
                }
            }
            
            $sanitized[$key] = $value;
        }
        
        return $sanitized;
    }
}
