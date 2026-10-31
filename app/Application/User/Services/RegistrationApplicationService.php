<?php

declare(strict_types=1);

namespace App\Application\User\Services;

use App\Domain\Party\DTO\BankAccountWriteData;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\Party\Services\BusinessCreator;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\DTO\UserWriteData;
use App\Domain\User\ValueObjects\UserId;

/**
 * Creates a frontend account together with its first business, which counts against the business limit of the
 * registration plan and becomes the default. The business details are independent of the login: later changes
 * of either do not reach the other.
 */
final class RegistrationApplicationService
{
    private const REGISTRATION_ROLE = 'frontend_user';

    public function __construct(
        private readonly UserWriteRepositoryInterface $users,
        private readonly BusinessCreator $businesses,
        private readonly TransactionBoundaryInterface $transactions,
    ) {}

    public function register(
        UserWriteData $user,
        BusinessWriteData $business,
        ?BankAccountWriteData $bankAccount,
    ): UserDTO {
        return $this->transactions->transaction(function () use ($user, $business, $bankAccount): UserDTO {
            $created = $this->users->create($user);
            $userId = UserId::fromInt($created->id);

            // The role carries the plan's limits, so it comes before the business that counts against them
            $this->users->assignRole($userId, self::REGISTRATION_ROLE);
            $this->businesses->create(
                $userId,
                $business,
                $bankAccount,
            );

            return $created;
        });
    }
}
