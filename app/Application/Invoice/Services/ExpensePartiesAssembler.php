<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services;

use App\Domain\Invoice\DTO\ExpenseWriteData;
use App\Domain\Party\Contracts\BankAccountReadRepositoryInterface;
use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Party\Exceptions\ArchivedReferenceSelectedException;
use App\Domain\Party\Exceptions\BankAccountNotFoundException;
use App\Domain\User\ValueObjects\UserId;

final class ExpensePartiesAssembler
{
    public function __construct(
        private readonly DocumentBusinessAssembler $businesses,
        private readonly DocumentCounterpartyAssembler $counterparties,
        private readonly BankAccountReadRepositoryInterface $accounts,
    ) {}

    public function assemble(
        UserId $userId,
        ExpenseWriteData $data,
        ?DocumentParties $stored,
    ): DocumentParties {
        $customer = $this->businesses->assemble(
            $userId,
            $data->businessId,
            $stored,
            false,
            false,
        );
        $supplier = $data->contactId === null ? $data->supplier : $this->counterparties->assemble(
            $userId,
            $data->contactId,
            $data->supplier,
            $stored?->contactId,
            $stored?->supplier,
            true,
        );

        return new DocumentParties(
            $data->businessId,
            $data->contactId,
            $data->bankAccountId,
            $supplier,
            $customer,
            $this->account(
                $userId,
                $data,
                $stored,
            ),
        );
    }

    private function account(
        UserId $userId,
        ExpenseWriteData $data,
        ?DocumentParties $stored,
    ): ?DocumentAccountDTO {
        $id = $data->bankAccountId;
        if ($id === null) {
            return $data->account;
        }
        $account = $this->accounts->findByIdForUser($id, $userId) ?? throw new BankAccountNotFoundException($id);
        if ($data->contactId === null || $account->owner->contactId?->equals($data->contactId) !== true) {
            throw new BankAccountNotFoundException($id);
        }
        $sameAccount = $stored?->bankAccountId?->equals($id) === true && $stored->contactId?->equals($data->contactId) === true;
        if ($account->isArchived() && ! $sameAccount) {
            throw new ArchivedReferenceSelectedException($id);
        }

        return $sameAccount && $stored->account !== null ? $stored->account : DocumentAccountDTO::fromBankAccount($account);
    }
}
