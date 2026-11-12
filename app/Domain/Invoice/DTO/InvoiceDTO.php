<?php

namespace App\Domain\Invoice\DTO;

use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Shared\Money\ValueObjects\Money;

class InvoiceDTO
{
    /**
     * Read model for Invoice.
     * Keep only domain-relevant fields. Formatting happens in Application/UI.
     *
     * @param  array<int,array>  $items
     */
    public function __construct(
        public readonly InvoiceId $id,
        public readonly int $user_id,
        public readonly DocumentParties $parties,
        public readonly ?string $number,
        public readonly ?string $payment_reference,
        public readonly ?string $constant_code,
        public readonly ?string $issue_date,
        public readonly ?string $tax_point_date,
        public readonly ?int $due_in,
        public readonly Money $subtotal,
        public readonly Money $total_tax,
        public readonly Money $total_amount,
        public readonly ?int $payment_method_id,
        public readonly ?string $invoice_text,
        public readonly ?string $currency,
        public readonly ?string $template,
        public readonly array $items = [],
        public readonly ?string $created_at = null,
        public readonly ?string $status = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] instanceof InvoiceId ? $data['id'] : InvoiceId::fromInt((int) $data['id']),
            user_id: (int) ($data['user_id'] ?? 0),
            parties: $data['parties'],
            number: $data['number'] ?? null,
            payment_reference: $data['payment_reference'] ?? null,
            constant_code: $data['constant_code'] ?? null,
            issue_date: $data['issue_date'] ?? null,
            tax_point_date: $data['tax_point_date'] ?? null,
            due_in: isset($data['due_in']) ? (int) $data['due_in'] : null,
            subtotal: $data['subtotal'],
            total_tax: $data['total_tax'],
            total_amount: $data['total_amount'],
            payment_method_id: $data['payment_method_id'] ?? null,
            invoice_text: $data['invoice_text'] ?? null,
            currency: $data['currency'] ?? null,
            template: $data['template'] ?? null,
            items: $data['items'] ?? [],
            created_at: $data['created_at'] ?? null,
            status: $data['status'] ?? null,
        );
    }
}
