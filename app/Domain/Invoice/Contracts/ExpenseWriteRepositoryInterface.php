<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\ExpenseDTO;
use App\Domain\Invoice\DTO\ExpenseWriteData;
use App\Domain\Invoice\ValueObjects\ExpenseId;
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
