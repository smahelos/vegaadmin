<?php

declare(strict_types=1);

namespace App\Application\User\Commands;

use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\DTO\UserWriteData;

final class UpdateProfileHandler
{
    public function __construct(
        private readonly UserWriteRepositoryInterface $users,
    ) {}

    public function handle(UpdateProfile $command, WriteScope $scope): void
    {
        $this->users->updateAccount(
            $scope,
            new UserWriteData($command->name, $command->email),
        );
    }
}
