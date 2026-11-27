<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoice;

use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Translation\Enums\Language;
use Closure;
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
            'invoice_ks' => 'nullable|string|max:20',
            'invoice_ss' => 'nullable|string|max:20',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_currency' => ['required', Rule::enum(Currency::class)],
            'due_in' => 'required|integer|min:1|max:' . InvoiceDueDate::MAX_DAYS,
            'invoice_text' => 'nullable|string',
            'template' => ['required', Rule::enum(InvoiceTemplate::class)],
        ];
    }

    /**
     * A draft gets its number and dates at issue; the guest invoice, never issued, is entered with them.
     *
     * @return array<string, mixed>
     */
    public static function guestRules(): array
    {
        return [
            'invoice_vs' => self::numberRules(),
            'issue_date' => 'required|date',
            'tax_point_date' => 'required|date',
        ];
    }

    /**
     * The number is also the variable symbol of QR payments, so only the InvoiceNumber format is accepted.
     *
     * @return list<mixed>
     */
    public static function numberRules(): array
    {
        return [
            'bail',
            'required',
            'string',
            self::invoiceNumber(...),
        ];
    }

    /** @return array<string, string> */
    public static function attributes(): array
    {
        return [
            'invoice_vs' => __('invoices.fields.invoice_vs'),
            'invoice_ks' => __('invoices.fields.invoice_ks'),
            'document_language' => __('invoices.fields.document_language'),
            'invoice_ss' => __('invoices.fields.invoice_ss'),
            'payment_method_id' => __('invoices.fields.payment_method_id'),
            'payment_currency' => __('invoices.fields.payment_currency'),
            'issue_date' => __('invoices.fields.issue_date'),
            'tax_point_date' => __('invoices.fields.tax_point_date'),
            'due_in' => __('invoices.fields.due_in'),
            'invoice_text' => __('invoices.fields.invoice_text'),
            'template' => __('invoices.fields.template'),
        ];
    }

    /** @param  array<string, mixed>  $validated */
    public static function writeData(array $validated): InvoiceWriteData
    {
        return new InvoiceWriteData(
            constantSymbol: $validated['invoice_ks'] ?? null,
            specificSymbol: $validated['invoice_ss'] ?? null,
            dueIn: (int) $validated['due_in'],
            currency: Currency::from($validated['payment_currency']),
            paymentMethodId: PaymentMethodId::fromInt((int) $validated['payment_method_id']),
            text: $validated['invoice_text'] ?? null,
            template: InvoiceTemplate::from($validated['template']),
            language: Language::from($validated['document_language']),
        );
    }

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
