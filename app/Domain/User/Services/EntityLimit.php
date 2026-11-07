<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\Shared\Config\Contracts\Config;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\User\Contracts\LimitedEntityCounterInterface;
use App\Domain\User\Contracts\RoleLimitReadRepositoryInterface;
use App\Domain\User\Contracts\UserReadRepositoryInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\DTO\RoleLimitDTO;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Exceptions\AmbiguousPlanRoleException;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;
use DateTimeZone;

/**
 * The one check of the plan limits (D-047): the owner's plan role sets the limit, the owner's records are the usage.
 * The owner's limit applies on every path, the admin included.
 */
final class EntityLimit
{
    public function __construct(
        private readonly UserWriteRepositoryInterface $userWrites,
        private readonly UserReadRepositoryInterface $userReads,
        private readonly RoleLimitReadRepositoryInterface $roleLimits,
        private readonly LimitedEntityCounterInterface $counter,
        private readonly ClockInterface $clock,
        private readonly Config $config,
    ) {}

    /**
     * Runs inside the transaction that creates or restores the record: the owner stays locked until it commits,
     * so two requests cannot both take the last free place.
     *
     * @throws EntityLimitExceededException
     * @throws AmbiguousPlanRoleException
     */
    public function assertCanCreate(UserId $owner, LimitedEntity $entity): void
    {
        $this->userWrites->lockForUpdate($owner);
        $limit = $this->limit($owner, $entity);

        if ($limit === null) {
            return;
        }

        $used = $this->used($owner, $entity);

        if ($used >= $limit) {
            throw new EntityLimitExceededException($entity, $limit);
        }
    }

    private function limit(UserId $owner, LimitedEntity $entity): ?int
    {
        $roleLimits = $this->roleLimits->listForUser($owner);

        // A user without a plan role row may create nothing limited
        if ($roleLimits === []) {
            return 0;
        }

        if (count($roleLimits) > 1) {
            throw new AmbiguousPlanRoleException(
                $owner,
                array_map(
                    fn (RoleLimitDTO $roleLimit): string => $roleLimit->role,
                    $roleLimits,
                ),
            );
        }

        return $roleLimits[0]->limit($entity);
    }

    private function used(UserId $owner, LimitedEntity $entity): int
    {
        if (!$entity->isMonthlyQuota()) {
            return $this->counter->countActive($owner, $entity);
        }

        return $this->counter->countCreatedSince(
            $owner,
            $entity,
            $this->quotaWindowStart($owner),
        );
    }

    /**
     * The later of the start of the calendar month in Billex's local time and the assignment of the plan role.
     */
    private function quotaWindowStart(UserId $owner): DateTimeImmutable
    {
        $zone = new DateTimeZone(
            (string) $this->config->get('app.schedule_timezone'),
        );
        $now = $this->clock->now();
        $localNow = $now->setTimezone($zone);
        $monthStart = $localNow->modify('midnight first day of this month');
        $assigned = $this->userReads->planRoleAssignedAt($owner);

        if ($assigned === null || $assigned < $monthStart) {
            return $monthStart;
        }

        return $assigned;
    }
}
