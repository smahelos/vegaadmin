<?php

declare(strict_types=1);

namespace App\Application\User\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;

/**
 * An operator's role assignment from the console, which gives the first admin the admin role. The account comes
 * from the operator's argument: shell access to the server is the trust boundary here, not an owner's sign-in.
 *
 * @implements CommandInterface<null>
 */
final readonly class AssignRole implements CommandInterface
{
    public function __construct(
        public string $email,
        public string $role,
    ) {}

    public static function access(): Access
    {
        return Access::system();
    }
}
