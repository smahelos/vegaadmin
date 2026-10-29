<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Application\Invoice\Services\ExpenseApplicationService;
use App\Domain\Invoice\DTO\ExpenseWriteData;
use App\Domain\Invoice\ValueObjects\ExpenseId;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\File\DTO\IncomingFile;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Requests\Party\AdminDocumentPartyInput;
use App\Http\Requests\Party\BankAccountInput;
use App\Http\Requests\Party\DocumentPartyInput;
use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_user()?->can($this->isMethod('POST') ? 'can_create_expense' : 'can_update_expense') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $accountFields = [];
        foreach (BankAccountInput::FIELDS as $field) {
            $accountFields[$field] = $this->input('account_' . $field);
        }

        return [
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
            'bank_account_id' => [
                'nullable',
                'integer',
                Rule::exists('bank_accounts', 'id')->where('contact_id', $this->integer('contact_id')),
            ],
            ...AdminDocumentPartyInput::rules(
                $this->all(),
                'supplier',
                $this->filled('contact_id') && ! $this->keepsContact(),
            ),
            ...DocumentPartyInput::accountRules($this->all(), ! $this->filled('bank_account_id') && BankAccountInput::isFilled($accountFields)),
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|decimal:0,2|min:0|max:99999999.99',
            'currency' => ['required', Rule::enum(Currency::class)],
            'category_id' => 'required|integer|exists:expense_categories,id',
            'payment_method_id' => 'nullable|integer|exists:payment_methods,id',
            'reference_number' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'tax_amount' => 'nullable|numeric|decimal:0,2|min:0|max:99999999.99',
            'tax_included' => 'nullable|boolean',
            'status_id' => [
                'nullable',
                'integer',
                Rule::exists('statuses', 'id')->whereIn('category_id', fn ($query) => $query->select('id')->from('status_categories')->where('slug', 'expense-statuses')),
            ],
            'attachments' => 'sometimes|nullable|array',
            'attachments.*' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt|max:10240',
            'attachments_removed' => 'nullable|json',
        ];
    }

    public function toWriteData(): ExpenseWriteData
    {
        $data = $this->validated();
        $currency = $data['currency'];
        $uploads = [];
        foreach ($this->file('attachments', []) as $file) {
            $uploads[] = new IncomingFile(
                originalName: $file->getClientOriginalName(),
                extension: $file->getClientOriginalExtension(),
                mime: $file->getMimeType() ?? 'application/octet-stream',
                sizeBytes: (int) $file->getSize(),
                path: $file->getPathname(),
            );
        }
        $removed = json_decode($data['attachments_removed'] ?? '[]', true);

        return new ExpenseWriteData(
            BusinessId::fromInt((int) $data['business_id']),
            isset($data['contact_id']) ? ContactId::fromInt((int) $data['contact_id']) : null,
            isset($data['bank_account_id']) ? BankAccountId::fromInt((int) $data['bank_account_id']) : null,
            DocumentPartyInput::party($data, 'supplier'),
            DocumentPartyInput::account($data),
            new DateTimeImmutable($data['expense_date']),
            Money::fromString((string) $data['amount'], $currency),
            (int) $data['category_id'],
            isset($data['payment_method_id']) ? (int) $data['payment_method_id'] : null,
            $data['reference_number'] ?? null,
            $data['description'] ?? null,
            Money::fromString((string) ($data['tax_amount'] ?? '0'), $currency),
            (bool) ($data['tax_included'] ?? false),
            isset($data['status_id']) ? (int) $data['status_id'] : null,
            $uploads,
            is_array($removed) ? array_values(array_filter($removed, is_string(...))) : [],
        );
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $attributes = [...DocumentPartyInput::attributes('supplier'), ...DocumentPartyInput::accountAttributes()];
        foreach ([
            'expense_date',
            'amount',
            'currency',
            'category_id',
            'payment_method_id',
            'reference_number',
            'description',
            'tax_amount',
            'tax_included',
            'status_id',
            'attachments',
        ] as $field) {
            $attributes[$field] = __('expenses.fields.' . $field);
        }
        foreach ([
            'user_id',
            'business_id',
            'contact_id',
            'bank_account_id',
        ] as $field) {
            $attributes[$field] = __('invoices.fields.' . $field);
        }

        return $attributes;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('currency'))) {
            $this->merge(['currency' => strtoupper($this->input('currency'))]);
        }
        if (is_string($this->input('reference_number'))) {
            $this->merge(['reference_number' => trim($this->input('reference_number'))]);
        }
    }

    private function keepsContact(): bool
    {
        $id = (int) $this->route('id');
        if ($this->isMethod('POST') || $id <= 0 || $this->integer('user_id') <= 0) {
            return false;
        }

        return app(ExpenseApplicationService::class)->get(UserId::fromInt($this->integer('user_id')), ExpenseId::fromInt($id))->parties->contactId?->value === $this->integer('contact_id');
    }
}
