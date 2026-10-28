<?php

namespace App\Application\Invoice\Services;

use App\Application\Invoice\Contracts\TemporaryInvoicePrintDataFactoryInterface;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\PrintableInvoiceData;
use App\Domain\Invoice\DTO\PrintableInvoiceItemData;
use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Domain\Shared\Money\Enums\Currency;

class TemporaryInvoicePrintDataFactory implements TemporaryInvoicePrintDataFactoryInterface
{
    public function __construct(
        private readonly InvoiceProductWriteDataAssembler $products,
        private readonly PaymentMethodDtoReadRepositoryInterface $paymentMethods,
    ) {}

    public function fromArray(array $invoiceData): PrintableInvoiceData
    {
        $currency = strtoupper((string) ($invoiceData['payment_currency'] ?? 'CZK'));
        $issue = $this->toImmutable($invoiceData['issue_date'] ?? null);
        $dueDate = null;
        if ($issue && isset($invoiceData['due_in'])) {
            $dueDate = InvoiceDueDate::fromIssueDateAndDays($issue, (int) $invoiceData['due_in']);
        }

        $products = $this->products->fromValidated($invoiceData['invoice-products']);
        $items = array_map(fn (InvoiceProductWriteData $product): PrintableInvoiceItemData => new PrintableInvoiceItemData(
            name: $product->name,
            quantity: (float) $product->quantity,
            unit: $product->unit,
            price: $product->price,
            tax_rate: (float) $product->taxRate,
            line_total: $product->total(),
        ), $products);
        $totals = InvoiceTotals::fromProducts(Currency::from($currency), $products);

        /** @var DocumentPartyDTO $supplier */
        $supplier = $invoiceData['supplier'];
        /** @var DocumentPartyDTO $customer */
        $customer = $invoiceData['customer'];
        /** @var DocumentAccountDTO|null $account */
        $account = $invoiceData['account'] ?? null;

        // Use a positive sentinel ID for temporary invoices to satisfy InvoiceId validation
        return new PrintableInvoiceData(
            id: InvoiceId::fromInt(1),
            number: $invoiceData['invoice_vs'] ?? null,
            issue_date: $issue,
            tax_point_date: $this->toImmutable($invoiceData['tax_point_date'] ?? null),
            due_date: $dueDate,
            currency: $currency,
            template: $invoiceData['template'] ?? 'default',
            invoice_logo: $invoiceData['invoice_logo'] ?? null,
            items: $items,
            subtotal: $totals->subtotal,
            total_tax: $totals->tax,
            total_amount: $totals->total(),
            supplier_name: $supplier->name,
            supplier_email: $supplier->email,
            supplier_phone: $supplier->phone,
            supplier_street: $supplier->address?->street,
            supplier_city: $supplier->address?->city,
            supplier_zip: $supplier->address?->postalCode,
            supplier_country: $supplier->address?->country->value,
            supplier_ico: $supplier->registrationNumber?->number,
            supplier_dic: $supplier->vatNumber?->value,
            supplier_logo: $invoiceData['invoice_logo'] ?? null,
            account_number: $account?->account->number,
            bank_code: $account?->account->bankCode,
            bank_name: $account?->bankName,
            iban: $account?->account->iban,
            swift: $account?->account->swift,
            client_name: $customer->name,
            client_email: $customer->email,
            client_phone: $customer->phone,
            client_street: $customer->address?->street,
            client_city: $customer->address?->city,
            client_zip: $customer->address?->postalCode,
            client_country: $customer->address?->country->value,
            client_ico: $customer->registrationNumber?->number,
            client_dic: $customer->vatNumber?->value,
            invoice_vs: $invoiceData['invoice_vs'] ?? null,
            invoice_ks: $invoiceData['invoice_ks'] ?? null,
            invoice_ss: $invoiceData['invoice_ss'] ?? null,
            paymentMethod: $this->paymentMethods->findById(PaymentMethodId::fromInt((int) $invoiceData['payment_method_id']))?->code,
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
