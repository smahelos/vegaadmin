<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\ExpenseDTO;
use App\Domain\Invoice\ValueObjects\ExpenseId;
use App\Domain\User\ValueObjects\UserId;

interface ExpenseReadRepositoryInterface
{
    public function findByIdForUser(ExpenseId $id, UserId $userId): ?ExpenseDTO;
}
