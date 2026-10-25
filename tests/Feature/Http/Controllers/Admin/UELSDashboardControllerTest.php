<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\EntityLimitUsage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class UELSDashboardControllerTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAdminTestEnvironment();
    }

    #[Test]
    public function dashboard_renders_with_users(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $this->get('/admin/uels-dashboard')
            ->assertOk()
            ->assertSee('id="user_select"', false);
    }

    #[Test]
    public function dashboard_shows_translated_labels(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        EntityLimitUsage::create([
            'user_id' => null,
            'entity_type' => 'invoice',
            'metric_type' => 'count',
            'period_type' => 'monthly',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'current_value' => 3,
            'last_reset_at' => now(),
        ]);

        $this->withSession(['backpack.language-switcher.locale' => 'cs'])
            ->get('/admin/uels-dashboard')
            ->assertOk()
            ->assertSee(__('entity_limits.dashboard.recent.title'))
            ->assertSee(__('entity_limits.entity_types.invoice.singular'))
            ->assertSee(__('entity_limits.periods.monthly'))
            ->assertSee(__('entity_limits.usages.labels.anonymous'))
            ->assertDontSeeText('Recent Activity')
            ->assertDontSeeText('Bulk Reset Usage');
    }
}
