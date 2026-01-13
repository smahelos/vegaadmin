<?php

namespace App\Domain\Invoice\Services;

use App\Domain\Invoice\Contracts\InvoicePrintDataBuilderInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\PrintableInvoiceData;
use App\Domain\Invoice\DTO\PrintableInvoiceItemData;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Party\Contracts\SupplierDtoReadRepositoryInterface;
use App\Domain\Party\Contracts\ClientDtoReadRepositoryInterface;
use App\Domain\Party\ValueObjects\PartyId;

/**
 * Builds domain-clean printable data for invoices from InvoiceDTO.
 * No Eloquent, no presentation formatting, no IO.
 */
class InvoicePrintDataBuilder implements InvoicePrintDataBuilderInterface
{
    public function __construct(
        private readonly SupplierDtoReadRepositoryInterface $supplierRepo,
        private readonly ClientDtoReadRepositoryInterface $clientRepo,
    ) {}

    public function build(InvoiceDTO $invoice): PrintableInvoiceData
    {
        // Compute dates
        $issue = $this->toImmutableDate($invoice->issue_date);
        $taxPoint = $this->toImmutableDate($invoice->created_at ?? null); // if tax_point_date is elsewhere, adjust later
        $due = $this->computeDueDate($issue, $invoice->due_in);

        // Items: expect array items with name, quantity, unit, price, tax_rate, tax_amount, total_price
        $items = [];
        foreach ($invoice->items as $item) {
            // Accept either primitive array or already mapped money objects
            $currency = strtoupper($item['currency'] ?? ($invoice->currency ?? 'CZK'));
            $price = $item['price'] instanceof Money ? $item['price'] : Money::fromFloat((float)($item['price'] ?? 0), $currency);
            $taxAmount = $item['tax_amount'] instanceof Money ? $item['tax_amount'] : Money::fromFloat((float)($item['tax_amount'] ?? 0), $currency);
            $lineTotal = $item['total_price'] instanceof Money ? $item['total_price'] : Money::fromFloat((float)($item['total_price'] ?? 0), $currency);
            $items[] = new PrintableInvoiceItemData(
                name: (string)($item['name'] ?? ''),
                quantity: isset($item['quantity']) ? (float)$item['quantity'] : 1.0,
                unit: $item['unit'] ?? null,
                price: $price,
                tax_rate: isset($item['tax_rate']) ? (float)$item['tax_rate'] : 0.0,
                tax_amount: $taxAmount,
                line_total: $lineTotal,
            );
        }

        // Enrich party data from DTO repositories if IDs available
        $supplier = null; $client = null;
        if ($invoice->supplier_id) {
            $supplier = $this->supplierRepo->findById(PartyId::fromInt($invoice->supplier_id));
        }
        if ($invoice->client_id) {
            $client = $this->clientRepo->findById(PartyId::fromInt($invoice->client_id));
        }

        return new PrintableInvoiceData(
            id: $invoice->id,
            number: $invoice->number,
            issue_date: $issue,
            tax_point_date: $taxPoint,
            due_date: $due,
            due_in: $invoice->due_in,
            currency: strtoupper($invoice->currency ?? 'CZK'),
            template: $invoice->template,
            invoice_logo: $invoice->logo,
            items: $items,
            subtotal: $invoice->subtotal,
            total_tax: $invoice->total_tax,
            total_amount: $invoice->total_amount,
            supplier_name: $supplier->name ?? null,
            supplier_email: $supplier->email ?? null,
            supplier_phone: $supplier->phone ?? null,
            supplier_street: $supplier->street ?? null,
            supplier_city: $supplier->city ?? null,
            supplier_zip: $supplier->zip ?? null,
            supplier_country: $supplier->country ?? null,
            supplier_ico: $supplier->ico ?? null,
            supplier_dic: $supplier->dic ?? null,
            supplier_logo: $supplier->supplier_logo ?? null,
            account_number: isset($supplier?->account_number) ? (string)$supplier->account_number : null,
            bank_code: $supplier->bank_code ?? null,
            bank_name: $supplier->bank_name ?? null,
            iban: $supplier->iban ?? null,
            swift: $supplier->swift ?? null,
            client_name: $client->name ?? null,
            client_email: $client->email ?? null,
            client_phone: $client->phone ?? null,
            client_street: $client->street ?? null,
            client_city: $client->city ?? null,
            client_zip: $client->zip ?? null,
            client_country: $client->country ?? null,
            client_ico: $client->ico ?? null,
            client_dic: $client->dic ?? null,
            invoice_vs: $invoice->number,
            invoice_ks: $invoice->constant_code,
            invoice_ss: $invoice->payment_reference,
            notes: null,
        );
    }

    private function toImmutableDate(?string $date): ?\DateTimeImmutable
    {
        if (!$date) { return null; }
        try { return new \DateTimeImmutable($date); } catch (\Exception) { return null; }
    }

    private function computeDueDate(?\DateTimeImmutable $issue, ?int $dueIn): ?\DateTimeImmutable
    {
        if (!$issue || $dueIn === null) { return null; }
        return $issue->modify('+' . (int)$dueIn . ' days');
    }
}
