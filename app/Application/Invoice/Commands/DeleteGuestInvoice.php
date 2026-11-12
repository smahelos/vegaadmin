<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CallsOutsideDatabaseInterface;
use App\Application\Shared\UseCase\Contracts\CommandInterface;

/**
 * Only the session that created the guest invoice may delete it: its token must match the session's.
 *
 * @implements CommandInterface<null>
 */
final readonly class DeleteGuestInvoice implements CallsOutsideDatabaseInterface, CommandInterface
{
    public function __construct(
        public string $token,
        public ?string $sessionToken,
    ) {}

    public static function access(): Access
    {
        return Access::public();
    }
}
