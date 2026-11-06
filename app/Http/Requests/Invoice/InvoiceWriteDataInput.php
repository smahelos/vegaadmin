<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoice;

use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Domain\Shared\Money\Enums\Currency;
use Closure;
use DateTimeImmutable;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * The invoice fields the frontend and the admin form share, and the write data built from them.
 */
final class InvoiceWriteDataInput
{
    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'invoice_vs' => [
                'bail',
                'required',
                'string',
                self::invoiceNumber(...),
            ],
            'invoice_ks' => 'nullable|string|max:20',
            'invoice_ss' => 'nullable|string|max:20',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_currency' => ['required', Rule::enum(Currency::class)],
            'issue_date' => 'required|date',
            'tax_point_date' => 'required|date',
            'due_in' => 'required|integer|min:1|max:' . InvoiceDueDate::MAX_DAYS,
            'status' => ['required', Rule::enum(InvoiceStatus::class)],
            'invoice_text' => 'nullable|string',
            'template' => ['required', Rule::enum(InvoiceTemplate::class)],
        ];
    }

    /** @return array<string, string> */
    public static function attributes(): array
    {
        return [
            'invoice_vs' => __('invoices.fields.invoice_vs'),
            'invoice_ks' => __('invoices.fields.invoice_ks'),
            'invoice_ss' => __('invoices.fields.invoice_ss'),
            'payment_method_id' => __('invoices.fields.payment_method_id'),
            'payment_currency' => __('invoices.fields.payment_currency'),
            'issue_date' => __('invoices.fields.issue_date'),
            'tax_point_date' => __('invoices.fields.tax_point_date'),
            'due_in' => __('invoices.fields.due_in'),
            'status' => __('invoices.fields.status'),
            'invoice_text' => __('invoices.fields.invoice_text'),
            'template' => __('invoices.fields.template'),
        ];
    }

    /** @param  array<string, mixed>  $validated */
    public static function writeData(array $validated): InvoiceWriteData
    {
        return new InvoiceWriteData(
            number: new InvoiceNumber($validated['invoice_vs']),
            constantSymbol: $validated['invoice_ks'] ?? null,
            specificSymbol: $validated['invoice_ss'] ?? null,
            issueDate: new DateTimeImmutable($validated['issue_date']),
            taxPointDate: new DateTimeImmutable($validated['tax_point_date']),
            dueIn: (int) $validated['due_in'],
            currency: Currency::from($validated['payment_currency']),
            status: InvoiceStatus::from($validated['status']),
            paymentMethodId: PaymentMethodId::fromInt((int) $validated['payment_method_id']),
            text: $validated['invoice_text'] ?? null,
            template: InvoiceTemplate::from($validated['template']),
        );
    }

    /** The number is also the variable symbol of QR payments, so only the InvoiceNumber format is accepted. */
    private static function invoiceNumber(
        string $attribute,
        mixed $value,
        Closure $fail,
    ): void {
        if (!is_string($value)) {
            return;
        }
        try {
            new InvoiceNumber($value);
        } catch (InvalidArgumentException) {
            $message = $fail('validation.regex');
            $message->translate();
        }
    }
}
