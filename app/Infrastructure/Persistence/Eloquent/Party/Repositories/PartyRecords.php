<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Party\Repositories;

use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\Exceptions\DuplicateCompanyNumberException;
use App\Domain\Party\ValueObjects\Address;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Party\Mappers\EloquentPartyProfileMapper;
use App\Models\Business;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Persistence that businesses and contacts share: the address, the Czech IČO key and the duplicate candidates.
 */
final class PartyRecords
{
    private const DUPLICATE_KEY_ERROR = 1062;

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
            DB::transaction(function () use ($party, $attributes, $address): void {
                $party->fill($attributes)->save();
                $this->saveAddress($party, $address);
            });
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
        return $query
            ->with('address')
            ->where('user_id', $userId->toInt())
            ->where(function (Builder $match) use ($profile): void {
                $match->where('name', $profile->name);

                if ($profile->registrationNumber !== null) {
                    $match->orWhere(function (Builder $number) use ($profile): void {
                        $number
                            ->where('registration_country', $profile->registrationNumber?->country->value)
                            ->where('company_number', $profile->registrationNumber?->number);
                    });
                }

                if ($profile->email !== null) {
                    $match->orWhere('email', $profile->email);
                }
            });
    }

    /**
     * One address per owner; an empty address is not stored.
     */
    private function saveAddress(Business|Contact $party, ?Address $address): void
    {
        if ($address === null) {
            $party->address()->delete();

            return;
        }

        $party->address()->updateOrCreate([], EloquentPartyProfileMapper::toAddressAttributes($address));
    }

    private function duplicateOf(Business|Contact $party, QueryException $e): ?DuplicateCompanyNumberException
    {
        if (($e->errorInfo[1] ?? null) !== self::DUPLICATE_KEY_ERROR || ! str_contains($e->getMessage(), 'czech_company_number')) {
            return null;
        }

        $existing = $party::query()
            ->where('user_id', $party->user_id)
            ->where('czech_company_number', $party->company_number)
            ->value('id');
        if ($existing === null) {
            return null;
        }

        return new DuplicateCompanyNumberException(
            $party instanceof Contact ? ContactId::fromInt($existing) : BusinessId::fromInt($existing),
        );
    }
}
