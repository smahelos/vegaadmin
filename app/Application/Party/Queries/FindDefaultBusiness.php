<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * Null while the owner has no active business.
 *
 * @implements QueryInterface<BusinessId|null>
 */
final readonly class FindDefaultBusiness extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::BUSINESS_VIEW);
    }
}
