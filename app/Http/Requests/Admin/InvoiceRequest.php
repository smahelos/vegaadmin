<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Application\Invoice\Contracts\InvoiceListingApplicationServiceInterface;
use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Requests\Party\AdminDocumentPartyInput;
use App\Http\Requests\Party\DocumentPartyInput;
use App\Http\Requests\Rules\InvoiceStatusId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_user()?->can($this->isMethod('POST') ? 'can_create_invoice' : 'can_update_invoice') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'user_id' => 'required|integer|exists:users,id',
            'business_id' => [
                'required',
                'integer',
                Rule::exists('businesses', 'id')->where('user_id', $this->integer('user_id')),
            ],
            'contact_id' => [
                'nullable',
                'integer',
                Rule::exists('contacts', 'id')->where('user_id', $this->integer('user_id')),
            ],
            'bank_account_id' => $this->paysByBankTransfer() ? [
                'required',
                'integer',
                Rule::exists('bank_accounts', 'id')->where('business_id', $this->integer('business_id')),
            ] : ['exclude'],
            'invoice_vs' => 'required|string|max:50',
            'invoice_ks' => 'nullable|string|max:20',
            'invoice_ss' => 'nullable|string|max:20',
            'due_in' => 'required|integer|min:1|max:' . InvoiceDueDate::MAX_DAYS,
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_amount' => 'required|numeric|decimal:0,2|min:0|max:99999999.99',
            'payment_status_id' => ['required', InvoiceStatusId::rule()],
            'payment_currency' => ['required', Rule::enum(Currency::class)],
            'issue_date' => 'required|date',
            'tax_point_date' => 'required|date',
            'invoice_text' => 'nullable|string',
            'invoice_logo' => 'nullable|file|mimes:jpeg,jpg,png,gif,svg,webp|max:2048',
            'template' => 'nullable|string|in:default,modern,minimal',
        ];

        $customerRules = AdminDocumentPartyInput::rules(
            $this->all(),
            'customer',
            $this->filled('contact_id') && ! $this->keepsContact(),
        );

        return [...$rules, ...$customerRules];
    }

    public function partySelection(): InvoicePartySelection
    {
        $validated = $this->validated();

        return new InvoicePartySelection(
            businessId: BusinessId::fromInt((int) $validated['business_id']),
            bankAccountId: isset($validated['bank_account_id']) ? BankAccountId::fromInt((int) $validated['bank_account_id']) : null,
            contactId: isset($validated['contact_id']) ? ContactId::fromInt((int) $validated['contact_id']) : null,
            customer: DocumentPartyInput::party($validated, 'customer'),
            saveCustomerToContacts: false,
            reloadBusiness: false,
            copyContact: true,
        );
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $attributes = DocumentPartyInput::attributes('customer');
        foreach ([
            'user_id',
            'business_id',
            'contact_id',
            'bank_account_id',
            'invoice_vs',
            'invoice_ks',
            'invoice_ss',
            'due_in',
            'payment_amount',
            'payment_currency',
            'payment_method_id',
            'issue_date',
            'tax_point_date',
            'invoice_text',
            'invoice_logo',
            'template',
        ] as $field) {
            $attributes[$field] = __('invoices.fields.' . $field);
        }
        $attributes['payment_status_id'] = __('invoices.fields.status');

        return $attributes;
    }

    private function keepsContact(): bool
    {
        $id = (int) $this->route('id');
        if ($this->isMethod('POST') || $id <= 0 || $this->integer('user_id') <= 0) {
            return false;
        }
        $invoice = app(InvoiceListingApplicationServiceInterface::class)->getById(
            InvoiceId::fromInt($id),
            UserId::fromInt($this->integer('user_id')),
        );

        return $invoice->parties?->contactId?->value === $this->integer('contact_id');
    }

    private function paysByBankTransfer(): bool
    {
        $id = $this->integer('payment_method_id');
        if ($id <= 0) {
            return false;
        }

        return app(PaymentMethodDtoReadRepositoryInterface::class)->findById(PaymentMethodId::fromInt($id))?->code === PaymentMethodCode::BANK_ACCOUNT;
    }
}
