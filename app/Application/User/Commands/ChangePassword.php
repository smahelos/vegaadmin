<?php

declare(strict_types=1);

namespace App\Application\User\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * The new password in plain text; the handler hashes it once the gate has let the command through.
 *
 * @implements CommandInterface<null>
 */
final readonly class ChangePassword extends OwnedUseCase implements CommandInterface
{
    public function __construct(
        UserId $owner,
        public string $password,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::PROFILE_UPDATE);
    }
}
