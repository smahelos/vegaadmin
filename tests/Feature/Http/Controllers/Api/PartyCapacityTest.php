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
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $owner->assignRole('frontend_user');
        $contactFactory = Contact::factory();
        $contactFactory = $contactFactory->count(3);
        $contactFactory->create(['user_id' => $owner->id]);
        Contact::factory()->archived()->create(['user_id' => $owner->id]);
        $contactFactory2 = Contact::factory();
        $contactFactory2->create();
        $businessFactory = Business::factory();
        $businessFactory->create(['user_id' => $owner->id]);
        Business::factory()->archived()->create(['user_id' => $owner->id]);
        $businessFactory2 = Business::factory();
        $businessFactory2->create();

        $this->actingAs($owner, 'web');
        $httpResponse = $this->getJson('/api/uels/contact/data');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertJsonPath('current_usage', 3);
        $httpResponse = $httpResponse->assertJsonPath('limit', 16);
        $httpResponse = $httpResponse->assertJsonPath('remaining_count', 13);
        $httpResponse->assertJsonPath('best_stats_period', 'lifetime');
        $httpResponse2 = $this->getJson('/api/uels/business/data');
        $httpResponse2 = $httpResponse2->assertOk();
        $httpResponse2 = $httpResponse2->assertJsonPath('current_usage', 1);
        $httpResponse2 = $httpResponse2->assertJsonPath('limit', 10);
        $httpResponse2->assertJsonPath('remaining_count', 9);
        $httpResponse3 = $this->getJson('/api/uels');
        $httpResponse3 = $httpResponse3->assertOk();
        $httpResponse3 = $httpResponse3->assertJsonPath('entities.contact.current_usage', 3);
        $httpResponse3 = $httpResponse3->assertJsonPath('entities.business.current_usage', 1);
        $httpResponse3 = $httpResponse3->assertJsonMissingPath('entities.client');
        $httpResponse3->assertJsonMissingPath('entities.supplier');
    }

    #[Test]
    public function full_capacity_is_reported_instead_of_unlimited_access(): void
    {
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $owner->assignRole('frontend_user');
        $contactFactory = Contact::factory();
        $contactFactory = $contactFactory->count(16);
        $contactFactory->create(['user_id' => $owner->id]);

        $httpClient = $this->actingAs($owner, 'web');
        $httpResponse = $httpClient->getJson('/api/uels/contact/data');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertJsonPath('current_usage', 16);
        $httpResponse = $httpResponse->assertJsonPath('limit', 16);
        $httpResponse = $httpResponse->assertJsonPath('remaining_count', 0);
        $httpResponse->assertJsonPath('is_at_limit', true);
    }

    #[Test]
    public function capacity_requires_authentication_and_a_configured_limit(): void
    {
        $httpResponse = $this->getJson('/api/uels/contact/data');
        $httpResponse->assertUnauthorized();
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $owner->givePermissionTo('frontend.api.access');
        $httpClient = $this->actingAs($owner, 'web');
        $httpResponse2 = $httpClient->getJson('/api/uels/contact/data');
        $httpResponse2 = $httpResponse2->assertOk();
        $httpResponse2 = $httpResponse2->assertJsonPath('limit', 0);
        $httpResponse2->assertJsonPath('status', 'no_permission');
    }

    #[Test]
    public function obsolete_entity_names_are_rejected(): void
    {
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $owner->assignRole('frontend_user');
        $this->actingAs($owner, 'web');
        $httpResponse = $this->getJson('/api/uels/client/data');
        $httpResponse->assertStatus(400);
        $httpResponse2 = $this->getJson('/api/uels/supplier/data');
        $httpResponse2->assertStatus(400);
    }
}
