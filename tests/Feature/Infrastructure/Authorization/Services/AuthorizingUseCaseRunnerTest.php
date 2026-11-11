<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Authorization\Services;

use App\Application\Product\Commands\CreateProduct;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Product\Contracts\ProductDtoWriteRepositoryInterface;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\User\Enums\Permission;
use App\Domain\User\Exceptions\PermissionDeniedException;
use App\Infrastructure\Persistence\Eloquent\Product\Repositories\EloquentProductDtoRepository;
use App\Models\Product;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\BuildsBackendUsers;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class AuthorizingUseCaseRunnerTest extends TestCase
{
    use BuildsBackendUsers;
    use BuildsParties;

    #[Test]
    public function a_user_cannot_act_for_another_owner(): void
    {
        $user = $this->frontendUser();
        $owner = $this->frontendUser();
        $useCases = app(AuthorizingUseCaseRunnerInterface::class);

        $this->expectException(PermissionDeniedException::class);

        $useCases->run(
            Actor::user(
                $this->idOf($user),
            ),
            new CreateProduct(
                $this->idOf($owner),
                $this->data(),
            ),
        );
    }

    #[Test]
    public function an_admin_needs_the_permission_in_the_admin_guard(): void
    {
        // frontend_user holds product.create in web, not in backpack
        $user = $this->frontendUser();
        $useCases = app(AuthorizingUseCaseRunnerInterface::class);

        $this->expectException(PermissionDeniedException::class);

        $useCases->run(
            Actor::admin(
                $this->idOf($user),
            ),
            new CreateProduct(
                $this->idOf($user),
                $this->data(),
            ),
        );
    }

    #[Test]
    public function a_failing_command_leaves_nothing_behind(): void
    {
        $owner = $this->frontendUser();
        $stored = app(EloquentProductDtoRepository::class);
        $this->app->instance(
            ProductDtoWriteRepositoryInterface::class,
            new class($stored) implements ProductDtoWriteRepositoryInterface
            {
                public function __construct(private readonly EloquentProductDtoRepository $stored) {}

                public function create(WriteScope $scope, ProductWriteData $writeData): ProductDTO
                {
                    $this->stored->create($scope, $writeData);

                    throw new RuntimeException('Failed after the insert');
                }

                public function update(
                    ProductId $id,
                    WriteScope $scope,
                    ProductWriteData $writeData,
                ): ProductDTO {
                    return $this->stored->update(
                        $id,
                        $scope,
                        $writeData,
                    );
                }

                public function isUsedOnInvoices(ProductId $id): bool
                {
                    return $this->stored->isUsedOnInvoices($id);
                }

                public function delete(ProductId $id, WriteScope $scope): void
                {
                    $this->stored->delete($id, $scope);
                }
            },
        );
        $useCases = app(AuthorizingUseCaseRunnerInterface::class);

        try {
            $useCases->run(
                Actor::user(
                    $this->idOf($owner),
                ),
                new CreateProduct(
                    $this->idOf($owner),
                    $this->data(),
                ),
            );
            $this->fail('The failing repository was not called');
        } catch (RuntimeException) {
            $productQuery = Product::where('user_id', $owner->id);
            $this->assertFalse($productQuery->exists());
        }
    }

    #[Test]
    public function allowed_answers_for_the_signed_in_user_of_the_actors_guard_only(): void
    {
        $user = $this->frontendUser();
        $other = $this->frontendUser();
        $admin = $this->backendUser(Permission::PRODUCT_VIEW);
        $this->actingAs($user, 'web');
        $useCases = app(AuthorizingUseCaseRunnerInterface::class);

        $this->assertTrue(
            $useCases->allowed(
                Actor::user(
                    $this->idOf($user),
                ),
                CreateProduct::class,
            ),
        );
        $this->assertFalse(
            $useCases->allowed(
                Actor::user(
                    $this->idOf($other),
                ),
                CreateProduct::class,
            ),
        );
        $this->actingAs($admin, 'backpack');
        $this->assertFalse(
            $useCases->allowed(
                Actor::admin(
                    $this->idOf($admin),
                ),
                CreateProduct::class,
            ),
        );
    }

    private function data(): ProductWriteData
    {
        return ProductWriteData::fromArray(['name' => 'Gate product', 'price' => 10]);
    }
}
