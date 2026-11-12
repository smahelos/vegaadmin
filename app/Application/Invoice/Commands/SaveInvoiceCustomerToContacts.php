<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * Creating the contact runs CreateContact as the owner, with its own permission and capacity (rules/permissions.md).
 *
 * @implements CommandInterface<null>
 */
final readonly class SaveInvoiceCustomerToContacts extends OwnedUseCase implements CommandInterface
{
    public function __construct(
        UserId $owner,
        public InvoiceId $id,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::INVOICE_UPDATE);
    }
}
