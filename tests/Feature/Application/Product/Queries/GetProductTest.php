<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Product\Queries;

use App\Application\Product\Queries\GetProduct;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\User\Exceptions\PermissionDeniedException;
use App\Models\Product;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class GetProductTest extends TestCase
{
    use BuildsParties;

    #[Test]
    public function a_user_reads_an_own_product(): void
    {
        $owner = $this->frontendUser();
        $product = $this->productOf($owner);

        $found = $this->read($owner, $product);

        $this->assertSame($product->id, $found->id);
    }

    #[Test]
    public function another_users_product_is_not_found(): void
    {
        $owner = $this->frontendUser();
        $intruder = $this->frontendUser();
        $product = $this->productOf($owner);

        $this->expectException(ProductNotFoundException::class);

        $this->read($intruder, $product);
    }

    #[Test]
    public function a_user_without_the_view_permission_is_refused(): void
    {
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $product = $this->productOf($owner);

        $this->expectException(PermissionDeniedException::class);

        $this->read($owner, $product);
    }

    private function productOf(User $owner): Product
    {
        $productFactory = Product::factory();

        return $productFactory->create(['user_id' => $owner->id]);
    }

    private function read(User $actor, Product $product): ProductDTO
    {
        $useCases = app(AuthorizingUseCaseRunnerInterface::class);

        return $useCases->run(
            Actor::user(
                $this->idOf($actor),
            ),
            new GetProduct(
                $this->idOf($actor),
                ProductId::fromInt($product->id),
            ),
        );
    }
}
