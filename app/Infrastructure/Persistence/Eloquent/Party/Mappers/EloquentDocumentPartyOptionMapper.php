<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Party\Mappers;

use App\Models\BankAccount;

final class EloquentDocumentPartyOptionMapper
{
    public static function label(BankAccount $account): string
    {
        return $account->label ?? $account->iban ?? $account->number ?? '';
    }
}
