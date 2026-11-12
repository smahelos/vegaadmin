<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * The business a document refers to, archived included: a stored document keeps an archived business.
 *
 * @implements QueryInterface<BusinessDTO>
 */
final readonly class GetDocumentBusiness extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public BusinessId $id,
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
