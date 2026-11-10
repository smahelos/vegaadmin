<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\User\Enums\Permission;
use App\Http\Requests\Invoice\InvoiceItemInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class InvoiceItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = backpack_user();

        return $user?->canAny([Permission::INVOICE_CREATE, Permission::INVOICE_UPDATE]) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        if ($this->isMethod('POST')) {
            $itemRules = InvoiceItemInput::rules(
                $this->integer('user_id'),
                false,
                false,
            );
        } else {
            $itemRules = [];
        }

        return [
            'user_id' => 'required|integer|min:1',
            'payment_currency' => ['required', Rule::enum(Currency::class)],
            'q' => 'nullable|string|max:255',
            ...$itemRules,
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'user_id' => __('parties.fields.owner'),
            'payment_currency' => __('invoices.fields.payment_currency'),
            'q' => __('invoices.items.search'),
            ...InvoiceItemInput::attributes(),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['invoice-products.*.currency.same' => __('invoices.validation.item_currency')];
    }
}
