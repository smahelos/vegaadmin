<?php

namespace App\Application\Invoice\Services;

use App\Application\Invoice\Contracts\TemporaryInvoicePrintDataFactoryInterface;
use App\Domain\Invoice\DTO\PrintableInvoiceData;
use App\Domain\Invoice\DTO\PrintableInvoiceItemData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Money\ValueObjects\Money;

class TemporaryInvoicePrintDataFactory implements TemporaryInvoicePrintDataFactoryInterface
{
    public function fromArray(array $invoiceData): PrintableInvoiceData
    {
        $currency = strtoupper((string)($invoiceData['payment_currency'] ?? 'CZK'));
        $issue = $this->toImmutable($invoiceData['issue_date'] ?? null);
        $dueIn = isset($invoiceData['due_in']) ? (int)$invoiceData['due_in'] : null;
        $dueDate = $issue && $dueIn !== null ? $issue->modify('+' . (int)$dueIn . ' days') : null;

        $productsRaw = $invoiceData['invoice-products'] ?? [];
        if (is_string($productsRaw)) {
            $decoded = json_decode($productsRaw, true);
            $productsRaw = is_array($decoded) ? $decoded : [];
        }
        $items = [];
        $subtotal = Money::fromFloat(0, $currency);
        $taxTotal = Money::fromFloat(0, $currency);
        foreach ($productsRaw as $p) {
            $q = isset($p['quantity']) ? (float)$p['quantity'] : 1.0;
            $itemCurrency = strtoupper((string)($p['currency'] ?? $currency));
            $price = Money::fromFloat((float)($p['price'] ?? 0), $itemCurrency);
            $taxRate = isset($p['tax_rate']) ? (float)$p['tax_rate'] : 0.0;
            $taxAmount = Money::fromFloat($price->toFloat() * $q * $taxRate / 100, $itemCurrency);
            $lineTotal = Money::fromFloat($price->toFloat() * $q + $taxAmount->toFloat(), $itemCurrency);
            $items[] = new PrintableInvoiceItemData(
                name: (string)($p['name'] ?? ''),
                quantity: $q,
                unit: $p['unit'] ?? null,
                price: $price,
                tax_rate: $taxRate,
                tax_amount: $taxAmount,
                line_total: $lineTotal,
            );
            if ($itemCurrency === $currency) {
                $subtotal = $subtotal->add(Money::fromFloat($price->toFloat() * $q, $currency));
                $taxTotal = $taxTotal->add(Money::fromFloat($taxAmount->toFloat(), $currency));
            }
        }
        $grand = Money::fromFloat(($invoiceData['payment_amount'] ?? ($subtotal->toFloat() + $taxTotal->toFloat())), $currency);

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
            subtotal: $subtotal,
            total_tax: $taxTotal,
            total_amount: $grand,
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
        if (!$d) { return null; }
        try { return new \DateTimeImmutable($d); } catch (\Exception) { return null; }
    }
}
