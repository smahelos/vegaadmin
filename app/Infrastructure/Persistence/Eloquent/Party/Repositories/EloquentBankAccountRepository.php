<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Party\Repositories;

use App\Domain\Party\Contracts\BankAccountReadRepositoryInterface;
use App\Domain\Party\Contracts\BankAccountWriteRepositoryInterface;
use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\DTO\BankAccountWriteData;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Party\Mappers\EloquentBankAccountMapper;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;

final class EloquentBankAccountRepository implements BankAccountReadRepositoryInterface, BankAccountWriteRepositoryInterface
{
    public function findByIdForUser(BankAccountId $id, UserId $userId): ?BankAccountDTO
    {
        $account = BankAccount::query()
            ->whereKey($id->value)
            ->where(function (Builder $owner) use ($userId): void {
                $owner
                    ->whereHas('business', fn (Builder $business) => $business->where('user_id', $userId->toInt()))
                    ->orWhereHas('contact', fn (Builder $contact) => $contact->where('user_id', $userId->toInt()));
            })
            ->first();

        return $account === null ? null : EloquentBankAccountMapper::toDTO($account);
    }

    public function listForOwner(AccountOwner $owner): array
    {
        return BankAccount::query()
            ->where(EloquentBankAccountMapper::toOwnerAttributes($owner))
            ->orderBy('id')
            ->get()
            ->map(fn (BankAccount $account): BankAccountDTO => EloquentBankAccountMapper::toDTO($account))
            ->values()
            ->all();
    }

    public function findDefaultIdForOwner(AccountOwner $owner): ?BankAccountId
    {
        $id = $this->ownerQuery($owner)->value('default_bank_account_id');

        return $id === null ? null : BankAccountId::fromInt($id);
    }

    public function create(AccountOwner $owner, BankAccountWriteData $data): BankAccountDTO
    {
        $account = BankAccount::query()->create(
            EloquentBankAccountMapper::toOwnerAttributes($owner) + EloquentBankAccountMapper::toAttributes($data),
        );

        return EloquentBankAccountMapper::toDTO($account);
    }

    public function update(BankAccountId $id, BankAccountWriteData $data): BankAccountDTO
    {
        $account = BankAccount::query()->findOrFail($id->value);
        $account->update(EloquentBankAccountMapper::toAttributes($data));

        return EloquentBankAccountMapper::toDTO($account);
    }

    public function archive(BankAccountId $id, DateTimeImmutable $archived): void
    {
        BankAccount::query()
            ->whereKey($id->value)
            ->update(['archived_at' => $archived]);
    }

    public function restore(BankAccountId $id): void
    {
        BankAccount::query()
            ->whereKey($id->value)
            ->update(['archived_at' => null]);
    }

    public function setDefaultForOwner(AccountOwner $owner, ?BankAccountId $id): void
    {
        $this->ownerQuery($owner)->update(['default_bank_account_id' => $id?->value]);
    }

    /**
     * @return Builder<Business>|Builder<Contact>
     */
    private function ownerQuery(AccountOwner $owner): Builder
    {
        if ($owner->businessId !== null) {
            return Business::query()->whereKey($owner->businessId->value);
        }

        return Contact::query()->whereKey($owner->contactId?->value);
    }
}
