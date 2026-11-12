<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * The owner's active businesses, all of them, offered as the document's supplier.
 *
 * @implements QueryInterface<list<BusinessDTO>>
 */
final readonly class ListActiveDocumentBusinesses extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::anyOf(
            Permission::INVOICE_CREATE,
            Permission::INVOICE_UPDATE,
            Permission::EXPENSE_CREATE,
            Permission::EXPENSE_UPDATE,
        );
    }
}
