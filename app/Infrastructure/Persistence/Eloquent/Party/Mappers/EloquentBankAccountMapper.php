<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Party\Mappers;

use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\DTO\BankAccountWriteData;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Payment\ValueObjects\BankAccount;
use App\Domain\Shared\Geography\Enums\Country;
use App\Models\BankAccount as BankAccountModel;

final class EloquentBankAccountMapper
{
    public static function toDTO(BankAccountModel $account): BankAccountDTO
    {
        return new BankAccountDTO(
            id: BankAccountId::fromInt($account->id),
            owner: $account->business_id === null
                ? AccountOwner::contact(ContactId::fromInt($account->contact_id))
                : AccountOwner::business(BusinessId::fromInt($account->business_id)),
            label: $account->label,
            account: BankAccount::fromParts(
                $account->iban,
                $account->number,
                $account->bank_code,
                Country::from($account->country),
                $account->swift,
            ),
            bankName: $account->bank_name,
            archived: $account->archived_at,
        );
    }

    /**
     * @return array<string, string|null>
     */
    public static function toAttributes(BankAccountWriteData $data): array
    {
        return [
            'label' => $data->label,
            'iban' => $data->account->iban,
            'number' => $data->account->number,
            'bank_code' => $data->account->bankCode,
            'country' => $data->account->country->value,
            'swift' => $data->account->swift,
            'bank_name' => $data->bankName,
        ];
    }

    /**
     * @return array{business_id: int|null, contact_id: int|null}
     */
    public static function toOwnerAttributes(AccountOwner $owner): array
    {
        return [
            'business_id' => $owner->businessId?->value,
            'contact_id' => $owner->contactId?->value,
        ];
    }
}
