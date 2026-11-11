<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Product\Queries;

use App\Application\Product\Queries\GetProductForEdit;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\Exceptions\PermissionDeniedException;
use App\Models\Permission as PermissionModel;
use App\Models\Product;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class GetProductForEditTest extends TestCase
{
    use BuildsParties;

    #[Test]
    public function the_edit_form_needs_the_update_permission_not_only_the_view_one(): void
    {
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $owner->givePermissionTo(
            PermissionModel::findByName(Permission::PRODUCT_VIEW->value, 'web'),
        );
        $productFactory = Product::factory();
        $product = $productFactory->create(['user_id' => $owner->id]);
        $useCases = app(AuthorizingUseCaseRunnerInterface::class);

        $this->expectException(PermissionDeniedException::class);

        $useCases->run(
            Actor::user(
                $this->idOf($owner),
            ),
            new GetProductForEdit(
                $this->idOf($owner),
                ProductId::fromInt($product->id),
            ),
        );
    }
}
