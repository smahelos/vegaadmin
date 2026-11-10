<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Domain\User\Enums\Permission;
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
            'create' => ['create'],
            'store' => ['store'],
            'show' => ['show'],
            'edit' => ['edit'],
            'update' => ['update'],
            'destroy' => ['destroy'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function ownedActions(): array
    {
        return [
            'show' => ['show', Permission::PRODUCT_VIEW],
            'edit' => ['edit', Permission::PRODUCT_UPDATE],
            'update' => ['update', Permission::PRODUCT_UPDATE],
            'destroy' => ['destroy', Permission::PRODUCT_DELETE],
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
    #[DataProvider('ownedActions')]
    public function a_permission_does_not_reach_the_product_of_another_user(string $action, Permission $permission): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->givePermissionTo($permission);
        $product = $this->productOf($userFactory->create());
        $this->actingAs($user, 'web');

        $response = $this->callAction($action, $product);

        $response->assertNotFound();
        $this->assertModelExists($product);
    }

    #[Test]
    public function a_revoked_permission_stops_applying_at_once(): void
    {
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $owner->givePermissionTo(Permission::PRODUCT_VIEW);
        $product = $this->productOf($owner);
        $this->actingAs($owner, 'web');

        $response = $this->callAction('show', $product);
        $response->assertOk();

        $owner->revokePermissionTo(Permission::PRODUCT_VIEW);

        $response = $this->callAction('show', $product);
        $response->assertForbidden();
    }

    #[Test]
    public function the_create_link_needs_the_create_permission(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->givePermissionTo(Permission::PRODUCT_VIEW);
        $this->actingAs($user, 'web');
        $locale = ['locale' => 'cs'];
        $createUrl = route('frontend.product.create', $locale);

        $response = $this->get(
            route('frontend.products', $locale),
        );
        $response->assertDontSee($createUrl);

        $user->givePermissionTo(Permission::PRODUCT_CREATE);

        $response = $this->get(
            route('frontend.products', $locale),
        );
        $response->assertSee($createUrl);
    }

    #[Test]
    public function the_product_screens_offer_only_the_permitted_actions(): void
    {
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $owner->givePermissionTo(Permission::PRODUCT_VIEW);
        $product = $this->productOf($owner);
        $this->actingAs($owner, 'web');
        $editUrl = route('frontend.product.edit', ['locale' => 'cs', 'id' => $product->id]);
        $deleteForm = 'delete-form-' . $product->id;

        $response = $this->callAction('index', $product);
        $response->assertDontSee($editUrl);
        $response->assertDontSee($deleteForm);
        $response = $this->callAction('show', $product);
        $response->assertDontSee($editUrl);

        $owner->givePermissionTo(Permission::PRODUCT_UPDATE, Permission::PRODUCT_DELETE);

        $response = $this->callAction('index', $product);
        $response->assertSee($editUrl);
        $response->assertSee($deleteForm);
        $response = $this->callAction('show', $product);
        $response->assertSee($editUrl);
    }

    #[Test]
    public function the_invoice_form_offers_the_product_picker_only_with_the_view_permission(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $this->actingAs($user, 'web');
        $url = route('frontend.invoice.create', ['locale' => 'cs']);

        $response = $this->get($url);
        $response->assertDontSee('select-product');

        $user->givePermissionTo(Permission::PRODUCT_VIEW);

        $response = $this->get($url);
        $response->assertSee('select-product');
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
            'create' => $this->get(
                route('frontend.product.create', $locale),
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
