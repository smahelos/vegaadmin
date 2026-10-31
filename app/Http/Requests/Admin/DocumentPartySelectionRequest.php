<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class DocumentPartySelectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_user()?->canAny([
            'can_create_invoice',
            'can_update_invoice',
            'can_create_expense',
            'can_update_expense',
        ]) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|min:1',
            'business_id' => 'nullable|integer|min:1',
            'contact_id' => 'nullable|integer|min:1',
            'q' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'id' => 'nullable|integer|min:1',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'user_id' => __('parties.fields.owner'),
            'business_id' => __('invoices.fields.business_id'),
            'contact_id' => __('invoices.fields.contact_id'),
        ];
    }
}
