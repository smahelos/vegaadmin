<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Invoice\Services\DocumentLogoApplicationService;
use App\Application\Invoice\Services\ExpensePartiesAssembler;
use App\Application\Invoice\Support\ExpenseAttachments;
use App\Domain\Invoice\Contracts\ExpenseReadRepositoryInterface;
use App\Domain\Invoice\Contracts\ExpenseWriteRepositoryInterface;
use App\Domain\Invoice\DTO\ExpenseDTO;
use App\Domain\Invoice\Exceptions\ExpenseNotFoundException;
use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;

final class UpdateExpenseHandler
{
    public function __construct(
        private readonly ExpenseReadRepositoryInterface $reader,
        private readonly ExpenseWriteRepositoryInterface $writer,
        private readonly ExpensePartiesAssembler $parties,
        private readonly ContactReadRepositoryInterface $contacts,
        private readonly ExpenseAttachments $attachments,
        private readonly DocumentLogoApplicationService $logos,
        private readonly TransactionBoundaryInterface $transactions,
    ) {}

    public function handle(UpdateExpense $command, WriteScope $scope): ExpenseDTO
    {
        $data = $command->data;
        $stored = $this->reader->findById($command->id, $scope->read)
            ?? throw new ExpenseNotFoundException('Expense not accessible for user');
        $contact = null;
        if ($data->contactId !== null) {
            $contact = $this->contacts->findById($data->contactId, $scope->read)
                ?? throw new ContactNotFoundException($data->contactId);
        }
        $sameSupplier = $stored->parties->contactId?->value === $data->contactId?->value;
        $keepLogo = $sameSupplier && ($data->contactId !== null || ($data->supplierLogo === null && !$data->removeSupplierLogo));

        return $this->attachments->saveWithAttachments(
            $stored->attachments,
            $data,
            fn (callable $attachments): ExpenseDTO => $this->logos->saveWithLogo(
                $stored->parties->supplierLogo,
                $keepLogo,
                $contact?->logo,
                $data->contactId === null ? $data->supplierLogo : null,
                fn (?string $logo): ExpenseDTO => $this->transactions->transaction(
                    function () use ($scope, $data, $stored, $logo, $attachments): ExpenseDTO {
                        $parties = $this->parties->assemble(
                            $scope->read,
                            $data,
                            $stored->parties,
                        );
                        $parties = $parties->withSupplierLogo($logo);

                        return $this->writer->update(
                            $stored->id,
                            $scope,
                            $data,
                            $parties,
                            $attachments(),
                        );
                    },
                ),
            ),
        );
    }
}
