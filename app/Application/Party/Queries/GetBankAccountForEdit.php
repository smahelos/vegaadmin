<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * The account for its edit form, which declares the permission of the update it leads to.
 *
 * @implements QueryInterface<BankAccountDTO>
 */
final readonly class GetBankAccountForEdit extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public AccountOwner $accountOwner,
        public BankAccountId $id,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::BANK_ACCOUNT_UPDATE);
    }
}
