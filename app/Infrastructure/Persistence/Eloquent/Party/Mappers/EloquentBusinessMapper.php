<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Party\Mappers;

use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\Party\DTO\InvoicingSettings;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Shared\Translation\Enums\Language;
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
            invoicing: new InvoicingSettings(
                vatPayer: $business->is_vat_payer,
                registerEntry: $business->register_entry,
                documentLanguage: Language::from($business->document_language),
            ),
            logo: $business->logo,
            defaultBankAccountId: $business->default_bank_account_id === null ? null : BankAccountId::fromInt($business->default_bank_account_id),
            archived: $business->archived_at,
        );
    }

    /**
     * @return array<string, string|bool|null>
     */
    public static function toAttributes(BusinessWriteData $data): array
    {
        $invoicing = $data->invoicing;

        return EloquentPartyProfileMapper::toAttributes($data->profile) + [
            'logo' => $data->logo,
            'is_vat_payer' => $invoicing->vatPayer,
            'register_entry' => $invoicing->registerEntry,
            'document_language' => $invoicing->documentLanguage->value,
        ];
    }
}
