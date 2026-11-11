<?php

declare(strict_types=1);

namespace App\Application\Product\Commands;

use App\Domain\Product\Contracts\ProductDtoReadRepositoryInterface;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\Services\ProductService;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;

final class DeleteProductHandler
{
    public function __construct(
        private readonly ProductDtoReadRepositoryInterface $productReads,
        private readonly ProductService $products,
        private readonly TransactionBoundaryInterface $transactions,
        private readonly FileUploadServiceInterface $files,
    ) {}

    public function handle(DeleteProduct $command, WriteScope $scope): void
    {
        $product = $this->productReads->findById($command->id, $scope->read);
        if ($product === null) {
            throw new ProductNotFoundException($command->id);
        }

        $this->transactions->transaction(
            fn () => $this->products->deleteProduct($command->id, $scope),
        );

        // The file goes only once the product is gone, so a refused delete keeps the image
        if ($product->image !== null) {
            $this->files->deleteFile($product->image, 'public');
        }
    }
}
