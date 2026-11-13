<?php

declare(strict_types=1);

namespace App\Application\Expense\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CallsOutsideDatabaseInterface;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Expense\DTO\ExpenseDTO;
use App\Domain\Expense\DTO\ExpenseWriteData;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * Stores the supplier's logo and the attachments, so the gate opens no transaction.
 *
 * @implements CommandInterface<ExpenseDTO>
 */
final readonly class CreateExpense extends OwnedUseCase implements CallsOutsideDatabaseInterface, CommandInterface
{
    public function __construct(
        UserId $owner,
        public ExpenseWriteData $data,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permissionWithOwner(Permission::EXPENSE_CREATE);
    }
}
