<?php

declare(strict_types=1);

namespace App\Application\Invoice\DTO;

use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\DocumentPartyDTO;

final readonly class DocumentPartyOptionDTO
{
    public function __construct(
        public int $id,
        public string $label,
        public ?bool $isDefault = null,
        public ?DocumentPartyDTO $profile = null,
        public ?DocumentAccountDTO $account = null,
        public ?string $logo = null,
    ) {}

    public static function fromParty(BusinessDTO|ContactDTO $party): self
    {
        return new self(
            $party->id->value,
            $party->profile->name,
            profile: DocumentPartyDTO::fromProfile($party->profile),
            logo: $party->logo,
        );
    }

    public static function fromBankAccount(BankAccountDTO $account, bool $isDefault): self
    {
        return new self(
            $account->id->value,
            $account->label ?? $account->account->iban,
            $isDefault,
            account: DocumentAccountDTO::fromBankAccount($account),
        );
    }
}
