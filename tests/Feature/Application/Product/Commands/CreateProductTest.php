<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Product\Commands;

use App\Application\Product\Commands\CreateProduct;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Enums\Permission;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Domain\User\Exceptions\PermissionDeniedException;
use App\Models\Product;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsBackendUsers;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class CreateProductTest extends TestCase
{
    use BuildsBackendUsers;
    use BuildsParties;

    #[Test]
    public function a_user_creates_a_product_with_a_money_price(): void
    {
        $owner = $this->frontendUser();

        $product = $this->create(
            $owner,
            [
                'name' => 'Money DTO Product',
                'price_money' => ['amount' => '123.45', 'currency' => 'czk'],
                'unit' => 'pieces',
            ],
        );

        $this->assertSame('123.45', $product->price?->getAmount());
        $this->assertSame('CZK', $product->price?->getCurrency());
        $this->assertSame($owner->id, $product->user_id);
    }

    #[Test]
    public function the_first_product_is_the_default_one_and_the_second_is_not(): void
    {
        $owner = $this->frontendUser();

        $first = $this->create($owner, ['name' => 'First Product', 'price' => 50]);
        $second = $this->create($owner, ['name' => 'Second Product', 'price' => 75]);

        $this->assertTrue($first->is_default);
        $this->assertFalse($second->is_default);
    }

    #[Test]
    public function the_slug_comes_from_the_name_unless_one_is_given(): void
    {
        $owner = $this->frontendUser();

        $generated = $this->create($owner, ['name' => 'Test Product Name', 'price' => 25]);
        $given = $this->create(
            $owner,
            [
                'name' => 'Test Product',
                'slug' => 'custom-slug',
                'price' => 25,
            ],
        );

        $this->assertSame('test-product-name', $generated->slug);
        $this->assertSame('custom-slug', $given->slug);
    }

    #[Test]
    public function a_reached_product_limit_refuses_the_product(): void
    {
        $owner = $this->frontendUser();
        $this->limitFrontendUser(LimitedEntity::PRODUCT, 0);

        $this->expectException(EntityLimitExceededException::class);

        $this->create($owner, ['name' => 'Over the limit', 'price' => 10]);
    }

    #[Test]
    public function a_user_without_the_permission_is_refused(): void
    {
        $userFactory = User::factory();
        $owner = $userFactory->create();

        $this->expectException(PermissionDeniedException::class);

        $this->create($owner, ['name' => 'Refused', 'price' => 10]);
    }

    #[Test]
    public function an_admin_creates_a_product_for_an_owner_who_may_create_products(): void
    {
        $admin = $this->backendUser(Permission::PRODUCT_CREATE);
        $owner = $this->frontendUser();
        $useCases = app(AuthorizingUseCaseRunnerInterface::class);

        $product = $useCases->run(
            Actor::admin(
                $this->idOf($admin),
            ),
            new CreateProduct(
                $this->idOf($owner),
                ProductWriteData::fromArray(['name' => 'For the owner', 'price' => 10]),
            ),
        );

        $this->assertSame($owner->id, $product->user_id);
    }

    #[Test]
    public function an_admin_is_refused_for_an_owner_who_may_not_create_products(): void
    {
        $admin = $this->backendUser(Permission::PRODUCT_CREATE);
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $useCases = app(AuthorizingUseCaseRunnerInterface::class);

        try {
            $useCases->run(
                Actor::admin(
                    $this->idOf($admin),
                ),
                new CreateProduct(
                    $this->idOf($owner),
                    ProductWriteData::fromArray(['name' => 'Refused', 'price' => 10]),
                ),
            );
            $this->fail('The owner\'s missing permission was not checked');
        } catch (PermissionDeniedException) {
            $productQuery = Product::where('user_id', $owner->id);
            $this->assertFalse($productQuery->exists());
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function create(User $owner, array $data): ProductDTO
    {
        $useCases = app(AuthorizingUseCaseRunnerInterface::class);

        return $useCases->run(
            Actor::user(
                $this->idOf($owner),
            ),
            new CreateProduct(
                $this->idOf($owner),
                ProductWriteData::fromArray($data),
            ),
        );
    }
}
