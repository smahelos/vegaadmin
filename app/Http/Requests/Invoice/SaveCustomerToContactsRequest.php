<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Creates a contact from the customer of a stored invoice and links the invoice to it.
 */
final class SaveCustomerToContactsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('frontend.can_create_contact') && $user->can('frontend.can_create_edit_invoice');
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [];
    }
}
