<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Product\Commands;

use App\Application\Product\Commands\UpdateProduct;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Models\Product;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class UpdateProductTest extends TestCase
{
    use BuildsParties;

    #[Test]
    public function a_user_changes_the_price_and_flags_of_an_own_product(): void
    {
        $owner = $this->frontendUser();
        $product = $this->productOf($owner);

        $updated = $this->update(
            $owner,
            $product,
            [
                'name' => 'Updated Product',
                'price' => '20.5',
                'currency' => 'eur',
                'is_default' => true,
                'is_active' => false,
            ],
        );

        $expectedPrice = Money::fromString('20.50', 'EUR');
        $this->assertSame('Updated Product', $updated->name);
        $this->assertTrue(
            $expectedPrice->equals($updated->price),
        );
        $this->assertTrue($updated->is_default);
        $this->assertFalse($updated->is_active);
    }

    #[Test]
    public function the_product_keeps_its_owner(): void
    {
        $owner = $this->frontendUser();
        $other = $this->frontendUser();
        $product = $this->productOf($owner);

        $updated = $this->update(
            $owner,
            $product,
            ['name' => 'Still mine', 'user_id' => $other->id],
        );

        $this->assertSame($owner->id, $updated->user_id);
    }

    #[Test]
    public function another_users_product_is_not_found_and_stays_unchanged(): void
    {
        $owner = $this->frontendUser();
        $intruder = $this->frontendUser();
        $product = $this->productOf($owner);

        try {
            $this->update(
                $intruder,
                $product,
                ['name' => 'Taken over'],
            );
            $this->fail('Another user\'s product was found');
        } catch (ProductNotFoundException) {
            $this->assertDatabaseHas(
                'products',
                ['id' => $product->id, 'name' => $product->name],
            );
        }
    }

    private function productOf(User $owner): Product
    {
        $productFactory = Product::factory();

        return $productFactory->create(['user_id' => $owner->id]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function update(
        User $actor,
        Product $product,
        array $data,
    ): ProductDTO {
        $useCases = app(AuthorizingUseCaseRunnerInterface::class);

        return $useCases->run(
            Actor::user(
                $this->idOf($actor),
            ),
            new UpdateProduct(
                $this->idOf($actor),
                ProductId::fromInt($product->id),
                ProductWriteData::fromArray($data),
            ),
        );
    }
}
