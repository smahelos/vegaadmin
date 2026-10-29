<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Mappers;

use App\Application\Invoice\DTO\DocumentPartyOptionDTO;
use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Infrastructure\Persistence\Eloquent\Party\Mappers\EloquentBankAccountMapper;
use App\Infrastructure\Persistence\Eloquent\Party\Mappers\EloquentPartyProfileMapper;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;

final class EloquentDocumentPartyOptionMapper
{
    public static function toDTO(Business|Contact|BankAccount $record): DocumentPartyOptionDTO
    {
        if ($record instanceof BankAccount) {
            $owner = $record->business ?? $record->contact;

            return new DocumentPartyOptionDTO(
                $record->id,
                self::label($record),
                $owner?->default_bank_account_id === $record->id,
                account: DocumentAccountDTO::fromBankAccount(EloquentBankAccountMapper::toDTO($record)),
            );
        }

        return new DocumentPartyOptionDTO(
            $record->id,
            $record->name,
            profile: DocumentPartyDTO::fromProfile(EloquentPartyProfileMapper::toProfile($record)),
        );
    }

    public static function label(BankAccount $account): string
    {
        return $account->label ?? $account->iban ?? $account->number ?? '';
    }
}
