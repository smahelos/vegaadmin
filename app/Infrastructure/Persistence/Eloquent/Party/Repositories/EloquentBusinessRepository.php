<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Party\Repositories;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Contracts\BusinessWriteRepositoryInterface;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\DTO\PartySearch;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Party\Mappers\EloquentBusinessMapper;
use App\Models\Business;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;

final class EloquentBusinessRepository implements BusinessReadRepositoryInterface, BusinessWriteRepositoryInterface
{
    public function __construct(
        private readonly PartyRecords $records,
    ) {}

    public function findById(BusinessId $id, ReadScope $scope): ?BusinessDTO
    {
        return $this->findByIdForUser($id, $scope->owner);
    }

    public function findByIdForUser(BusinessId $id, UserId $userId): ?BusinessDTO
    {
        $query = $this->ofUser($userId);
        $query->whereKey($id->value);
        $business = $query->first();

        return $business === null ? null : EloquentBusinessMapper::toDTO($business);
    }

    public function listActive(ReadScope $scope): array
    {
        $query = $this->ofUser($scope->owner);
        $query->whereNull('archived_at');

        return $this->list($query);
    }

    public function listLatestActive(ReadScope $scope, int $limit): array
    {
        return array_map(
            fn (Business $business): BusinessDTO => EloquentBusinessMapper::toDTO($business),
            $this->records->latestActive(
                Business::query(),
                $scope->owner,
                $limit,
            ),
        );
    }

    public function countActive(ReadScope $scope): int
    {
        return $this->records->countActiveForUser(
            Business::query(),
            $scope->owner,
            false,
        );
    }

    public function countActiveForUpdate(ReadScope $scope): int
    {
        return $this->records->countActiveForUser(
            Business::query(),
            $scope->owner,
            true,
        );
    }

    public function findDefaultId(ReadScope $scope): ?BusinessId
    {
        $query = User::query();
        $query->whereKey($scope->owner->toInt());
        $id = $query->value('default_business_id');

        return $id === null ? null : BusinessId::fromInt($id);
    }

    public function search(
        ReadScope $scope,
        PartySearch $search,
        PageRequest $page,
    ): PaginatedResult {
        $found = $this->records->search(
            Business::query(),
            $scope->owner,
            $search,
            $page,
        );

        return new PaginatedResult(
            array_map(
                fn (Business $business): BusinessDTO => EloquentBusinessMapper::toDTO($business),
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
        ?BusinessId $except,
    ): array {
        $query = $this->records->possibleDuplicates(
            Business::query(),
            $scope->owner,
            $profile,
        );
        if ($except !== null) {
            $query->whereKeyNot($except->value);
        }

        return $this->list($query);
    }

    public function create(WriteScope $scope, BusinessWriteData $data): BusinessDTO
    {
        $business = $this->records->save(
            new Business(['user_id' => $scope->owner->toInt()]),
            EloquentBusinessMapper::toAttributes($data),
            $data->profile->address,
        );

        return EloquentBusinessMapper::toDTO($business);
    }

    public function update(
        BusinessId $id,
        WriteScope $scope,
        BusinessWriteData $data,
    ): BusinessDTO {
        $query = $this->ofUser($scope->owner);
        $business = $query->findOrFail($id->value);
        $business = $this->records->save(
            $business,
            EloquentBusinessMapper::toAttributes($data),
            $data->profile->address,
        );

        return EloquentBusinessMapper::toDTO($business);
    }

    public function archive(
        BusinessId $id,
        WriteScope $scope,
        DateTimeImmutable $archived,
    ): void {
        $query = $this->owned($id, $scope);
        $query->update(['archived_at' => $archived]);
    }

    public function restore(BusinessId $id, WriteScope $scope): void
    {
        $query = $this->owned($id, $scope);
        $query->update(['archived_at' => null]);
    }

    public function setDefault(WriteScope $scope, ?BusinessId $id): void
    {
        $query = User::query();
        $query->whereKey($scope->owner->toInt());
        $query->update(['default_business_id' => $id?->value]);
    }

    /**
     * @return Builder<Business>
     */
    private function owned(BusinessId $id, WriteScope $scope): Builder
    {
        $query = Business::query();
        $query->whereKey($id->value);
        $query->where('user_id', $scope->owner->toInt());

        return $query;
    }

    /**
     * @return Builder<Business>
     */
    private function ofUser(UserId $userId): Builder
    {
        $query = Business::query();
        $query->with('address');
        $query->where('user_id', $userId->toInt());

        return $query;
    }

    /**
     * @param  Builder<Business>  $query
     * @return list<BusinessDTO>
     */
    private function list(Builder $query): array
    {
        $query->orderBy('name');
        $query->orderBy('id');
        $businesses = $query->get();
        $businesses = $businesses->map(
            fn (Business $business): BusinessDTO => EloquentBusinessMapper::toDTO($business),
        );
        $businesses = $businesses->values();

        return $businesses->all();
    }
}
