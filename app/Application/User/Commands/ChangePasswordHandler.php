<?php

declare(strict_types=1);

namespace App\Application\User\Commands;

use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Hash\Contracts\HashInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\ValueObjects\UserPassword;

final class ChangePasswordHandler
{
    public function __construct(
        private readonly UserWriteRepositoryInterface $users,
        private readonly HashInterface $hasher,
    ) {}

    public function handle(ChangePassword $command, WriteScope $scope): void
    {
        $password = UserPassword::fromPlainText(
            $command->password,
            $this->hasher,
        );
        $this->users->changePassword($scope, $password);
    }
}
