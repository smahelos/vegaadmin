<?php

declare(strict_types=1);

namespace App\Application\Product\Commands;

use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\Services\ProductService;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Services\EntityLimit;

final class CreateProductHandler
{
    public function __construct(
        private readonly ProductService $products,
        private readonly EntityLimit $limits,
    ) {}

    public function handle(CreateProduct $command, WriteScope $scope): ProductDTO
    {
        $this->limits->assertCanCreate($command->owner, LimitedEntity::PRODUCT);

        return $this->products->createProduct($scope, $command->data);
    }
}
