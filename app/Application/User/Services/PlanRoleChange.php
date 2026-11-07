<?php

declare(strict_types=1);

namespace App\Application\User\Services;

use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\User\Contracts\RoleLimitReadRepositoryInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\DTO\RoleLimitDTO;
use App\Domain\User\ValueObjects\UserId;

/**
 * A change of the plan role restarts the monthly quota window (D-047 point 2), also when an admin changes the
 * roles by hand.
 */
final class PlanRoleChange
{
    public function __construct(
        private readonly RoleLimitReadRepositoryInterface $roleLimits,
        private readonly UserWriteRepositoryInterface $users,
        private readonly ClockInterface $clock,
    ) {}

    /**
     * @return list<string>
     */
    public function planRoles(UserId $user): array
    {
        $roles = array_map(
            fn (RoleLimitDTO $roleLimit): string => $roleLimit->role,
            $this->roleLimits->listForUser($user),
        );
        sort($roles);

        return $roles;
    }

    /**
     * @param  list<string>  $before  the plan roles before the change, from planRoles()
     */
    public function recordIfChanged(UserId $user, array $before): void
    {
        if ($this->planRoles($user) === $before) {
            return;
        }

        $this->users->recordPlanRoleAssignment($user, $this->clock->now());
    }
}
