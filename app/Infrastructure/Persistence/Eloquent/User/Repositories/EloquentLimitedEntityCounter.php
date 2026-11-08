<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\User\Repositories;

use App\Domain\User\Contracts\LimitedEntityCounterInterface;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Product;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/**
 * Counts the rows of the limited entities directly, so the User context needs no repository of the contexts that
 * already depend on it. The ForUpdate counts are locking reads: a plain read would see the transaction's earlier
 * snapshot and miss a record another request committed while this one waited for the owner's lock.
 */
final class EloquentLimitedEntityCounter implements LimitedEntityCounterInterface
{
    public function countActive(UserId $owner, LimitedEntity $entity): int
    {
        $query = $this->activeRecords($owner, $entity);

        return $query->count();
    }

    public function countActiveForUpdate(UserId $owner, LimitedEntity $entity): int
    {
        $query = $this->activeRecords($owner, $entity);
        $query->lockForUpdate();

        return $query->count();
    }

    public function countCreatedSince(
        UserId $owner,
        LimitedEntity $entity,
        DateTimeImmutable $since,
    ): int {
        $query = $this->recordsCreatedSince(
            $owner,
            $entity,
            $since,
        );

        return $query->count();
    }

    public function countCreatedSinceForUpdate(
        UserId $owner,
        LimitedEntity $entity,
        DateTimeImmutable $since,
    ): int {
        $query = $this->recordsCreatedSince(
            $owner,
            $entity,
            $since,
        );
        $query->lockForUpdate();

        return $query->count();
    }

    /**
     * @return Builder<Business>|Builder<Contact>|Builder<Product>
     */
    private function activeRecords(UserId $owner, LimitedEntity $entity): Builder
    {
        $query = match ($entity) {
            LimitedEntity::BUSINESS => Business::query(),
            LimitedEntity::CONTACT => Contact::query(),
            LimitedEntity::PRODUCT => Product::query(),
            LimitedEntity::INVOICE, LimitedEntity::EXPENSE => throw new InvalidArgumentException(
                "{$entity->value} have a monthly quota, not a capacity",
            ),
        };
        $query->where('user_id', $owner->toInt());

        // Products cannot be archived yet (TECH_DEBT step 12), so every product takes a place
        if ($entity !== LimitedEntity::PRODUCT) {
            $query->whereNull('archived_at');
        }

        return $query;
    }

    /**
     * @return Builder<Invoice>|Builder<Expense>
     */
    private function recordsCreatedSince(
        UserId $owner,
        LimitedEntity $entity,
        DateTimeImmutable $since,
    ): Builder {
        $query = match ($entity) {
            LimitedEntity::INVOICE => Invoice::query(),
            LimitedEntity::EXPENSE => Expense::query(),
            LimitedEntity::BUSINESS, LimitedEntity::CONTACT, LimitedEntity::PRODUCT => throw new InvalidArgumentException(
                "{$entity->value} have a capacity, not a monthly quota",
            ),
        };
        // Bindings are formatted without their zone, so the bound must be in the zone timestamps are stored in
        $storedZone = new DateTimeZone(
            config('app.timezone'),
        );
        $query->where('user_id', $owner->toInt());
        $query->where(
            'created_at',
            '>=',
            $since->setTimezone($storedZone),
        );

        return $query;
    }
}
