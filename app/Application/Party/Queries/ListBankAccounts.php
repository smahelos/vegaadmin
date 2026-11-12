<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * The accounts of a business or contact, archived included.
 *
 * @implements QueryInterface<list<BankAccountDTO>>
 */
final readonly class ListBankAccounts extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public AccountOwner $accountOwner,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::BANK_ACCOUNT_VIEW);
    }
}
