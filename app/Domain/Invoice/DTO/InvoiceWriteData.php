<?php

namespace App\Domain\Invoice\DTO;

use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Shared\Money\ValueObjects\Money;

/**
 * Immutable write data for Invoice mutations.
 * Null means "do not change" for updates.
 */
class InvoiceWriteData
{
    public function __construct(
        // Required parameters first (no default values)
        public readonly ?string $payment_reference,
        public readonly ?string $constant_code,
        public readonly ?string $tax_point_date,

        // Optional parameters with default values
        public readonly ?DocumentParties $parties = null,
        public readonly ?string $number = null,
        public readonly ?string $issue_date = null,
        public readonly ?int $due_in = null,
        public readonly ?string $currency = null,
        public readonly ?string $template = null,
        public readonly ?string $note = null,
        // public readonly ?array $meta = null,
        public readonly ?int $user_id = null,
        public readonly ?Money $total_amount = null,
        public readonly ?int $payment_method_id = null,
        public readonly ?string $invoice_text = null,
        public readonly array $items = [],
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null,
        public readonly ?string $status = null,
    ) {}

    public static function fromArray(array $data, ?DocumentParties $parties = null): self
    {
        // Normalize currency from multiple accepted shapes
        $currency = $data['currency']
            ?? $data['payment_currency']
            ?? null;
        if (is_string($currency)) {
            $currency = strtoupper($currency);
        }

        return new self(
            parties: $parties,
            number: $data['number'] ?? ($data['invoice_vs'] ?? null),
            issue_date: $data['issue_date'] ?? null,
            due_in: isset($data['due_in']) ? (int) $data['due_in'] : null,
            currency: $currency,
            template: $data['template'] ?? null,
            note: $data['note'] ?? ($data['invoice_text'] ?? null),
            // meta: $data['meta'] ?? null,
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,

            payment_reference: $data['payment_reference'] ?? ($data['invoice_ss'] ?? null),
            constant_code: $data['constant_code'] ?? ($data['invoice_ks'] ?? null),
            tax_point_date: $data['tax_point_date'] ?? null,
            total_amount: $data['total_amount'] ?? null,
            payment_method_id: isset($data['payment_method_id']) ? (int) $data['payment_method_id'] : null,
            invoice_text: $data['invoice_text'] ?? null,
            items: $data['items'] ?? [],
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null,
            status: $data['status'] ?? (int) $data['payment_status_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        $properties = get_object_vars($this);

        return array_filter($properties, fn ($v) => $v !== null);
    }
}
