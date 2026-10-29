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
        Business::factory()->create(['user_id' => $this->regularUser->id]);
        Business::factory()->create(['user_id' => $this->regularUser->id, 'archived_at' => now()]);
        Contact::factory()->create(['user_id' => $this->regularUser->id]);
        Contact::factory()->create(['user_id' => $this->regularUser->id, 'archived_at' => now()]);
        $this->actingAs($this->adminUser, 'backpack')->get('/admin/dashboard')
            ->assertOk()
            ->assertViewHas('dashboardStats', fn (array $stats): bool => $stats['total_businesses'] === 1 && $stats['total_contacts'] === 1 && $stats['recent_contacts'] === 1)
            ->assertSee(backpack_url('business/create'))
            ->assertDontSee(backpack_url('client/create'));
    }
}
