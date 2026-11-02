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
        $batchFactory = $contactFactory->count(3);
        $batchFactory->create(['user_id' => $owner->id]);
        Contact::factory()->archived()->create(['user_id' => $owner->id]);
        $contactFactory->create();
        $businessFactory = Business::factory();
        $businessFactory->create(['user_id' => $owner->id]);
        Business::factory()->archived()->create(['user_id' => $owner->id]);
        $businessFactory->create();

        $this->actingAs($owner, 'web');
        $httpResponse = $this->getJson('/api/uels/contact/data');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertJsonPath('current_usage', 3);
        $httpResponse = $httpResponse->assertJsonPath('limit', 16);
        $httpResponse = $httpResponse->assertJsonPath('remaining_count', 13);
        $httpResponse->assertJsonPath('best_stats_period', 'lifetime');
        $httpResponse = $this->getJson('/api/uels/business/data');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertJsonPath('current_usage', 1);
        $httpResponse = $httpResponse->assertJsonPath('limit', 10);
        $httpResponse->assertJsonPath('remaining_count', 9);
        $httpResponse = $this->getJson('/api/uels');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertJsonPath('entities.contact.current_usage', 3);
        $httpResponse = $httpResponse->assertJsonPath('entities.business.current_usage', 1);
        $httpResponse = $httpResponse->assertJsonMissingPath('entities.client');
        $httpResponse->assertJsonMissingPath('entities.supplier');
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
        $httpResponse = $httpClient->getJson('/api/uels/contact/data');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertJsonPath('limit', 0);
        $httpResponse->assertJsonPath('status', 'no_permission');
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
        $httpResponse = $this->getJson('/api/uels/supplier/data');
        $httpResponse->assertStatus(400);
    }
}
