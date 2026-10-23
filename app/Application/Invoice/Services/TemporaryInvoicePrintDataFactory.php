<?php

namespace App\Application\Invoice\Services;

use App\Application\Invoice\Contracts\TemporaryInvoicePrintDataFactoryInterface;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\PrintableInvoiceData;
use App\Domain\Invoice\DTO\PrintableInvoiceItemData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Shared\Money\Enums\Currency;

class TemporaryInvoicePrintDataFactory implements TemporaryInvoicePrintDataFactoryInterface
{
    public function __construct(private readonly InvoiceProductWriteDataAssembler $products) {}

    public function fromArray(array $invoiceData): PrintableInvoiceData
    {
        $currency = strtoupper((string) ($invoiceData['payment_currency'] ?? 'CZK'));
        $issue = $this->toImmutable($invoiceData['issue_date'] ?? null);
        $dueIn = isset($invoiceData['due_in']) ? (int) $invoiceData['due_in'] : null;
        $dueDate = $issue && $dueIn !== null ? $issue->modify('+' . (int) $dueIn . ' days') : null;

        $products = $this->products->fromValidated($invoiceData['invoice-products']);
        $items = array_map(fn (InvoiceProductWriteData $product): PrintableInvoiceItemData => new PrintableInvoiceItemData(
            name: $product->name,
            quantity: (float) $product->quantity,
            unit: $product->unit,
            price: $product->price,
            tax_rate: (float) $product->taxRate,
            tax_amount: $product->taxAmount(),
            line_total: $product->total(),
        ), $products);
        $totals = InvoiceTotals::fromProducts(Currency::from($currency), $products);

        // Use a positive sentinel ID for temporary invoices to satisfy InvoiceId validation
        return new PrintableInvoiceData(
            id: InvoiceId::fromInt(1),
            number: $invoiceData['invoice_vs'] ?? null,
            issue_date: $issue,
            tax_point_date: $this->toImmutable($invoiceData['tax_point_date'] ?? null),
            due_date: $dueDate,
            due_in: $dueIn,
            currency: $currency,
            template: $invoiceData['template'] ?? 'default',
            invoice_logo: $invoiceData['invoice_logo'] ?? null,
            items: $items,
            subtotal: $totals->subtotal,
            total_tax: $totals->tax,
            total_amount: $totals->total(),
            supplier_name: $invoiceData['name'] ?? null,
            supplier_email: $invoiceData['email'] ?? null,
            supplier_phone: $invoiceData['phone'] ?? null,
            supplier_street: $invoiceData['street'] ?? null,
            supplier_city: $invoiceData['city'] ?? null,
            supplier_zip: $invoiceData['zip'] ?? null,
            supplier_country: $invoiceData['country'] ?? null,
            supplier_ico: $invoiceData['ico'] ?? null,
            supplier_dic: $invoiceData['dic'] ?? null,
            supplier_logo: $invoiceData['invoice_logo'] ?? null,
            account_number: $invoiceData['account_number'] ?? null,
            bank_code: $invoiceData['bank_code'] ?? null,
            bank_name: $invoiceData['bank_name'] ?? null,
            iban: $invoiceData['iban'] ?? null,
            swift: $invoiceData['swift'] ?? null,
            client_name: $invoiceData['client_name'] ?? null,
            client_email: $invoiceData['client_email'] ?? null,
            client_phone: $invoiceData['client_phone'] ?? null,
            client_street: $invoiceData['client_street'] ?? null,
            client_city: $invoiceData['client_city'] ?? null,
            client_zip: $invoiceData['client_zip'] ?? null,
            client_country: $invoiceData['client_country'] ?? null,
            client_ico: $invoiceData['client_ico'] ?? null,
            client_dic: $invoiceData['client_dic'] ?? null,
            invoice_vs: $invoiceData['invoice_vs'] ?? null,
            invoice_ks: $invoiceData['invoice_ks'] ?? null,
            invoice_ss: $invoiceData['invoice_ss'] ?? null,
            notes: $invoiceData['invoice_text'] ?? null,
        );
    }

    private function toImmutable(?string $d): ?\DateTimeImmutable
    {
        if (! $d) {
            return null;
        }
        try {
            return new \DateTimeImmutable($d);
        } catch (\Exception) {
            return null;
        }
    }
}
