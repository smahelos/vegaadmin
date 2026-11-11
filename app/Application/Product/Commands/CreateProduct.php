<?php

declare(strict_types=1);

namespace App\Application\Product\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/** @implements CommandInterface<ProductDTO> */
final readonly class CreateProduct extends OwnedUseCase implements CommandInterface
{
    public function __construct(
        UserId $owner,
        public ProductWriteData $data,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permissionWithOwner(Permission::PRODUCT_CREATE);
    }
}
