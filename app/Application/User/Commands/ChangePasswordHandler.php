<?php

declare(strict_types=1);

namespace App\Application\User\Commands;

use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;

final class ChangePasswordHandler
{
    public function __construct(
        private readonly UserWriteRepositoryInterface $users,
    ) {}

    public function handle(ChangePassword $command, WriteScope $scope): void
    {
        $this->users->changePassword($scope, $command->password);
    }
}
