<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CallsOutsideDatabaseInterface;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * Only a draft can be deleted (D-050, point 2). Its copy of the supplier logo is removed after the commit, so
 * the gate opens no transaction.
 *
 * @implements CommandInterface<null>
 */
final readonly class DeleteInvoice extends OwnedUseCase implements CallsOutsideDatabaseInterface, CommandInterface
{
    public function __construct(
        UserId $owner,
        public InvoiceId $id,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::INVOICE_DELETE);
    }
}
