<?php

declare(strict_types=1);

namespace App\Application\User\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Domain\Party\DTO\BankAccountWriteData;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\DTO\UserWriteData;

/**
 * A new frontend account with its first business, as one public entry that cannot target an existing owner
 * (PRD_PERMISSIONS 4.4).
 *
 * @implements CommandInterface<UserDTO>
 */
final readonly class Register implements CommandInterface
{
    public function __construct(
        public UserWriteData $user,
        public BusinessWriteData $business,
        public ?BankAccountWriteData $firstAccount,
    ) {}

    public static function access(): Access
    {
        return Access::public();
    }
}
