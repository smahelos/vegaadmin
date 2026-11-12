<?php

declare(strict_types=1);

namespace App\Application\User\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\User\DTO\EntityUsageDTO;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * The owner's usage of one plan limit, shown next to the forms of the limited entities. Whoever can fill in one of
 * those forms sees it; it shows counts only, never records.
 *
 * @implements QueryInterface<EntityUsageDTO>
 */
final readonly class GetEntityUsage extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public LimitedEntity $entity,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::anyOf(
            Permission::BUSINESS_CREATE,
            Permission::BUSINESS_UPDATE,
            Permission::CONTACT_CREATE,
            Permission::CONTACT_UPDATE,
            Permission::EXPENSE_CREATE,
            Permission::EXPENSE_UPDATE,
            Permission::INVOICE_CREATE,
            Permission::INVOICE_UPDATE,
            Permission::PRODUCT_CREATE,
            Permission::PRODUCT_UPDATE,
        );
    }
}
