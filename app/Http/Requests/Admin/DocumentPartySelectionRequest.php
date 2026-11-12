<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Foundation\Http\FormRequest;

final class DocumentPartySelectionRequest extends FormRequest
{
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

    public function owner(): UserId
    {
        return UserId::fromInt(
            (int) $this->validated('user_id'),
        );
    }

    public function term(): ?string
    {
        $term = $this->validated('q');

        return $term === null ? null : (string) $term;
    }

    public function selectedId(): ?int
    {
        $id = $this->validated('id');

        return $id === null ? null : (int) $id;
    }

    /** The business when both are sent: an account belongs to one of them. */
    public function accountOwner(): ?AccountOwner
    {
        $business = $this->validated('business_id');
        if ($business !== null) {
            return AccountOwner::business(
                BusinessId::fromInt((int) $business),
            );
        }
        $contact = $this->validated('contact_id');
        if ($contact !== null) {
            return AccountOwner::contact(
                ContactId::fromInt((int) $contact),
            );
        }

        return null;
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
