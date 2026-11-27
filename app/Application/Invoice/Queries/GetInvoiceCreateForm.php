<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Invoice\DTO\InvoiceFormDTO;
use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/** @implements QueryInterface<InvoiceFormDTO> */
final readonly class GetInvoiceCreateForm extends OwnedUseCase implements QueryInterface
{
    /**
     * @param  ?BusinessId  $businessId  The supplier the form opens with; the default business without it
     */
    public function __construct(
        UserId $owner,
        public ?BusinessId $businessId = null,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::INVOICE_CREATE);
    }
}
