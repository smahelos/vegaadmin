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

    public function findByIdForUser(ContactId $id, UserId $userId): ?ContactDTO
    {
        $contact = $this->ofUser($userId)
            ->whereKey($id->value)
            ->first();

        return $contact === null ? null : EloquentContactMapper::toDTO($contact);
    }

    public function countActiveForUser(UserId $userId): int
    {
        return $this->records->countActiveForUser(
            Contact::query(),
            $userId,
            false,
        );
    }

    public function countActiveForUserForUpdate(UserId $userId): int
    {
        return $this->records->countActiveForUser(
            Contact::query(),
            $userId,
            true,
        );
    }

    public function listLatestActiveForUser(UserId $userId, int $limit): array
    {
        return array_map(
            fn (Contact $contact): ContactDTO => EloquentContactMapper::toDTO($contact),
            $this->records->latestActive(
                Contact::query(),
                $userId,
                $limit,
            ),
        );
    }

    public function searchForUser(
        UserId $userId,
        PartySearch $search,
        PageRequest $page,
    ): PaginatedResult {
        $found = $this->records->search(
            Contact::query(),
            $userId,
            $search,
            $page,
        );

        return new PaginatedResult(
            array_map(fn (Contact $contact): ContactDTO => EloquentContactMapper::toDTO($contact), $found->items()),
            $found->total(),
            $found->perPage(),
            $found->currentPage(),
        );
    }

    public function listPossibleDuplicates(
        UserId $userId,
        PartyProfile $profile,
        ?ContactId $except,
    ): array {
        $query = $this->records->possibleDuplicates(
            Contact::query(),
            $userId,
            $profile,
        );
        if ($except !== null) {
            $query->whereKeyNot($except->value);
        }

        return $this->list($query);
    }

    public function create(UserId $userId, ContactWriteData $data): ContactDTO
    {
        $contact = $this->records->save(
            new Contact(['user_id' => $userId->toInt()]),
            EloquentContactMapper::toAttributes($data),
            $data->profile->address,
        );

        return EloquentContactMapper::toDTO($contact);
    }

    public function update(ContactId $id, ContactWriteData $data): ContactDTO
    {
        $contact = $this->records->save(
            Contact::query()->findOrFail($id->value),
            EloquentContactMapper::toAttributes($data),
            $data->profile->address,
        );

        return EloquentContactMapper::toDTO($contact);
    }

    public function archive(ContactId $id, DateTimeImmutable $archived): void
    {
        Contact::query()
            ->whereKey($id->value)
            ->update(['archived_at' => $archived]);
    }

    public function restore(ContactId $id): void
    {
        Contact::query()
            ->whereKey($id->value)
            ->update(['archived_at' => null]);
    }

    /**
     * @return Builder<Contact>
     */
    private function ofUser(UserId $userId): Builder
    {
        return Contact::query()
            ->with('address')
            ->where('user_id', $userId->toInt());
    }

    /**
     * @param  Builder<Contact>  $query
     * @return list<ContactDTO>
     */
    private function list(Builder $query): array
    {
        return $query
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (Contact $contact): ContactDTO => EloquentContactMapper::toDTO($contact))
            ->values()
            ->all();
    }
}
