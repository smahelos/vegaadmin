<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\ExpenseDTO;
use App\Domain\Invoice\ValueObjects\ExpenseId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

interface ExpenseReadRepositoryInterface
{
    public function findById(ExpenseId $id, ReadScope $scope): ?ExpenseDTO;
}
