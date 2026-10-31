<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

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
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::findByName('admin', 'backpack'));
    }

    #[Test]
    public function a_guest_is_sent_to_the_admin_login(): void
    {
        $this->get('/admin/business')->assertRedirect(route('backpack.auth.login'));
    }

    #[Test]
    public function an_admin_user_without_the_permission_cannot_list_or_create(): void
    {
        $backendUser = $this->backendUser();
        $this->actingAs($backendUser, 'backpack');

        $this->get('/admin/business')->assertForbidden();
        $this->get('/admin/business/create')->assertForbidden();
    }

    #[Test]
    public function creates_a_business_for_the_chosen_owner_within_the_owners_limit(): void
    {
        $owner = $this->frontendUser();

        $this->actingAs($this->admin, 'backpack')
            ->post('/admin/business', [
                'user_id' => $owner->id,
                'name' => 'Admin Firma',
                'registration_country' => 'CZ',
                'company_number' => '27082440',
                'street' => 'Hlavní 1',
                'city' => 'Brno',
                'postal_code' => '602 00',
                'country' => 'CZ',
                '_save_action' => 'save_and_back',
            ])
            ->assertRedirect();

        $business = Business::query()->where('user_id', $owner->id)->sole();
        $this->assertSame('27082440', $business->company_number);
        $this->assertSame('Brno', Address::query()->where('business_id', $business->id)->value('city'));
        $this->assertSame($business->id, $owner->fresh()?->default_business_id);
    }

    #[Test]
    public function the_owners_capacity_limit_applies_in_the_admin(): void
    {
        $owner = $this->frontendUser();
        $this->limitActive('business', 1);
        Business::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($this->admin, 'backpack')
            ->from('/admin/business/create')
            ->post('/admin/business', [
                'user_id' => $owner->id,
                'name' => 'Druhá',
                'country' => 'CZ',
            ])
            ->assertRedirect('/admin/business/create');

        $this->assertSame(1, Business::query()->where('user_id', $owner->id)->count());
    }

    #[Test]
    public function a_czech_company_number_of_another_business_of_the_owner_is_refused(): void
    {
        $owner = $this->frontendUser();
        Business::factory()->archived()->create([
            'user_id' => $owner->id,
            'company_number' => '27082440',
        ]);

        $this->actingAs($this->admin, 'backpack')
            ->from('/admin/business/create')
            ->post('/admin/business', [
                'user_id' => $owner->id,
                'name' => 'Kopie',
                'registration_country' => 'CZ',
                'company_number' => '27082440',
                'country' => 'CZ',
            ])
            ->assertSessionHasErrors('company_number');
    }

    #[Test]
    public function updates_a_business_and_clears_emptied_fields(): void
    {
        $business = Business::factory()->create(['email' => 'old@example.test']);

        $this->actingAs($this->admin, 'backpack')
            ->put('/admin/business/' . $business->id, [
                'id' => $business->id,
                'name' => 'Nový název',
                'email' => '',
                'country' => 'CZ',
                '_save_action' => 'save_and_back',
            ])
            ->assertRedirect();

        $business->refresh();
        $this->assertSame('Nový název', $business->name);
        $this->assertNull($business->email);
    }

    #[Test]
    public function archives_and_restores_a_business_of_its_owner(): void
    {
        $owner = $this->frontendUser();
        $business = Business::factory()->create(['user_id' => $owner->id]);
        $owner->forceFill(['default_business_id' => $business->id])->save();

        $this->actingAs($this->admin, 'backpack')
            ->post(route('admin.businesses.archive', ['id' => $business->id]))
            ->assertRedirect();
        $this->assertNotNull($business->fresh()?->archived_at);
        $this->assertNull($owner->fresh()?->default_business_id);

        $this->post(route('admin.businesses.restore', ['id' => $business->id]))->assertRedirect();
        $this->assertNull($business->fresh()?->archived_at);
        $this->assertSame($business->id, $owner->fresh()?->default_business_id);
    }

    #[Test]
    public function archiving_a_default_accepts_an_explicit_replacement_without_set_default_permission(): void
    {
        $owner = $this->frontendUser();
        $business = Business::factory()->create(['user_id' => $owner->id]);
        $replacement = Business::factory()->create(['user_id' => $owner->id]);
        $owner->forceFill(['default_business_id' => $business->id])->save();
        $this->actingAs($this->backendUser('can_archive_business', 'can_view_business'), 'backpack');
        $this->postJson('/admin/business/search', ['start' => 0, 'length' => 100])
            ->assertOk()->assertSee('replacement_business_id')->assertSee($replacement->name);
        $this->post(route('admin.businesses.archive', ['id' => $business->id]), [
            'replacement_business_id' => $replacement->id,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNotNull($business->fresh()->archived_at);
        $this->assertSame($replacement->id, $owner->fresh()->default_business_id);
    }

    #[Test]
    public function archiving_rejects_a_foreign_archived_or_unchanged_replacement(): void
    {
        $owner = $this->frontendUser();
        $business = Business::factory()->create(['user_id' => $owner->id]);
        $owner->forceFill(['default_business_id' => $business->id])->save();
        $archived = Business::factory()->archived()->create(['user_id' => $owner->id]);
        $foreign = Business::factory()->create();
        $this->actingAs($this->admin, 'backpack');
        foreach ([
            $business->id,
            $archived->id,
            $foreign->id,
            'invalid',
        ] as $replacement) {
            $this->post(route('admin.businesses.archive', ['id' => $business->id]), [
                'replacement_business_id' => $replacement,
            ])->assertSessionHasErrors('replacement_business_id');
            $this->assertNull($business->fresh()->archived_at);
            $this->assertSame($business->id, $owner->fresh()->default_business_id);
        }
    }

    #[Test]
    public function archiving_needs_the_permission(): void
    {
        $business = Business::factory()->create();
        $backendUser = $this->backendUser('can_view_business');

        $this->actingAs($backendUser, 'backpack')
            ->post(route('admin.businesses.archive', ['id' => $business->id]))
            ->assertForbidden();
    }

    #[Test]
    public function the_admin_has_no_delete_operation(): void
    {
        $business = Business::factory()->create();

        $this->actingAs($this->admin, 'backpack')
            ->delete('/admin/business/' . $business->id)
            ->assertStatus(405);
    }
}
