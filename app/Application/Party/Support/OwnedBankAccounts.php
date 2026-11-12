<?php

declare(strict_types=1);

namespace App\Application\Party\Support;

use App\Domain\Party\Contracts\BankAccountReadRepositoryInterface;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\Exceptions\BankAccountNotFoundException;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

/**
 * Finds the business or contact an account belongs to within the scope, and the account within its owner.
 */
final class OwnedBankAccounts
{
    public function __construct(
        private readonly BankAccountReadRepositoryInterface $accounts,
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly ContactReadRepositoryInterface $contacts,
    ) {}

    /**
     * @throws BusinessNotFoundException
     * @throws ContactNotFoundException
     */
    public function assertOwner(ReadScope $scope, AccountOwner $owner): void
    {
        if ($owner->businessId !== null && $this->businesses->findById($owner->businessId, $scope) === null) {
            throw new BusinessNotFoundException($owner->businessId);
        }
        if ($owner->contactId !== null && $this->contacts->findById($owner->contactId, $scope) === null) {
            throw new ContactNotFoundException($owner->contactId);
        }
    }

    /**
     * @throws BankAccountNotFoundException when the account is missing, outside the scope or of another owner
     */
    public function get(
        ReadScope $scope,
        AccountOwner $owner,
        BankAccountId $id,
    ): BankAccountDTO {
        $account = $this->accounts->findById($id, $scope)
            ?? throw new BankAccountNotFoundException($id);
        if (
            $account->owner->businessId?->value !== $owner->businessId?->value
            || $account->owner->contactId?->value !== $owner->contactId?->value
        ) {
            throw new BankAccountNotFoundException($id);
        }

        return $account;
    }
}
