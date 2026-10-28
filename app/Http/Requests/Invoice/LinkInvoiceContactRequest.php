<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoice;

use App\Domain\Party\ValueObjects\ContactId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Links the customer of a stored invoice to an existing contact of the user.
 */
final class LinkInvoiceContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('frontend.can_create_edit_invoice') === true;
    }

    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        return [
            'contact_id' => [
                'required',
                'integer',
                Rule::exists('contacts', 'id')->where('user_id', $this->user()?->getAuthIdentifier()),
            ],
        ];
    }

    public function contactId(): ContactId
    {
        return ContactId::fromInt($this->integer('contact_id'));
    }
}
