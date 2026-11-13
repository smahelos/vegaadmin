<?php

declare(strict_types=1);

namespace App\Application\Expense\Services;

use App\Domain\Expense\DTO\ExpenseWriteData;
use App\Domain\Party\Contracts\BankAccountReadRepositoryInterface;
use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Party\Exceptions\ArchivedReferenceSelectedException;
use App\Domain\Party\Exceptions\BankAccountNotFoundException;
use App\Domain\Party\Services\DocumentBusinessAssembler;
use App\Domain\Party\Services\DocumentCounterpartyAssembler;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class ExpensePartiesAssembler
{
    public function __construct(
        private readonly DocumentBusinessAssembler $businesses,
        private readonly DocumentCounterpartyAssembler $counterparties,
        private readonly BankAccountReadRepositoryInterface $accounts,
    ) {}

    public function assemble(
        ReadScope $scope,
        ExpenseWriteData $data,
        ?DocumentParties $stored,
    ): DocumentParties {
        $customer = $this->businesses->assemble(
            $scope,
            $data->businessId,
            $stored,
            false,
            false,
        );
        if ($data->contactId === null) {
            $supplier = $data->supplier;
        } else {
            $supplier = $this->counterparties->assemble(
                $scope,
                $data->contactId,
                $data->supplier,
                $stored?->contactId,
                $stored?->supplier,
                true,
            );
        }

        return new DocumentParties(
            $data->businessId,
            $data->contactId,
            $data->bankAccountId,
            $supplier,
            $customer,
            $this->account(
                $scope,
                $data,
                $stored,
            ),
        );
    }

    private function account(
        ReadScope $scope,
        ExpenseWriteData $data,
        ?DocumentParties $stored,
    ): ?DocumentAccountDTO {
        $id = $data->bankAccountId;
        if ($id === null) {
            return $data->account;
        }
        $account = $this->accounts->findById($id, $scope) ?? throw new BankAccountNotFoundException($id);
        if ($data->contactId === null || $account->owner->contactId?->equals($data->contactId) !== true) {
            throw new BankAccountNotFoundException($id);
        }
        $sameAccount = $stored?->bankAccountId?->equals($id) === true && $stored->contactId?->equals($data->contactId) === true;
        if ($account->isArchived() && !$sameAccount) {
            throw new ArchivedReferenceSelectedException($id);
        }

        return $sameAccount && $stored->account !== null ? $stored->account : DocumentAccountDTO::fromBankAccount($account);
    }
}
