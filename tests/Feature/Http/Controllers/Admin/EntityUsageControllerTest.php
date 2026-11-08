<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

final class EntityUsageControllerTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAdminTestEnvironment();
    }

    #[Test]
    public function a_guest_gets_no_usage(): void
    {
        $user = $this->userWithPlanRole();

        $httpClient = $this->withHeader('X-Requested-With', 'XMLHttpRequest');
        $response = $httpClient->get(
            $this->usageUrl('products', $user->id),
        );

        $response->assertUnauthorized();
    }

    #[Test]
    public function only_a_permission_of_the_entity_shows_its_usage(): void
    {
        $user = $this->userWithPlanRole();
        $this->regularUser->givePermissionTo(
            Permission::findByName('can_update_product', 'backpack'),
        );
        $this->actingAs($this->regularUser, 'backpack');

        $allowed = $this->get(
            $this->usageUrl('products', $user->id),
        );
        $denied = $this->get(
            $this->usageUrl('invoices', $user->id),
        );

        $allowed->assertOk();
        $denied->assertForbidden();
    }

    #[Test]
    public function an_unknown_entity_is_refused_and_an_unknown_user_is_invalid(): void
    {
        $user = $this->userWithPlanRole();
        $this->actingAs($this->adminUser, 'backpack');

        $unknownEntity = $this->getJson(
            $this->usageUrl('unknown', $user->id),
        );
        $unknownUser = $this->getJson(
            $this->usageUrl('products', 0),
        );

        $unknownEntity->assertForbidden();
        $unknownUser->assertUnprocessable();
    }

    #[Test]
    public function it_shows_the_usage_of_the_selected_user(): void
    {
        $user = $this->userWithPlanRole();
        $productFactory = Product::factory(2);
        $productFactory->create(['user_id' => $user->id]);
        $this->actingAs($this->adminUser, 'backpack');

        $response = $this->get(
            $this->usageUrl('products', $user->id),
        );

        $response->assertOk();
        $response->assertSeeTextInOrder(
            [
                __('role_limits.usage.products'),
                '2 /',
                '5',
            ],
        );
    }

    #[Test]
    public function it_warns_about_several_plan_roles(): void
    {
        $user = $this->userWithPlanRole();
        $user->assignRole(
            Role::findByName('frontend_user_plus', 'web'),
        );
        $this->actingAs($this->adminUser, 'backpack');

        $response = $this->get(
            $this->usageUrl('products', $user->id),
        );

        $response->assertOk();
        $response->assertSeeText(
            __('role_limits.usage.ambiguous'),
        );
    }

    #[Test]
    public function every_limited_entity_form_offers_the_usage(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        foreach (
            [
                'invoice' => 'invoices',
                'expense' => 'expenses',
                'business' => 'businesses',
                'contact' => 'contacts',
                'product' => 'products',
            ] as $crud => $entity
        ) {
            $response = $this->get('/admin/' . $crud . '/create');

            $response->assertOk();
            $response->assertSee('entity-usage?entity=' . $entity);
        }
    }

    #[Test]
    public function the_edit_form_without_an_owner_input_uses_the_stored_owner(): void
    {
        $user = $this->userWithPlanRole();
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $user->id]);
        $this->actingAs($this->adminUser, 'backpack');

        $response = $this->get('/admin/business/' . $business->id . '/edit');

        $response->assertOk();
        $response->assertSee('data-owner="' . $user->id . '"', false);
    }

    private function usageUrl(string $entity, int $userId): string
    {
        return '/admin/entity-usage?entity=' . $entity . '&user_id=' . $userId;
    }

    private function userWithPlanRole(): User
    {
        $userFactory = User::factory();
        $user = $userFactory->create(['email' => 'usage-' . uniqid() . '@billex.test']);
        $user->assignRole(
            Role::findByName('frontend_user', 'web'),
        );

        return $user;
    }
}
