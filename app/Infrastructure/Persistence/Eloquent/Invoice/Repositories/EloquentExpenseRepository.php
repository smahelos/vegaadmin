<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Repositories;

use App\Domain\Invoice\Contracts\ExpenseReadRepositoryInterface;
use App\Domain\Invoice\Contracts\ExpenseWriteRepositoryInterface;
use App\Domain\Invoice\DTO\ExpenseDTO;
use App\Domain\Invoice\DTO\ExpenseWriteData;
use App\Domain\Invoice\ValueObjects\ExpenseId;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Invoice\Mappers\EloquentExpenseMapper;
use App\Models\Expense;

final class EloquentExpenseRepository implements ExpenseReadRepositoryInterface, ExpenseWriteRepositoryInterface
{
    public function findByIdForUser(ExpenseId $id, UserId $userId): ?ExpenseDTO
    {
        $query = Expense::query();
        $query->whereKey($id->value);
        $query->where('user_id', $userId->toInt());
        $expense = $query->first();

        return $expense === null ? null : EloquentExpenseMapper::toDTO($expense);
    }

    public function create(
        UserId $userId,
        ExpenseWriteData $data,
        DocumentParties $parties,
        array $attachments,
    ): ExpenseDTO {
        return EloquentExpenseMapper::toDTO(
            Expense::create(
                EloquentExpenseMapper::toAttributes(
                    $userId,
                    $data,
                    $parties,
                    $attachments,
                ),
            ),
        );
    }

    public function update(
        ExpenseId $id,
        UserId $userId,
        ExpenseWriteData $data,
        DocumentParties $parties,
        array $attachments,
    ): ExpenseDTO {
        $query = Expense::query();
        $query->whereKey($id->value);
        $query->where('user_id', $userId->toInt());
        $expense = $query->firstOrFail();
        $expense->fill(
            EloquentExpenseMapper::toAttributes(
                $userId,
                $data,
                $parties,
                $attachments,
            ),
        );
        $expense->save();

        return EloquentExpenseMapper::toDTO($expense);
    }
}
