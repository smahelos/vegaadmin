<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Models\Product;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The owner of a product still needs the permission of each action, and a plan role holds all of them.
 */
final class ProductPermissionsTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function actions(): array
    {
        return [
            'index' => ['index'],
            'store' => ['store'],
            'show' => ['show'],
            'edit' => ['edit'],
            'update' => ['update'],
            'destroy' => ['destroy'],
        ];
    }

    #[Test]
    #[DataProvider('actions')]
    public function an_owner_without_the_permission_is_refused(string $action): void
    {
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $product = $this->productOf($owner);
        $this->actingAs($owner, 'web');

        $response = $this->callAction($action, $product);

        $response->assertForbidden();
        $this->assertModelExists($product);
    }

    #[Test]
    public function a_frontend_user_deletes_an_own_product(): void
    {
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $owner->assignRole(
            Role::findByName('frontend_user', 'web'),
        );
        $product = $this->productOf($owner);
        $this->actingAs($owner, 'web');

        $response = $this->callAction('destroy', $product);

        $response->assertRedirect();
        $this->assertModelMissing($product);
    }

    #[Test]
    public function a_permission_does_not_reach_the_product_of_another_user(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->givePermissionTo('frontend.can_delete_product');
        $product = $this->productOf($userFactory->create());
        $this->actingAs($user, 'web');

        $response = $this->callAction('destroy', $product);

        $response->assertNotFound();
        $this->assertModelExists($product);
    }

    private function callAction(string $action, Product $product): TestResponse
    {
        $input = [
            'name' => 'Refused',
            'price' => 10,
            'currency' => 'CZK',
        ];
        $locale = ['locale' => 'cs'];
        $owned = ['locale' => 'cs', 'id' => $product->id];

        return match ($action) {
            'index' => $this->get(
                route('frontend.products', $locale),
            ),
            'store' => $this->post(
                route('frontend.product.store', $locale),
                $input,
            ),
            'show' => $this->get(
                route('frontend.product.show', $owned),
            ),
            'edit' => $this->get(
                route('frontend.product.edit', $owned),
            ),
            'update' => $this->put(
                route('frontend.product.update', $owned),
                $input,
            ),
            'destroy' => $this->delete(
                route('frontend.product.destroy', $owned),
            ),
        };
    }

    private function productOf(User $owner): Product
    {
        $productFactory = Product::factory();

        return $productFactory->create(['user_id' => $owner->id]);
    }
}
