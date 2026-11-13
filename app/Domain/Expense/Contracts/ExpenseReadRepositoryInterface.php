<?php

declare(strict_types=1);

namespace App\Domain\Expense\Contracts;

use App\Domain\Expense\DTO\ExpenseDTO;
use App\Domain\Expense\ValueObjects\ExpenseId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

interface ExpenseReadRepositoryInterface
{
    public function findById(ExpenseId $id, ReadScope $scope): ?ExpenseDTO;
}
