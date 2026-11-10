<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoice;

use App\Domain\User\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Creates a contact from the customer of a stored invoice and links the invoice to it.
 */
final class SaveCustomerToContactsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can(Permission::CONTACT_CREATE) && $user->can(Permission::INVOICE_UPDATE);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [];
    }
}
