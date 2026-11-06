<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Persistence\Eloquent\Product\Repositories;

use App\Domain\Product\Contracts\ProductDtoWriteRepositoryInterface;
use App\Domain\Product\Exceptions\ProductInUseException;
use App\Domain\Product\ValueObjects\ProductId;
use App\Models\InvoiceProduct;
use App\Models\Product;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EloquentProductDtoRepositoryTest extends TestCase
{
    #[Test]
    public function delete_refused_by_an_invoice_line_throws_product_in_use(): void
    {
        $productFactory = Product::factory();
        $product = $productFactory->create();
        $invoiceProductFactory = InvoiceProduct::factory();
        $invoiceProductFactory->create(['product_id' => $product->id]);

        try {
            $productDtoWriteRepository = app(ProductDtoWriteRepositoryInterface::class);
            $productDtoWriteRepository->delete(
                ProductId::fromInt($product->id),
            );
            $this->fail('ProductInUseException was not thrown');
        } catch (ProductInUseException) {
            $this->assertDatabaseHas('products', ['id' => $product->id]);
        }
    }

    #[Test]
    public function delete_removes_a_product_without_invoice_lines(): void
    {
        $productFactory = Product::factory();
        $product = $productFactory->create();

        $productDtoWriteRepository = app(ProductDtoWriteRepositoryInterface::class);
        $productDtoWriteRepository->delete(
            ProductId::fromInt($product->id),
        );

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
