<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Party\Repositories;

use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\Contracts\ContactWriteRepositoryInterface;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\DTO\ContactWriteData;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\DTO\PartySearch;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Party\Mappers\EloquentContactMapper;
use App\Models\Contact;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;

final class EloquentContactRepository implements ContactReadRepositoryInterface, ContactWriteRepositoryInterface
{
    public function __construct(
        private readonly PartyRecords $records,
    ) {}

    public function findById(ContactId $id, ReadScope $scope): ?ContactDTO
    {
        return $this->findByIdForUser($id, $scope->owner);
    }

    public function findByIdForUser(ContactId $id, UserId $userId): ?ContactDTO
    {
        $query = $this->ofUser($userId);
        $query->whereKey($id->value);
        $contact = $query->first();

        return $contact === null ? null : EloquentContactMapper::toDTO($contact);
    }

    public function listLatestActive(ReadScope $scope, int $limit): array
    {
        return array_map(
            fn (Contact $contact): ContactDTO => EloquentContactMapper::toDTO($contact),
            $this->records->latestActive(
                Contact::query(),
                $scope->owner,
                $limit,
            ),
        );
    }

    public function search(
        ReadScope $scope,
        PartySearch $search,
        PageRequest $page,
    ): PaginatedResult {
        $found = $this->records->search(
            Contact::query(),
            $scope->owner,
            $search,
            $page,
        );

        return new PaginatedResult(
            array_map(
                fn (Contact $contact): ContactDTO => EloquentContactMapper::toDTO($contact),
                $found->items(),
            ),
            $found->total(),
            $found->perPage(),
            $found->currentPage(),
        );
    }

    public function listPossibleDuplicates(
        ReadScope $scope,
        PartyProfile $profile,
        ?ContactId $except,
    ): array {
        $query = $this->records->possibleDuplicates(
            Contact::query(),
            $scope->owner,
            $profile,
        );
        if ($except !== null) {
            $query->whereKeyNot($except->value);
        }

        return $this->list($query);
    }

    public function create(WriteScope $scope, ContactWriteData $data): ContactDTO
    {
        $contact = $this->records->save(
            new Contact(['user_id' => $scope->owner->toInt()]),
            EloquentContactMapper::toAttributes($data),
            $data->profile->address,
        );

        return EloquentContactMapper::toDTO($contact);
    }

    public function update(
        ContactId $id,
        WriteScope $scope,
        ContactWriteData $data,
    ): ContactDTO {
        $query = $this->ofUser($scope->owner);
        $contact = $query->findOrFail($id->value);
        $contact = $this->records->save(
            $contact,
            EloquentContactMapper::toAttributes($data),
            $data->profile->address,
        );

        return EloquentContactMapper::toDTO($contact);
    }

    public function archive(
        ContactId $id,
        WriteScope $scope,
        DateTimeImmutable $archived,
    ): void {
        $query = $this->owned($id, $scope);
        $query->update(['archived_at' => $archived]);
    }

    public function restore(ContactId $id, WriteScope $scope): void
    {
        $query = $this->owned($id, $scope);
        $query->update(['archived_at' => null]);
    }

    /**
     * @return Builder<Contact>
     */
    private function owned(ContactId $id, WriteScope $scope): Builder
    {
        $query = Contact::query();
        $query->whereKey($id->value);
        $query->where('user_id', $scope->owner->toInt());

        return $query;
    }

    /**
     * @return Builder<Contact>
     */
    private function ofUser(UserId $userId): Builder
    {
        $query = Contact::query();
        $query->with('address');
        $query->where('user_id', $userId->toInt());

        return $query;
    }

    /**
     * @param  Builder<Contact>  $query
     * @return list<ContactDTO>
     */
    private function list(Builder $query): array
    {
        $query->orderBy('name');
        $query->orderBy('id');
        $contacts = $query->get();
        $contacts = $contacts->map(
            fn (Contact $contact): ContactDTO => EloquentContactMapper::toDTO($contact),
        );
        $contacts = $contacts->values();

        return $contacts->all();
    }
}
