<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Invoice\Services\DocumentLogoApplicationService;
use App\Application\Invoice\Services\ExpensePartiesAssembler;
use App\Application\Invoice\Support\ExpenseAttachments;
use App\Domain\Invoice\Contracts\ExpenseWriteRepositoryInterface;
use App\Domain\Invoice\DTO\ExpenseDTO;
use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Services\EntityLimit;

final class CreateExpenseHandler
{
    public function __construct(
        private readonly ExpenseWriteRepositoryInterface $writer,
        private readonly ExpensePartiesAssembler $parties,
        private readonly ContactReadRepositoryInterface $contacts,
        private readonly ExpenseAttachments $attachments,
        private readonly DocumentLogoApplicationService $logos,
        private readonly TransactionBoundaryInterface $transactions,
        private readonly EntityLimit $limits,
    ) {}

    public function handle(CreateExpense $command, WriteScope $scope): ExpenseDTO
    {
        $data = $command->data;
        $contact = null;
        if ($data->contactId !== null) {
            $contact = $this->contacts->findById($data->contactId, $scope->read)
                ?? throw new ContactNotFoundException($data->contactId);
        }

        return $this->attachments->saveWithAttachments(
            [],
            $data,
            fn (callable $attachments): ExpenseDTO => $this->logos->saveWithLogo(
                null,
                false,
                $contact?->logo,
                $data->contactId === null ? $data->supplierLogo : null,
                fn (?string $logo): ExpenseDTO => $this->transactions->transaction(
                    function () use ($scope, $data, $logo, $attachments): ExpenseDTO {
                        $this->limits->assertCanCreate($scope->owner, LimitedEntity::EXPENSE);

                        $parties = $this->parties->assemble(
                            $scope->read,
                            $data,
                            null,
                        );
                        $parties = $parties->withSupplierLogo($logo);

                        return $this->writer->create(
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
