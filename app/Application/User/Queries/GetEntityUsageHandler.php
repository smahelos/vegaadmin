<?php

declare(strict_types=1);

namespace App\Application\User\Queries;

use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\User\DTO\EntityUsageDTO;
use App\Domain\User\Exceptions\AmbiguousPlanRoleException;
use App\Domain\User\Services\EntityLimit;

final class GetEntityUsageHandler
{
    public function __construct(
        private readonly EntityLimit $limits,
    ) {}

    /**
     * @throws AmbiguousPlanRoleException
     */
    public function handle(GetEntityUsage $query, ReadScope $scope): EntityUsageDTO
    {
        return $this->limits->usage($scope->owner, $query->entity);
    }
}
