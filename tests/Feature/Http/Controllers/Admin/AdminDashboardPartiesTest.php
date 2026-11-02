<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Business;
use App\Models\Contact;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

final class AdminDashboardPartiesTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    #[Test]
    public function dashboard_counts_only_active_businesses_and_contacts_and_links_to_business_creation(): void
    {
        $this->setUpAdminTestEnvironment();
        $businessFactory = Business::factory();
        $businessFactory->create(['user_id' => $this->regularUser->id]);
        $businessFactory->create(
            ['user_id' => $this->regularUser->id, 'archived_at' => now()],
        );
        $contactFactory = Contact::factory();
        $contactFactory->create(['user_id' => $this->regularUser->id]);
        $contactFactory->create(
            ['user_id' => $this->regularUser->id, 'archived_at' => now()],
        );
        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $httpResponse = $httpClient->get('/admin/dashboard');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertViewHas(
            'dashboardStats',
            fn (array $stats): bool => $stats['total_businesses'] === 1 && $stats['total_contacts'] === 1 && $stats['recent_contacts'] === 1,
        );
        $httpResponse = $httpResponse->assertSee(
            backpack_url('business/create'),
        );
        $httpResponse->assertDontSee(
            backpack_url('client/create'),
        );
    }
}
