<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Party\Repositories;

use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\DTO\PartySearch;
use App\Domain\Party\Enums\PartySort;
use App\Domain\Party\Exceptions\DuplicateCompanyNumberException;
use App\Domain\Party\ValueObjects\Address;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Party\Mappers\EloquentPartyProfileMapper;
use App\Models\Address as AddressModel;
use App\Models\Business;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Persistence that businesses and contacts share: the address, the Czech IČO key and the duplicate candidates.
 */
final class PartyRecords
{
    private const DUPLICATE_KEY_ERROR = 1062;

    /** @param Builder<Business>|Builder<Contact> $query */
    public function countActiveForUser(
        Builder $query,
        UserId $userId,
        bool $forUpdate,
    ): int {
        $query->where('user_id', $userId->toInt());
        $query->whereNull('archived_at');
        if ($forUpdate) {
            // The owner lock serializes activations; a locking read also sees commits after an earlier snapshot.
            $query->lockForUpdate();
        }

        return $query->count();
    }

    /**
     * @template T of Business|Contact
     *
     * @param  Builder<T>  $query
     * @return list<T>
     */
    public function latestActive(
        Builder $query,
        UserId $userId,
        int $limit,
    ): array {
        $query->with('address');
        $query->where('user_id', $userId->toInt());
        $query->whereNull('archived_at');
        $query->orderByDesc('created_at');
        $query->orderByDesc('id');
        $query->limit($limit);
        $parties = $query->get();

        return $parties->all();
    }

    /**
     * Saves the party with the given attributes and its address in one transaction, and turns a clash on the
     * Czech IČO key, which also catches concurrent inserts, into a domain exception that names the existing record.
     *
     * @template T of Business|Contact
     *
     * @param  T  $party
     * @param  array<string, string|null>  $attributes
     * @return T
     */
    public function save(
        Business|Contact $party,
        array $attributes,
        ?Address $address,
    ): Business|Contact {
        try {
            DB::transaction(
                function () use ($party, $attributes, $address): void {
                    $party->fill($attributes);
                    $party->save();
                    $this->saveAddress($party, $address);
                },
            );
        } catch (QueryException $e) {
            throw $this->duplicateOf($party, $e) ?? $e;
        }

        return $party->load('address');
    }

    /**
     * Matches on the registration identity, the name or the email, archived records included; DuplicateFinder
     * decides which of them are duplicates.
     *
     * @template T of Business|Contact
     *
     * @param  Builder<T>  $query
     * @return Builder<T>
     */
    public function possibleDuplicates(
        Builder $query,
        UserId $userId,
        PartyProfile $profile,
    ): Builder {
        $query->with('address');
        $query->where('user_id', $userId->toInt());
        $query->where(
            function (Builder $match) use ($profile): void {
                $match->where('name', $profile->name);

                if ($profile->registrationNumber !== null) {
                    $match->orWhere(
                        function (Builder $number) use ($profile): void {
                            $number->where('registration_country', $profile->registrationNumber?->country->value);
                            $number->where('company_number', $profile->registrationNumber?->number);
                        },
                    );
                }

                if ($profile->email !== null) {
                    $match->orWhere('email', $profile->email);
                }
            },
        );

        return $query;
    }

    /**
     * One page of the user's active or archived parties; the name and id keep the order stable within equal values.
     *
     * @template T of Business|Contact
     *
     * @param  Builder<T>  $query
     * @return LengthAwarePaginator<int, T>
     */
    public function search(
        Builder $query,
        UserId $userId,
        PartySearch $search,
        PageRequest $page,
    ): LengthAwarePaginator {
        $query->with('address');
        $query->where('user_id', $userId->toInt());

        if ($search->archived) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        $term = trim($search->term ?? '');
        if ($term !== '') {
            $pattern = '%' . addcslashes($term, '%_\\') . '%';
            $query->where(
                function (Builder $match) use ($pattern): void {
                    $match->where(
                        'name',
                        'like',
                        $pattern,
                    );
                    $match->orWhere(
                        'short_name',
                        'like',
                        $pattern,
                    );
                    $match->orWhere(
                        'company_number',
                        'like',
                        $pattern,
                    );
                    $match->orWhere(
                        'email',
                        'like',
                        $pattern,
                    );
                    $match->orWhereHas(
                        'address',
                        fn (Builder $address): Builder => $address->where(
                            'city',
                            'like',
                            $pattern,
                        ),
                    );
                },
            );
        }

        $direction = $search->ascending ? 'asc' : 'desc';
        $this->orderBy(
            $query,
            $search->sort,
            $direction,
        );

        $query->orderBy('name');
        $query->orderBy('id');

        return $query->paginate(
            $page->perPage(),
            ['*'],
            'page',
            $page->page(),
        );
    }

    /**
     * One address per owner; an empty address is not stored.
     */
    private function saveAddress(Business|Contact $party, ?Address $address): void
    {
        $relation = $party->address();
        if ($address === null) {
            $relation->delete();

            return;
        }

        $relation->updateOrCreate(
            [],
            EloquentPartyProfileMapper::toAddressAttributes($address),
        );
    }

    /**
     * @param  Builder<Business>|Builder<Contact>  $query
     */
    private function orderBy(
        Builder $query,
        PartySort $sort,
        string $direction,
    ): void {
        if ($sort === PartySort::CITY) {
            $model = $query->getModel();
            $foreignKey = $model instanceof Contact ? 'contact_id' : 'business_id';
            $cityQuery = AddressModel::query();
            $cityQuery->select('city');
            $cityQuery->whereColumn($foreignKey, $model->getQualifiedKeyName());
            $cityQuery->limit(1);
            $query->orderBy(
                $cityQuery,
                $direction,
            );

            return;
        }

        $column = match ($sort) {
            PartySort::NAME => 'name',
            PartySort::SHORT_NAME => 'short_name',
            PartySort::COMPANY_NUMBER => 'company_number',
            PartySort::EMAIL => 'email',
            PartySort::CREATED => 'created_at',
        };
        $query->orderBy($column, $direction);
    }

    private function duplicateOf(Business|Contact $party, QueryException $e): ?DuplicateCompanyNumberException
    {
        if (
            ($e->errorInfo[1] ?? null) !== self::DUPLICATE_KEY_ERROR
            || !str_contains($e->getMessage(), 'czech_company_number')
        ) {
            return null;
        }

        $query = $party::query();
        $query->where('user_id', $party->user_id);
        $query->where('czech_company_number', $party->company_number);
        $existing = $query->value('id');
        if ($existing === null) {
            return null;
        }

        return new DuplicateCompanyNumberException(
            $party instanceof Contact ? ContactId::fromInt($existing) : BusinessId::fromInt($existing),
        );
    }
}
