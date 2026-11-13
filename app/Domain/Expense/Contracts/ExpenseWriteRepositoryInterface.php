<?php

declare(strict_types=1);

namespace App\Domain\Expense\Contracts;

use App\Domain\Expense\DTO\ExpenseDTO;
use App\Domain\Expense\DTO\ExpenseWriteData;
use App\Domain\Expense\ValueObjects\ExpenseId;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;

interface ExpenseWriteRepositoryInterface
{
    /** @param list<string> $attachments */
    public function create(
        WriteScope $scope,
        ExpenseWriteData $data,
        DocumentParties $parties,
        array $attachments,
    ): ExpenseDTO;

    /** @param list<string> $attachments */
    public function update(
        ExpenseId $id,
        WriteScope $scope,
        ExpenseWriteData $data,
        DocumentParties $parties,
        array $attachments,
    ): ExpenseDTO;
}
