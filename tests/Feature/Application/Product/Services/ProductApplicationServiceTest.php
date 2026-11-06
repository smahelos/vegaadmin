<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Product\Services;

use App\Application\Product\Contracts\ProductApplicationServiceInterface;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ProductApplicationServiceTest extends TestCase
{
    #[Test]
    public function find_product_returns_the_owners_product(): void
    {
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $product = $this->productOf($owner);

        $products = app(ProductApplicationServiceInterface::class);
        $found = $products->findProduct($owner->id, $product->id);

        $this->assertSame($product->id, $found->id);
    }

    #[Test]
    public function find_product_hides_the_product_of_another_user(): void
    {
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $other = $userFactory->create();
        $product = $this->productOf($owner);

        $products = app(ProductApplicationServiceInterface::class);

        $this->expectException(ModelNotFoundException::class);

        $products->findProduct($other->id, $product->id);
    }

    #[Test]
    public function find_product_returns_the_product_of_another_user_to_an_admin(): void
    {
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $admin = $userFactory->create();
        $admin->assignRole(
            Role::findByName('admin', 'backpack'),
        );
        $product = $this->productOf($owner);

        $products = app(ProductApplicationServiceInterface::class);
        $found = $products->findProduct($admin->id, $product->id);

        $this->assertSame($product->id, $found->id);
    }

    private function productOf(User $owner): Product
    {
        $productFactory = Product::factory();

        return $productFactory->create(['user_id' => $owner->id]);
    }
}
