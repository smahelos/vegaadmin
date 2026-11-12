<?php

declare(strict_types=1);

namespace App\Application\User\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * The signed-in user's own name and email; the password changes only through ChangePassword.
 *
 * @implements CommandInterface<null>
 */
final readonly class UpdateProfile extends OwnedUseCase implements CommandInterface
{
    public function __construct(
        UserId $owner,
        public string $name,
        public string $email,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::PROFILE_UPDATE);
    }
}
