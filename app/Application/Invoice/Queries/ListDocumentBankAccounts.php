<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * The bank accounts of the owner's business or contact, archived included; empty for a party outside the scope.
 *
 * @implements QueryInterface<list<BankAccountDTO>>
 */
final readonly class ListDocumentBankAccounts extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public AccountOwner $accountOwner,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::anyOf(
            Permission::INVOICE_CREATE,
            Permission::INVOICE_UPDATE,
            Permission::EXPENSE_CREATE,
            Permission::EXPENSE_UPDATE,
        );
    }
}
