<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Party\Mappers;

use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\DTO\ContactWriteData;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Contact;

final class EloquentContactMapper
{
    /**
     * Expects the address relation to be loaded.
     */
    public static function toDTO(Contact $contact): ContactDTO
    {
        return new ContactDTO(
            id: ContactId::fromInt($contact->id),
            userId: UserId::fromInt($contact->user_id),
            profile: EloquentPartyProfileMapper::toProfile($contact),
            contactPerson: $contact->contact_person,
            defaultBankAccountId: $contact->default_bank_account_id === null
                ? null
                : BankAccountId::fromInt($contact->default_bank_account_id),
            archived: $contact->archived_at,
            logo: $contact->logo,
        );
    }

    /**
     * @return array<string, string|null>
     */
    public static function toAttributes(ContactWriteData $data): array
    {
        return EloquentPartyProfileMapper::toAttributes($data->profile) + ['contact_person' => $data->contactPerson, 'logo' => $data->logo];
    }
}
