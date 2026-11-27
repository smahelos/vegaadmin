<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CallsOutsideDatabaseInterface;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * A new draft with the parties, lines and settings of an invoice and no number (D-050, point 4). Copies the
 * supplier logo into the draft's files, so the gate opens no transaction.
 *
 * @implements CommandInterface<InvoiceDTO>
 */
final readonly class CopyInvoice extends OwnedUseCase implements CallsOutsideDatabaseInterface, CommandInterface
{
    public function __construct(
        UserId $owner,
        public InvoiceId $id,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::INVOICE_CREATE);
    }
}
