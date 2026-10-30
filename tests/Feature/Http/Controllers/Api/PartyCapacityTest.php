<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Business;
use App\Models\Contact;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PartyCapacityTest extends TestCase
{
    #[Test]
    public function party_capacity_counts_only_the_owners_active_records(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('frontend_user');
        Contact::factory()->count(3)->create(['user_id' => $owner->id]);
        Contact::factory()->archived()->create(['user_id' => $owner->id]);
        Contact::factory()->create();
        Business::factory()->create(['user_id' => $owner->id]);
        Business::factory()->archived()->create(['user_id' => $owner->id]);
        Business::factory()->create();

        $this->actingAs($owner, 'web');
        $this->getJson('/api/uels/contact/data')->assertOk()
            ->assertJsonPath('current_usage', 3)->assertJsonPath('limit', 16)
            ->assertJsonPath('remaining_count', 13)->assertJsonPath('best_stats_period', 'lifetime');
        $this->getJson('/api/uels/business/data')->assertOk()
            ->assertJsonPath('current_usage', 1)->assertJsonPath('limit', 10)
            ->assertJsonPath('remaining_count', 9);
        $this->getJson('/api/uels')->assertOk()
            ->assertJsonPath('entities.contact.current_usage', 3)
            ->assertJsonPath('entities.business.current_usage', 1)
            ->assertJsonMissingPath('entities.client')->assertJsonMissingPath('entities.supplier');
    }

    #[Test]
    public function full_capacity_is_reported_instead_of_unlimited_access(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('frontend_user');
        Contact::factory()->count(16)->create(['user_id' => $owner->id]);

        $this->actingAs($owner, 'web')->getJson('/api/uels/contact/data')->assertOk()
            ->assertJsonPath('current_usage', 16)->assertJsonPath('limit', 16)
            ->assertJsonPath('remaining_count', 0)->assertJsonPath('is_at_limit', true);
    }

    #[Test]
    public function capacity_requires_authentication_and_a_configured_limit(): void
    {
        $this->getJson('/api/uels/contact/data')->assertUnauthorized();
        $owner = User::factory()->create();
        $owner->givePermissionTo('frontend.api.access');
        $this->actingAs($owner, 'web')->getJson('/api/uels/contact/data')->assertOk()
            ->assertJsonPath('limit', 0)->assertJsonPath('status', 'no_permission');
    }

    #[Test]
    public function obsolete_entity_names_are_rejected(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('frontend_user');
        $this->actingAs($owner, 'web');
        $this->getJson('/api/uels/client/data')->assertStatus(400);
        $this->getJson('/api/uels/supplier/data')->assertStatus(400);
    }
}
