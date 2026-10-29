<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services;

use App\Application\Invoice\Contracts\TemporaryInvoicePrintDataFactoryInterface;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\PrintableInvoiceDTO;
use App\Domain\Invoice\DTO\PrintableInvoiceItemDTO;
use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\DTO\PartyPrintDTO;
use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Domain\Shared\Money\Enums\Currency;
use DateMalformedStringException;
use DateTimeImmutable;

final class TemporaryInvoicePrintDataFactory implements TemporaryInvoicePrintDataFactoryInterface
{
    public function __construct(
        private readonly InvoiceProductWriteDataAssembler $products,
        private readonly PaymentMethodDtoReadRepositoryInterface $paymentMethods,
    ) {}

    public function fromArray(array $invoiceData): PrintableInvoiceDTO
    {
        $currency = strtoupper((string) ($invoiceData['payment_currency'] ?? 'CZK'));
        $issue = $this->toImmutable($invoiceData['issue_date'] ?? null);
        $dueDate = null;
        if ($issue && isset($invoiceData['due_in'])) {
            $dueDate = InvoiceDueDate::fromIssueDateAndDays($issue, (int) $invoiceData['due_in']);
        }

        $products = $this->products->fromValidated($invoiceData['invoice-products']);
        $items = array_map(fn (InvoiceProductWriteData $product): PrintableInvoiceItemDTO => new PrintableInvoiceItemDTO(
            name: $product->name,
            quantity: (float) $product->quantity,
            unit: $product->unit,
            price: $product->price,
            taxRate: (float) $product->taxRate,
            lineTotal: $product->total(),
        ), $products);
        $totals = InvoiceTotals::fromProducts(Currency::from($currency), $products);

        /** @var DocumentPartyDTO $supplier */
        $supplier = $invoiceData['supplier'];
        /** @var DocumentPartyDTO $customer */
        $customer = $invoiceData['customer'];
        /** @var DocumentAccountDTO|null $account */
        $account = $invoiceData['account'] ?? null;

        // Use a positive sentinel ID for temporary invoices to satisfy InvoiceId validation
        return new PrintableInvoiceDTO(
            id: InvoiceId::fromInt(1),
            number: $invoiceData['invoice_vs'] ?? null,
            issueDate: $issue,
            taxPointDate: $this->toImmutable($invoiceData['tax_point_date'] ?? null),
            dueDate: $dueDate,
            currency: $currency,
            template: $invoiceData['template'] ?? 'default',
            items: $items,
            subtotal: $totals->subtotal,
            totalTax: $totals->tax,
            totalAmount: $totals->total(),
            supplier: PartyPrintDTO::fromDocumentParty($supplier),
            customer: PartyPrintDTO::fromDocumentParty($customer),
            account: $account,
            supplierLogo: $invoiceData['invoice_logo'] ?? null,
            variableSymbol: $invoiceData['invoice_vs'] ?? null,
            constantSymbol: $invoiceData['invoice_ks'] ?? null,
            specificSymbol: $invoiceData['invoice_ss'] ?? null,
            paymentMethod: $this->paymentMethods->findById(PaymentMethodId::fromInt((int) $invoiceData['payment_method_id']))?->code,
            notes: $invoiceData['invoice_text'] ?? null,
        );
    }

    private function toImmutable(?string $d): ?DateTimeImmutable
    {
        if (! $d) {
            return null;
        }
        try {
            return new DateTimeImmutable($d);
        } catch (DateMalformedStringException) {
            return null;
        }
    }
}
