<?php

declare(strict_types=1);

namespace App\Domain\Party\DTO;

use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;

final readonly class BusinessDTO
{
    public function __construct(
        public BusinessId $id,
        public UserId $userId,
        public PartyProfile $profile,
        public ?string $logo,
        public ?BankAccountId $defaultBankAccountId,
        public ?DateTimeImmutable $archived,
    ) {}

    public function isArchived(): bool
    {
        return $this->archived !== null;
    }
}
