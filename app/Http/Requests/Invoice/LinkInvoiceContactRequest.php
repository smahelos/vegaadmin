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
    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        $user = $this->user();
        $contactExists = Rule::exists('contacts', 'id');
        $contactExists->where('user_id', $user?->getAuthIdentifier());

        return [
            'contact_id' => [
                'required',
                'integer',
                $contactExists,
            ],
        ];
    }

    public function contactId(): ContactId
    {
        return ContactId::fromInt(
            $this->integer('contact_id'),
        );
    }
}
