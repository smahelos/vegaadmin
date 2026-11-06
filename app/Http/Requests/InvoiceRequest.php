<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Application\Invoice\Services\InvoiceProductWriteDataAssembler;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Http\Requests\Invoice\InvoiceItemInput;
use App\Http\Requests\Invoice\InvoiceWriteDataInput;
use App\Http\Requests\Party\DocumentPartyInput;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class InvoiceRequest extends BaseEntityRequest
{
    /**
     * Override authorization to allow guest invoice creation on specific route.
     * Guests can access the guest store endpoint (temporary invoice creation) without authentication.
     */
    public function authorize(): bool
    {
        if ($this->isGuestStore()) {
            return true; // Skip parent authorization & limit checks for guest temporary invoices
        }

        if ($this->boolean('save_customer_to_contacts')) {
            $user = $this->user();
            if ($user?->can('frontend.can_create_contact') !== true) {
                return false;
            }
        }

        return parent::authorize();
    }

    /**
     * Customize failed validation for guest JSON endpoint to avoid redirect (302) and return 422 JSON.
     */
    protected function failedValidation(Validator $validator): never
    {
        if ($this->isGuestStore()) {
            // For guest JSON endpoint, return 422 JSON response
            $responseFactory = response();
            $errors = $validator->errors();
            $response = $responseFactory->json(
                [
                    'message' => __('invoices.messages.validation_failed'),
                    'errors' => $errors->toArray(),
                ],
                422,
            );
            throw new ValidationException($validator, $response);
        }

        // For regular routes, show both field errors and general error message
        $redirector = redirect();
        $redirect = $redirector->back();
        $redirect->withErrors($validator->errors());
        $redirect->withInput();
        $redirect->with(
            'error',
            __('invoices.messages.validation_failed'),
        );

        throw new ValidationException($validator, $redirect);
    }

    /**
     * Force JSON expectation for guest store route so Laravel returns 422 JSON instead of redirect.
     */
    public function expectsJson(): bool
    {
        if ($this->isGuestStore()) {
            return true;
        }

        return parent::expectsJson();
    }

    public function wantsJson(): bool
    {
        if ($this->isGuestStore()) {
            return true;
        }

        return parent::wantsJson();
    }

    /**
     * Get the entity type for limit checking
     */
    protected function getEntityType(): string
    {
        return 'invoice';
    }

    /**
     * Get required permission for invoice operations
     * Note: Invoice creation from frontend is currently unlimited
     */
    protected function getRequiredPermission(): string
    {
        return ''; // No permission required - frontend invoice creation is unlimited
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Get max file size from config
        $maxFileSize = Config::get('file_upload.contexts.invoice_logo.max_kb', 2048);

        $user = $this->user();

        return [
            ...InvoiceWriteDataInput::rules(),
            // Ensure only allowed file types are accepted; allow SVG as image format
            'invoice_logo' => $this->isGuestStore() ? 'nullable|file|mimes:jpeg,jpg,png,gif,svg,webp|max:' . $maxFileSize : ['exclude'],
            ...InvoiceItemInput::rules(
                $user?->getAuthIdentifier(),
                $this->isGuestStore(),
                true,
            ),
            ...$this->partyRules(),
        ];
    }

    /**
     * A signed-in user selects the business and its account and picks or enters the customer; a guest enters
     * both parties and the account.
     *
     * @return array<string, mixed>
     */
    private function partyRules(): array
    {
        $input = $this->all();
        $bankTransfer = $this->paysByBankTransfer();

        if ($this->isGuestStore()) {
            return [
                'business_id' => ['prohibited'],
                'bank_account_id' => ['prohibited'],
                'contact_id' => ['prohibited'],
                'save_customer_to_contacts' => ['prohibited'],
                ...DocumentPartyInput::rules(
                    $input,
                    'supplier',
                    true,
                ),
                ...DocumentPartyInput::rules(
                    $input,
                    'customer',
                    true,
                ),
                ...DocumentPartyInput::accountRules($input, $bankTransfer),
            ];
        }

        $user = $this->user();
        $userId = $user?->getAuthIdentifier();
        $businessExists = Rule::exists('businesses', 'id');
        $businessExists->where('user_id', $userId);
        if ($bankTransfer) {
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
        $contactExists = Rule::exists('contacts', 'id');
        $contactExists->where('user_id', $userId);

        return [
            'business_id' => [
                'required',
                'integer',
                $businessExists,
            ],
            'bank_account_id' => $bankAccountRules,
            'contact_id' => [
                'nullable',
                'integer',
                $contactExists,
            ],
            'save_customer_to_contacts' => ['boolean'],
            'reload_business' => ['boolean'],
            ...DocumentPartyInput::rules(
                $input,
                'customer',
                true,
            ),
        ];
    }

    private function paysByBankTransfer(): bool
    {
        $paymentMethodId = $this->integer('payment_method_id');
        if ($paymentMethodId <= 0) {
            return false;
        }

        $paymentMethods = app(PaymentMethodDtoReadRepositoryInterface::class);
        $paymentMethod = $paymentMethods->findById(
            PaymentMethodId::fromInt($paymentMethodId),
        );

        return $paymentMethod?->code === PaymentMethodCode::BANK_ACCOUNT;
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

    /**
     * The parties a signed-in user chose, from the validated input.
     */
    public function partySelection(): InvoicePartySelection
    {
        $validated = $this->validated();
        $bankAccountId = $validated['bank_account_id'] ?? null;
        $contactId = $validated['contact_id'] ?? null;

        return new InvoicePartySelection(
            businessId: BusinessId::fromInt((int) $validated['business_id']),
            bankAccountId: $bankAccountId === null ? null : BankAccountId::fromInt((int) $bankAccountId),
            contactId: $contactId === null ? null : ContactId::fromInt((int) $contactId),
            customer: DocumentPartyInput::party($validated, 'customer'),
            saveCustomerToContacts: $contactId === null && $this->boolean('save_customer_to_contacts'),
            reloadBusiness: $this->boolean('reload_business'),
        );
    }

    /**
     * A party entered on the form, the guest's supplier or customer.
     */
    public function oneOffParty(string $role): DocumentPartyDTO
    {
        return DocumentPartyInput::party($this->validated(), $role);
    }

    /**
     * The account a guest entered, null without one.
     */
    public function oneOffAccount(): ?DocumentAccountDTO
    {
        return DocumentPartyInput::account($this->validated());
    }

    private function isGuestStore(): bool
    {
        // Tests pass a plain object with a name property instead of an Illuminate route
        $route = $this->route();
        $name = match (true) {
            is_object($route) && method_exists($route, 'getName') => $route->getName(),
            is_object($route) && property_exists($route, 'name') => $route->name,
            default => null,
        };

        return $name === 'frontend.invoice.store.guest';
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...InvoiceWriteDataInput::attributes(),
            'invoice_logo' => __('invoices.fields.invoice_logo'),
            ...InvoiceItemInput::attributes(),
            'business_id' => __('invoices.fields.business_id'),
            'bank_account_id' => __('invoices.fields.bank_account_id'),
            'contact_id' => __('invoices.fields.contact_id'),
            ...DocumentPartyInput::attributes('supplier'),
            ...DocumentPartyInput::attributes('customer'),
            ...DocumentPartyInput::accountAttributes(),
        ];
    }

    /**
     * An item must be in the currency of the invoice; the standard messages cannot say that.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'invoice-products.required' => __('invoices.validation.amount_positive'),
            'invoice-products.min' => __('invoices.validation.amount_positive'),
            'invoice-products.*.currency.same' => __('invoices.validation.item_currency'),
        ];
    }

    /**
     * Fills empty fields from their *_fallback inputs and decodes the items.
     */
    protected function prepareForValidation(): void
    {
        // Call parent preparation first
        parent::prepareForValidation();

        // IMPORTANT: Never replace the whole input with $this->all()
        // because it can include UploadedFile instances and move them out of the files bag.
        // Instead, build a minimal diff and merge it to preserve uploaded files.

        $merge = [];

        // Handle any other fallback fields
        foreach (
            [
                'payment_method_id',
                'due_in',
                'status',
            ] as $field
        ) {
            if (!$this->filled($field) && $this->has($field . '_fallback')) {
                $merge[$field] = $this->input($field . '_fallback');
            }
        }

        // The form sends the items as one JSON field; a value that is not JSON stays and fails the array rule.
        $items = $this->input('invoice-products');
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            if (is_array($decoded)) {
                $merge['invoice-products'] = $decoded;
            }
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }
}
