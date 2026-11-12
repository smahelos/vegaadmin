<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Enums\Permission;
use App\Models\Address;
use App\Models\Business;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Support\BuildsBackendUsers;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class BusinessCrudControllerTest extends TestCase
{
    use BuildsBackendUsers;
    use BuildsParties;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $userFactory = User::factory();
        $this->admin = $userFactory->create();
        $this->admin->assignRole(
            Role::findByName('admin', 'backpack'),
        );
    }

    #[Test]
    public function a_guest_is_sent_to_the_admin_login(): void
    {
        $httpResponse = $this->get('/admin/business');
        $httpResponse->assertRedirect(
            route('backpack.auth.login'),
        );
    }

    #[Test]
    public function an_admin_user_without_the_permission_cannot_list_or_create(): void
    {
        $backendUser = $this->backendUser();
        $this->actingAs($backendUser, 'backpack');

        $httpResponse = $this->get('/admin/business');
        $httpResponse->assertForbidden();
        $httpResponse = $this->get('/admin/business/create');
        $httpResponse->assertForbidden();
    }

    #[Test]
    public function creates_a_business_for_the_chosen_owner_within_the_owners_limit(): void
    {
        $owner = $this->frontendUser();

        $httpClient = $this->actingAs($this->admin, 'backpack');
        $httpResponse = $httpClient->post(
            '/admin/business',
            [
                'user_id' => $owner->id,
                'name' => 'Admin Firma',
                'registration_country' => 'CZ',
                'company_number' => '27082440',
                'street' => 'Hlavní 1',
                'city' => 'Brno',
                'postal_code' => '602 00',
                'country' => 'CZ',
                '_save_action' => 'save_and_back',
            ],
        );
        $httpResponse->assertRedirect();

        $businessQuery = Business::query();
        $businessQuery->where('user_id', $owner->id);
        $business = $businessQuery->sole();
        $this->assertSame('27082440', $business->company_number);
        $addressQuery = Address::query();
        $addressQuery->where('business_id', $business->id);
        $this->assertSame(
            'Brno',
            $addressQuery->value('city'),
        );
        $this->assertSame($business->id, $owner->fresh()?->default_business_id);
    }

    #[Test]
    public function the_owners_capacity_limit_applies_in_the_admin(): void
    {
        $owner = $this->frontendUser();
        $this->limitFrontendUser(LimitedEntity::BUSINESS, 1);
        $businessFactory = Business::factory();
        $businessFactory->create(['user_id' => $owner->id]);

        $httpClient = $this->actingAs($this->admin, 'backpack');
        $httpClient = $httpClient->from('/admin/business/create');
        $httpResponse = $httpClient->post(
            '/admin/business',
            [
                'user_id' => $owner->id,
                'name' => 'Druhá',
                'country' => 'CZ',
            ],
        );
        $httpResponse->assertRedirect('/admin/business/create');

        $businessQuery = Business::query();
        $businessQuery->where('user_id', $owner->id);
        $this->assertSame(
            1,
            $businessQuery->count(),
        );
    }

    #[Test]
    public function a_czech_company_number_of_another_business_of_the_owner_is_refused(): void
    {
        $owner = $this->frontendUser();
        $businessFactory = Business::factory();
        $archivedFactory = $businessFactory->archived();
        $archivedFactory->create(
            [
                'user_id' => $owner->id,
                'company_number' => '27082440',
            ],
        );

        $httpClient = $this->actingAs($this->admin, 'backpack');
        $httpClient = $httpClient->from('/admin/business/create');
        $httpResponse = $httpClient->post(
            '/admin/business',
            [
                'user_id' => $owner->id,
                'name' => 'Kopie',
                'registration_country' => 'CZ',
                'company_number' => '27082440',
                'country' => 'CZ',
            ],
        );
        $httpResponse->assertSessionHasErrors('company_number');
    }

    #[Test]
    public function updates_a_business_and_clears_emptied_fields(): void
    {
        $owner = $this->frontendUser();
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $owner->id, 'email' => 'old@example.test']);

        $httpClient = $this->actingAs($this->admin, 'backpack');
        $httpResponse = $httpClient->put(
            '/admin/business/' . $business->id,
            [
                'id' => $business->id,
                'name' => 'Nový název',
                'email' => '',
                'country' => 'CZ',
                '_save_action' => 'save_and_back',
            ],
        );
        $httpResponse->assertRedirect();

        $business->refresh();
        $this->assertSame('Nový název', $business->name);
        $this->assertNull($business->email);
    }

    #[Test]
    public function archives_and_restores_a_business_of_its_owner(): void
    {
        $owner = $this->frontendUser();
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $owner->id]);
        $owner->forceFill(['default_business_id' => $business->id]);
        $owner->save();

        $httpClient = $this->actingAs($this->admin, 'backpack');
        $httpResponse = $httpClient->post(
            route('admin.businesses.archive', ['id' => $business->id]),
        );
        $httpResponse->assertRedirect();
        $this->assertNotNull($business->fresh()?->archived_at);
        $this->assertNull($owner->fresh()?->default_business_id);

        $httpResponse = $this->post(
            route('admin.businesses.restore', ['id' => $business->id]),
        );
        $httpResponse->assertRedirect();
        $this->assertNull($business->fresh()?->archived_at);
        $this->assertSame($business->id, $owner->fresh()?->default_business_id);
    }

    #[Test]
    public function archiving_a_default_accepts_an_explicit_replacement_without_set_default_permission(): void
    {
        $owner = $this->frontendUser();
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $owner->id]);
        $replacement = $businessFactory->create(['user_id' => $owner->id]);
        $owner->forceFill(['default_business_id' => $business->id]);
        $owner->save();
        $this->actingAs(
            $this->backendUser(Permission::BUSINESS_ARCHIVE, Permission::BUSINESS_VIEW),
            'backpack',
        );
        $httpResponse = $this->postJson('/admin/business/search', ['start' => 0, 'length' => 100]);
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSee('replacement_business_id');
        $httpResponse->assertSee($replacement->name);
        $httpResponse = $this->post(
            route('admin.businesses.archive', ['id' => $business->id]),
            [
                'replacement_business_id' => $replacement->id,
            ],
        );
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $this->assertNotNull($business->fresh()->archived_at);
        $this->assertSame($replacement->id, $owner->fresh()->default_business_id);
    }

    #[Test]
    public function archiving_rejects_a_foreign_archived_or_unchanged_replacement(): void
    {
        $owner = $this->frontendUser();
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $owner->id]);
        $owner->forceFill(['default_business_id' => $business->id]);
        $owner->save();
        $archivedFactory = $businessFactory->archived();
        $archived = $archivedFactory->create(['user_id' => $owner->id]);
        $foreign = $businessFactory->create();
        $this->actingAs($this->admin, 'backpack');
        foreach (
            [
                $business->id,
                $archived->id,
                $foreign->id,
                'invalid',
            ] as $replacement
        ) {
            $response = $this->post(
                route('admin.businesses.archive', ['id' => $business->id]),
                [
                    'replacement_business_id' => $replacement,
                ],
            );
            $response->assertSessionHasErrors('replacement_business_id');
            $this->assertNull($business->fresh()->archived_at);
            $this->assertSame($business->id, $owner->fresh()->default_business_id);
        }
    }

    #[Test]
    public function archiving_needs_the_permission(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create();
        $backendUser = $this->backendUser(Permission::BUSINESS_VIEW);

        $httpClient = $this->actingAs($backendUser, 'backpack');
        $httpResponse = $httpClient->post(
            route('admin.businesses.archive', ['id' => $business->id]),
        );
        $httpResponse->assertForbidden();
    }

    #[Test]
    public function the_admin_has_no_delete_operation(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create();

        $httpClient = $this->actingAs($this->admin, 'backpack');
        $httpResponse = $httpClient->delete('/admin/business/' . $business->id);
        $httpResponse->assertStatus(405);
    }

    #[Test]
    public function archiving_without_the_permission_is_refused_before_validation(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create();

        $httpClient = $this->actingAs(
            $this->backendUser(Permission::BUSINESS_VIEW),
            'backpack',
        );
        $httpResponse = $httpClient->post(
            route('admin.businesses.archive', ['id' => $business->id]),
            ['replacement_business_id' => 'not a number'],
        );
        $httpResponse->assertForbidden();
        $httpResponse->assertSessionHasNoErrors();

        $this->assertNull($business->fresh()?->archived_at);
    }

    #[Test]
    public function an_update_for_an_owner_without_the_permission_is_refused(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['name' => 'Kept']);

        $httpClient = $this->actingAs($this->admin, 'backpack');
        $httpResponse = $httpClient->put(
            '/admin/business/' . $business->id,
            [
                'id' => $business->id,
                'name' => 'Changed',
                'country' => 'CZ',
                '_save_action' => 'save_and_back',
            ],
        );
        $httpResponse->assertForbidden();

        $this->assertSame('Kept', $business->fresh()?->name);
    }
}
