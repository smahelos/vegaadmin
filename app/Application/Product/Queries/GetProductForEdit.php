<?php

declare(strict_types=1);

namespace App\Application\Product\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * The product for its edit form, which declares the permission of the update it leads to.
 *
 * @implements QueryInterface<ProductDTO>
 */
final readonly class GetProductForEdit extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public ProductId $id,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::PRODUCT_UPDATE);
    }
}
