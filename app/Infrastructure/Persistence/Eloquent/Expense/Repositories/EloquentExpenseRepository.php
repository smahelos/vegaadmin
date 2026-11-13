<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Expense\Repositories;

use App\Domain\Expense\Contracts\ExpenseReadRepositoryInterface;
use App\Domain\Expense\Contracts\ExpenseWriteRepositoryInterface;
use App\Domain\Expense\DTO\ExpenseDTO;
use App\Domain\Expense\DTO\ExpenseWriteData;
use App\Domain\Expense\ValueObjects\ExpenseId;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Infrastructure\Persistence\Eloquent\Expense\Mappers\EloquentExpenseMapper;
use App\Models\Expense;

final class EloquentExpenseRepository implements ExpenseReadRepositoryInterface, ExpenseWriteRepositoryInterface
{
    public function findById(ExpenseId $id, ReadScope $scope): ?ExpenseDTO
    {
        $query = Expense::query();
        $query->whereKey($id->value);
        $query->where('user_id', $scope->owner->toInt());
        $expense = $query->first();

        return $expense === null ? null : EloquentExpenseMapper::toDTO($expense);
    }

    public function create(
        WriteScope $scope,
        ExpenseWriteData $data,
        DocumentParties $parties,
        array $attachments,
    ): ExpenseDTO {
        return EloquentExpenseMapper::toDTO(
            Expense::create(
                EloquentExpenseMapper::toAttributes(
                    $scope->owner,
                    $data,
                    $parties,
                    $attachments,
                ),
            ),
        );
    }

    public function update(
        ExpenseId $id,
        WriteScope $scope,
        ExpenseWriteData $data,
        DocumentParties $parties,
        array $attachments,
    ): ExpenseDTO {
        $query = Expense::query();
        $query->whereKey($id->value);
        $query->where('user_id', $scope->owner->toInt());
        $expense = $query->firstOrFail();
        $expense->fill(
            EloquentExpenseMapper::toAttributes(
                $scope->owner,
                $data,
                $parties,
                $attachments,
            ),
        );
        $expense->save();

        return EloquentExpenseMapper::toDTO($expense);
    }
}
