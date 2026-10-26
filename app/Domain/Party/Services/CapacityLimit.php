<?php

declare(strict_types=1);

namespace App\Domain\Party\Services;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\User\Contracts\PermissionLimitResolverInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\Enums\EntityType;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Domain\User\ValueObjects\UserId;

/**
 * Caps the active businesses and contacts of an account. There is no counter: the active rows are the count, so
 * archiving frees a place and restoring takes one. The owner's limit applies to every path, the admin included.
 */
final class CapacityLimit
{
    private const METRIC_TYPE = 'active';

    private const PERIOD_TYPE = 'lifetime';

    public function __construct(
        private readonly UserWriteRepositoryInterface $users,
        private readonly PermissionLimitResolverInterface $limits,
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly ContactReadRepositoryInterface $contacts,
    ) {}

    /**
     * Runs inside the transaction that creates or restores the record: the owner stays locked until it commits,
     * so two requests cannot both take the last free place. No configured limit allows nothing.
     *
     * @throws EntityLimitExceededException
     */
    public function assertCanActivate(UserId $owner, EntityType $type): void
    {
        $this->users->lockForUpdate($owner);

        $active = match ($type) {
            EntityType::BUSINESS => $this->businesses->countActiveForUser($owner),
            EntityType::CONTACT => $this->contacts->countActiveForUser($owner),
        };
        $limit = $this->limits->getUserLimit(
            $owner->toInt(),
            $type->value,
            self::METRIC_TYPE,
            self::PERIOD_TYPE,
        );

        if ($active >= $limit) {
            throw new EntityLimitExceededException([
                'entity_type' => $type->value,
                'limit' => $limit,
                'current_usage' => $active,
            ]);
        }
    }
}
