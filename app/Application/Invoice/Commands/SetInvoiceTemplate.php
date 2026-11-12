<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/** @implements CommandInterface<null> */
final readonly class SetInvoiceTemplate extends OwnedUseCase implements CommandInterface
{
    public function __construct(
        UserId $owner,
        public InvoiceId $id,
        public InvoiceTemplate $template,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::INVOICE_UPDATE);
    }
}
