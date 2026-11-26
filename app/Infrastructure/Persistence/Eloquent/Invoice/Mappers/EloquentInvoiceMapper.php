<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Mappers;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductDTO;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;
use App\Infrastructure\Persistence\Eloquent\Party\Mappers\EloquentDocumentPartiesMapper;
use App\Models\Invoice;
use App\Models\InvoiceProduct;

final class EloquentInvoiceMapper
{
    public function toDto(Invoice $model): InvoiceDTO
    {
        $currency = Currency::from($model->payment_currency);
        $totals = InvoiceTotals::zero($currency);
        $items = [];
        foreach ($model->invoiceProducts as $line) {
            $item = $this->toItem($line);
            $totals = $totals->withLine($item->base(), $item->taxAmount);
            $items[] = $item;
        }
        $paymentMethodId = $model->payment_method_id;

        return new InvoiceDTO(
            id: InvoiceId::fromInt($model->id),
            parties: EloquentDocumentPartiesMapper::toDocumentParties($model),
            number: $model->invoice_vs,
            constantSymbol: $model->invoice_ks,
            specificSymbol: $model->invoice_ss,
            issueDate: $model->issue_date->toDateTimeImmutable(),
            taxPointDate: $model->tax_point_date->toDateTimeImmutable(),
            dueIn: $model->due_in,
            subtotal: $totals->subtotal,
            totalTax: $totals->tax,
            totalAmount: Money::fromString($model->payment_amount, $currency),
            paymentMethodId: $paymentMethodId === null ? null : PaymentMethodId::fromInt($paymentMethodId),
            text: $model->invoice_text,
            template: InvoiceTemplate::from($model->template),
            status: $model->payment_status,
            items: $items,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(
        InvoiceWriteData $data,
        DocumentParties $parties,
        Money $total,
    ): array {
        return [
            ...EloquentDocumentPartiesMapper::toAttributes($parties),
            'invoice_vs' => $data->number->value,
            'invoice_ks' => $data->constantSymbol,
            'invoice_ss' => $data->specificSymbol,
            'issue_date' => $data->issueDate->format('Y-m-d'),
            'tax_point_date' => $data->taxPointDate->format('Y-m-d'),
            'due_in' => $data->dueIn,
            'payment_method_id' => $data->paymentMethodId->value,
            'payment_currency' => $data->currency->value,
            'payment_amount' => $total->amount,
            'invoice_text' => $data->text,
            'template' => $data->template->value,
            'payment_status' => $data->status->value,
        ];
    }

    private function toItem(InvoiceProduct $line): InvoiceProductDTO
    {
        $currency = Currency::from($line->currency);

        return new InvoiceProductDTO(
            name: $line->name,
            quantity: $line->quantity,
            unit: Unit::from($line->unit),
            price: Money::fromString($line->price ?? '0', $currency),
            taxRate: $line->tax_rate,
            taxAmount: Money::fromString($line->tax_amount, $currency),
            total: Money::fromString($line->total_price, $currency),
        );
    }
}
