<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Mappers;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceIssue;
use App\Domain\Invoice\DTO\InvoiceProductDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Invoice\ValueObjects\VatLine;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Translation\Enums\Language;
use App\Domain\Shared\Unit\Enums\Unit;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Party\Mappers\EloquentDocumentPartiesMapper;
use App\Models\Invoice;
use App\Models\InvoiceProduct;

final class EloquentInvoiceMapper
{
    public function toDto(Invoice $model): InvoiceDTO
    {
        $currency = Currency::from($model->payment_currency);
        $items = [];
        foreach ($model->invoiceProducts as $line) {
            $items[] = $this->toItem($line);
        }
        $paymentMethodId = $model->payment_method_id;
        $issue = $this->toIssue($model);
        // Issuing fixed the VAT of a payer's invoice in its VAT lines; a draft and a non-payer's invoice have none
        if ($issue !== null && $issue->vatPayer) {
            $vatLines = [];
            foreach ($model->vatLines as $vatLine) {
                $vatLines[] = new VatLine(
                    $vatLine->rate,
                    Money::fromString($vatLine->base, $currency),
                    Money::fromString($vatLine->vat, $currency),
                );
            }
            $totals = InvoiceTotals::fromVatLines($currency, $vatLines);
        } else {
            $totals = InvoiceTotals::fromLines($currency, $items);
        }

        return new InvoiceDTO(
            id: InvoiceId::fromInt($model->id),
            parties: EloquentDocumentPartiesMapper::toDocumentParties($model),
            constantSymbol: $model->invoice_ks,
            specificSymbol: $model->invoice_ss,
            dueIn: $model->due_in,
            totals: $totals,
            totalAmount: Money::fromString($model->payment_amount, $currency),
            paymentMethodId: $paymentMethodId === null ? null : PaymentMethodId::fromInt($paymentMethodId),
            text: $model->invoice_text,
            template: InvoiceTemplate::from($model->template),
            language: Language::from($model->document_language),
            status: $model->payment_status,
            items: $items,
            issue: $issue,
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
            'invoice_ks' => $data->constantSymbol,
            'invoice_ss' => $data->specificSymbol,
            'due_in' => $data->dueIn,
            'payment_method_id' => $data->paymentMethodId->value,
            'payment_currency' => $data->currency->value,
            'payment_amount' => $total->amount,
            'invoice_text' => $data->text,
            'template' => $data->template->value,
            'document_language' => $data->language->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toIssueAttributes(InvoiceIssue $issue): array
    {
        return [
            'invoice_vs' => $issue->number->value,
            'issue_date' => $issue->date->format('Y-m-d'),
            'tax_point_date' => $issue->taxPointDate->format('Y-m-d'),
            'issued_at' => $issue->issuedAt,
            'issued_by' => $issue->issuedBy->value,
            'is_vat_payer' => $issue->vatPayer,
            'register_entry' => $issue->registerEntry,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toVatLineAttributes(VatLine $vatLine): array
    {
        return [
            'rate' => $vatLine->rate,
            'base' => $vatLine->base->amount,
            'vat' => $vatLine->vat->amount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toLineAttributes(InvoiceProductWriteData $data): array
    {
        return [
            'product_id' => $data->productId?->value,
            'name' => $data->name,
            'quantity' => $data->quantity,
            'unit' => $data->unit->value,
            'category' => $data->category,
            'description' => $data->description,
            'price' => $data->price->amount,
            'currency' => $data->price->currency->value,
            'tax_rate' => $data->taxRate,
            'is_custom_product' => $data->isCustomProduct(),
        ];
    }

    private function toIssue(Invoice $model): ?InvoiceIssue
    {
        $issuedAt = $model->issued_at;
        if ($issuedAt === null) {
            return null;
        }

        return new InvoiceIssue(
            number: new InvoiceNumber((string) $model->invoice_vs),
            date: $model->issue_date->toDateTimeImmutable(),
            taxPointDate: $model->tax_point_date->toDateTimeImmutable(),
            issuedAt: $issuedAt->toDateTimeImmutable(),
            issuedBy: UserId::fromInt((int) $model->issued_by),
            vatPayer: (bool) $model->is_vat_payer,
            registerEntry: $model->register_entry,
        );
    }

    private function toItem(InvoiceProduct $line): InvoiceProductDTO
    {
        $currency = Currency::from($line->currency);
        $productId = $line->product_id;

        return new InvoiceProductDTO(
            productId: $productId === null ? null : ProductId::fromInt($productId),
            name: $line->name,
            quantity: $line->quantity,
            unit: Unit::from($line->unit),
            price: Money::fromString($line->price ?? '0', $currency),
            taxRate: $line->tax_rate,
            category: $line->category,
            description: $line->description,
        );
    }
}
