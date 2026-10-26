<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Party\Mappers;

use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Business;

final class EloquentBusinessMapper
{
    /**
     * Expects the address relation to be loaded.
     */
    public static function toDTO(Business $business): BusinessDTO
    {
        return new BusinessDTO(
            id: BusinessId::fromInt($business->id),
            userId: UserId::fromInt($business->user_id),
            profile: EloquentPartyProfileMapper::toProfile($business),
            logo: $business->logo,
            defaultBankAccountId: $business->default_bank_account_id === null
                ? null
                : BankAccountId::fromInt($business->default_bank_account_id),
            archived: $business->archived_at,
        );
    }

    /**
     * @return array<string, string|null>
     */
    public static function toAttributes(BusinessWriteData $data): array
    {
        return EloquentPartyProfileMapper::toAttributes($data->profile) + ['logo' => $data->logo];
    }
}
