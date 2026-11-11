<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Product\Commands;

use App\Application\Product\Commands\DeleteProduct;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Product\Exceptions\ProductInUseException;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\ValueObjects\ProductId;
use App\Models\InvoiceProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class DeleteProductTest extends TestCase
{
    use BuildsParties;

    #[Test]
    public function a_user_deletes_an_own_product_with_its_image(): void
    {
        $disk = Storage::fake('public');
        $disk->put('products/photo.jpg', 'image');
        $owner = $this->frontendUser();
        $product = $this->productOf($owner);
        $product->forceFill(['image' => 'products/photo.jpg']);
        $product->saveQuietly();

        $this->deleteAs($owner, $product);

        $this->assertModelMissing($product);
        $disk->assertMissing('products/photo.jpg');
    }

    #[Test]
    public function a_product_on_an_invoice_line_stays_with_its_image(): void
    {
        $disk = Storage::fake('public');
        $disk->put('products/photo.jpg', 'image');
        $owner = $this->frontendUser();
        $product = $this->productOf($owner);
        $product->forceFill(['image' => 'products/photo.jpg']);
        $product->saveQuietly();
        $invoiceProductFactory = InvoiceProduct::factory();
        $invoiceProductFactory->create(['product_id' => $product->id]);

        try {
            $this->deleteAs($owner, $product);
            $this->fail('A product on an invoice line was deleted');
        } catch (ProductInUseException) {
            $this->assertModelExists($product);
            $disk->assertExists('products/photo.jpg');
        }
    }

    #[Test]
    public function another_users_product_is_not_found_and_stays(): void
    {
        $owner = $this->frontendUser();
        $intruder = $this->frontendUser();
        $product = $this->productOf($owner);

        try {
            $this->deleteAs($intruder, $product);
            $this->fail('Another user\'s product was found');
        } catch (ProductNotFoundException) {
            $this->assertModelExists($product);
        }
    }

    private function productOf(User $owner): Product
    {
        $productFactory = Product::factory();

        return $productFactory->create(['user_id' => $owner->id]);
    }

    private function deleteAs(User $actor, Product $product): void
    {
        $useCases = app(AuthorizingUseCaseRunnerInterface::class);
        $useCases->run(
            Actor::user(
                $this->idOf($actor),
            ),
            new DeleteProduct(
                $this->idOf($actor),
                ProductId::fromInt($product->id),
            ),
        );
    }
}
