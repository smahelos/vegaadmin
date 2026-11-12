<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Party\Repositories;

use App\Domain\Party\Contracts\BankAccountReadRepositoryInterface;
use App\Domain\Party\Contracts\BankAccountWriteRepositoryInterface;
use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\DTO\BankAccountWriteData;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Party\Mappers\EloquentBankAccountMapper;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;

final class EloquentBankAccountRepository implements BankAccountReadRepositoryInterface, BankAccountWriteRepositoryInterface
{
    public function findById(BankAccountId $id, ReadScope $scope): ?BankAccountDTO
    {
        $query = $this->ofUser($scope->owner);
        $query->whereKey($id->value);
        $account = $query->first();

        return $account === null ? null : EloquentBankAccountMapper::toDTO($account);
    }

    public function listForOwner(ReadScope $scope, AccountOwner $owner): array
    {
        $query = $this->ofUser($scope->owner);
        $query->where(
            EloquentBankAccountMapper::toOwnerAttributes($owner),
        );
        $query->orderBy('id');
        $accounts = $query->get();
        $accounts = $accounts->map(
            fn (BankAccount $account): BankAccountDTO => EloquentBankAccountMapper::toDTO($account),
        );
        $accounts = $accounts->values();

        return $accounts->all();
    }

    public function findDefaultIdForOwner(ReadScope $scope, AccountOwner $owner): ?BankAccountId
    {
        $query = $this->ownerQuery($owner);
        $query->where('user_id', $scope->owner->toInt());
        $id = $query->value('default_bank_account_id');

        return $id === null ? null : BankAccountId::fromInt($id);
    }

    public function create(
        WriteScope $scope,
        AccountOwner $owner,
        BankAccountWriteData $data,
    ): BankAccountDTO {
        $ownerQuery = $this->ownerQuery($owner);
        $ownerQuery->where('user_id', $scope->owner->toInt());
        // Exactly one id is set, so the owner is looked up once
        if ($owner->businessId !== null && !$ownerQuery->exists()) {
            throw new BusinessNotFoundException($owner->businessId);
        }
        if ($owner->contactId !== null && !$ownerQuery->exists()) {
            throw new ContactNotFoundException($owner->contactId);
        }
        $query = BankAccount::query();
        $account = $query->create(
            EloquentBankAccountMapper::toOwnerAttributes($owner) + EloquentBankAccountMapper::toAttributes($data),
        );

        return EloquentBankAccountMapper::toDTO($account);
    }

    public function update(
        BankAccountId $id,
        WriteScope $scope,
        BankAccountWriteData $data,
    ): BankAccountDTO {
        $query = $this->ofUser($scope->owner);
        $account = $query->findOrFail($id->value);
        $account->update(
            EloquentBankAccountMapper::toAttributes($data),
        );

        return EloquentBankAccountMapper::toDTO($account);
    }

    public function archive(
        BankAccountId $id,
        WriteScope $scope,
        DateTimeImmutable $archived,
    ): void {
        $query = $this->ofUser($scope->owner);
        $query->whereKey($id->value);
        $query->update(['archived_at' => $archived]);
    }

    public function restore(BankAccountId $id, WriteScope $scope): void
    {
        $query = $this->ofUser($scope->owner);
        $query->whereKey($id->value);
        $query->update(['archived_at' => null]);
    }

    public function setDefaultForOwner(
        WriteScope $scope,
        AccountOwner $owner,
        ?BankAccountId $id,
    ): void {
        $query = $this->ownerQuery($owner);
        $query->where('user_id', $scope->owner->toInt());
        $query->update(['default_bank_account_id' => $id?->value]);
    }

    /**
     * Accounts whose business or contact belongs to the user.
     *
     * @return Builder<BankAccount>
     */
    private function ofUser(UserId $userId): Builder
    {
        $query = BankAccount::query();
        $query->where(
            function (Builder $owner) use ($userId): void {
                $owner->whereHas(
                    'business',
                    fn (Builder $business) => $business->where('user_id', $userId->toInt()),
                );
                $owner->orWhereHas(
                    'contact',
                    fn (Builder $contact) => $contact->where('user_id', $userId->toInt()),
                );
            },
        );

        return $query;
    }

    /**
     * @return Builder<Business>|Builder<Contact>
     */
    private function ownerQuery(AccountOwner $owner): Builder
    {
        if ($owner->businessId !== null) {
            $query = Business::query();
            $query->whereKey($owner->businessId->value);

            return $query;
        }

        $query = Contact::query();
        $query->whereKey($owner->contactId?->value);

        return $query;
    }
}
