<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Application\Invoice\Contracts\InvoiceListingApplicationServiceInterface;
use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Application\Invoice\Services\InvoiceProductWriteDataAssembler;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Requests\Invoice\InvoiceItemInput;
use App\Http\Requests\Invoice\InvoiceWriteDataInput;
use App\Http\Requests\Party\AdminDocumentPartyInput;
use App\Http\Requests\Party\DocumentPartyInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = backpack_user();

        return $user?->can(
            $this->isMethod('POST') ? 'can_create_invoice' : 'can_update_invoice',
        ) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $businessExists = Rule::exists('businesses', 'id');
        $businessExists->where(
            'user_id',
            $this->integer('user_id'),
        );
        $contactExists = Rule::exists('contacts', 'id');
        $contactExists->where(
            'user_id',
            $this->integer('user_id'),
        );
        if ($this->paysByBankTransfer()) {
            $bankAccountExists = Rule::exists('bank_accounts', 'id');
            $bankAccountExists->where(
                'business_id',
                $this->integer('business_id'),
            );
            $bankAccountRules = [
                'required',
                'integer',
                $bankAccountExists,
            ];
        } else {
            $bankAccountRules = ['exclude'];
        }

        $rules = [
            'user_id' => 'required|integer|exists:users,id',
            'business_id' => [
                'required',
                'integer',
                $businessExists,
            ],
            'contact_id' => [
                'nullable',
                'integer',
                $contactExists,
            ],
            'bank_account_id' => $bankAccountRules,
            ...InvoiceWriteDataInput::rules(),
        ];

        $customerRules = AdminDocumentPartyInput::rules(
            $this->all(),
            'customer',
            $this->filled('contact_id') && !$this->keepsContact(),
        );

        return [
            ...$rules,
            ...InvoiceItemInput::rules(
                $this->integer('user_id'),
                false,
                true,
            ),
            ...$customerRules,
        ];
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('invoice-products');
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            if (is_array($decoded)) {
                $this->merge(['invoice-products' => $decoded]);
            }
        }
    }

    /** The user the invoice belongs to; an admin may create it for anyone. */
    public function owner(): UserId
    {
        return UserId::fromInt(
            (int) $this->validated('user_id'),
        );
    }

    public function toWriteData(): InvoiceWriteData
    {
        return InvoiceWriteDataInput::writeData(
            $this->validated(),
        );
    }

    /** @return list<InvoiceProductWriteData> */
    public function products(): array
    {
        $assembler = app(InvoiceProductWriteDataAssembler::class);

        return $assembler->fromValidated(
            $this->validated('invoice-products'),
        );
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
        $attributes = [
            ...DocumentPartyInput::attributes('customer'),
            ...InvoiceItemInput::attributes(),
            ...InvoiceWriteDataInput::attributes(),
        ];
        foreach (
            [
                'user_id',
                'business_id',
                'contact_id',
                'bank_account_id',
            ] as $field
        ) {
            $attributes[$field] = __('invoices.fields.' . $field);
        }

        return $attributes;
    }

    private function keepsContact(): bool
    {
        $id = (int) $this->route('id');
        if ($this->isMethod('POST') || $id <= 0 || $this->integer('user_id') <= 0) {
            return false;
        }
        $invoices = app(InvoiceListingApplicationServiceInterface::class);
        $invoice = $invoices->getById(
            InvoiceId::fromInt($id),
            UserId::fromInt(
                $this->integer('user_id'),
            ),
        );

        return $invoice->parties?->contactId?->value === $this->integer('contact_id');
    }

    private function paysByBankTransfer(): bool
    {
        $id = $this->integer('payment_method_id');
        if ($id <= 0) {
            return false;
        }

        $paymentMethods = app(PaymentMethodDtoReadRepositoryInterface::class);
        $paymentMethod = $paymentMethods->findById(
            PaymentMethodId::fromInt($id),
        );

        return $paymentMethod?->code === PaymentMethodCode::BANK_ACCOUNT;
    }
}
