<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * The parties stored on the invoice, which its edit form starts from.
 *
 * @implements QueryInterface<DocumentParties>
 */
final readonly class GetInvoiceParties extends OwnedUseCase implements QueryInterface
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
