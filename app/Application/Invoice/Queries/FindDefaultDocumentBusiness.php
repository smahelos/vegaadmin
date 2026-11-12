<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * The owner's default business, preselected on a new document.
 *
 * @implements QueryInterface<BusinessId|null>
 */
final readonly class FindDefaultDocumentBusiness extends OwnedUseCase implements QueryInterface
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
