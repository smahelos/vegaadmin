<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CallsOutsideDatabaseInterface;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * Replaces the supplier's logo in the document's files, so the gate opens no transaction.
 *
 * @implements CommandInterface<InvoiceDTO>
 */
final readonly class UpdateInvoice extends OwnedUseCase implements CallsOutsideDatabaseInterface, CommandInterface
{
    /**
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function __construct(
        UserId $owner,
        public InvoiceId $id,
        public InvoiceWriteData $data,
        public InvoicePartySelection $parties,
        public array $products,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permissionWithOwner(Permission::INVOICE_UPDATE);
    }
}
