<?php

declare(strict_types=1);

namespace App\Application\User\Services;

use App\Domain\User\DTO\EntityUsageDTO;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Exceptions\AmbiguousPlanRoleException;
use App\Domain\User\Services\EntityLimit;
use App\Domain\User\ValueObjects\UserId;

final class EntityUsageApplicationService
{
    public function __construct(
        private readonly EntityLimit $limits,
    ) {}

    /**
     * @throws AmbiguousPlanRoleException
     */
    public function usage(UserId $owner, LimitedEntity $entity): EntityUsageDTO
    {
        return $this->limits->usage($owner, $entity);
    }

    /**
     * @return list<EntityUsageDTO>
     *
     * @throws AmbiguousPlanRoleException
     */
    public function usages(UserId $owner): array
    {
        $usages = [];
        foreach (LimitedEntity::cases() as $entity) {
            $usages[] = $this->limits->usage($owner, $entity);
        }

        return $usages;
    }
}
