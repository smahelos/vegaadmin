<?php

namespace App\Domain\Invoice\DTO;

use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Money\ValueObjects\Money;

/**
 * Domain DTO with all information needed to render an invoice, without presentation concerns.
 */
class PrintableInvoiceData
{
    /**
     * @param array<int, PrintableInvoiceItemData> $items
     */
    public function __construct(
        public readonly InvoiceId $id,
        public readonly ?string $number,
        public readonly ?\DateTimeImmutable $issue_date,
        public readonly ?\DateTimeImmutable $tax_point_date,
        public readonly ?\DateTimeImmutable $due_date,
        public readonly ?int $due_in,
        public readonly string $currency,
        public readonly ?string $template,
        public readonly ?string $invoice_logo,
        public readonly array $items,
        public readonly Money $subtotal,
        public readonly Money $total_tax,
        public readonly Money $total_amount,
        // Supplier
        public readonly ?string $supplier_name,
        public readonly ?string $supplier_email,
        public readonly ?string $supplier_phone,
        public readonly ?string $supplier_street,
        public readonly ?string $supplier_city,
        public readonly ?string $supplier_zip,
        public readonly ?string $supplier_country,
        public readonly ?string $supplier_ico,
        public readonly ?string $supplier_dic,
        public readonly ?string $supplier_logo,
        public readonly ?string $account_number,
        public readonly ?string $bank_code,
        public readonly ?string $bank_name,
        public readonly ?string $iban,
        public readonly ?string $swift,
        // Client
        public readonly ?string $client_name,
        public readonly ?string $client_email,
        public readonly ?string $client_phone,
        public readonly ?string $client_street,
        public readonly ?string $client_city,
        public readonly ?string $client_zip,
        public readonly ?string $client_country,
        public readonly ?string $client_ico,
        public readonly ?string $client_dic,
        // Payment identification
        public readonly ?string $invoice_vs,
        public readonly ?string $invoice_ks,
        public readonly ?string $invoice_ss,
        public readonly ?string $notes,
    ) {}
}
