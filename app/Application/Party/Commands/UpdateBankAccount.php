<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\DTO\BankAccountWriteData;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/** @implements CommandInterface<BankAccountDTO> */
final readonly class UpdateBankAccount extends OwnedUseCase implements CommandInterface
{
    public function __construct(
        UserId $owner,
        public AccountOwner $accountOwner,
        public BankAccountId $id,
        public BankAccountWriteData $data,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::BANK_ACCOUNT_UPDATE);
    }
}
