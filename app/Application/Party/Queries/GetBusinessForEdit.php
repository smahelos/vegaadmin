<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * The business for its edit form, which declares the permission of the update it leads to.
 *
 * @implements QueryInterface<BusinessDTO>
 */
final readonly class GetBusinessForEdit extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public BusinessId $id,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::BUSINESS_UPDATE);
    }
}
