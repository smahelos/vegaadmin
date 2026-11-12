<?php

declare(strict_types=1);

namespace App\Application\User\Commands;

use App\Application\Party\Commands\CreateBusiness;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\ValueObjects\UserId;

/**
 * The business details are independent of the login: later changes of either do not reach the other.
 */
final class RegisterHandler
{
    private const REGISTRATION_ROLE = 'frontend_user';

    public function __construct(
        private readonly UserWriteRepositoryInterface $users,
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
        private readonly ClockInterface $clock,
    ) {}

    public function handle(Register $command): UserDTO
    {
        $created = $this->users->newAccount($command->user);
        $userId = UserId::fromInt($created->id);

        // The role carries the plan's permissions and limits, so it comes before the business that needs them
        $this->users->assignRole($userId, self::REGISTRATION_ROLE);
        $this->users->recordPlanRoleAssignment($userId, $this->clock->now());
        $this->useCases->run(
            Actor::user($userId),
            new CreateBusiness(
                $userId,
                $command->business,
                $command->firstAccount,
            ),
        );

        return $created;
    }
}
