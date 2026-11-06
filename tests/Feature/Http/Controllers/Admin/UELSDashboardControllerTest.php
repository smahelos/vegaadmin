<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\EntityLimitUsage;
use Carbon\CarbonImmutable;
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

        $httpResponse = $this->get('/admin/uels-dashboard');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertSee('id="user_select"', false);
    }

    #[Test]
    public function dashboard_shows_translated_labels(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $now = CarbonImmutable::now();
        EntityLimitUsage::create(
            [
                'user_id' => null,
                'entity_type' => 'invoice',
                'metric_type' => 'count',
                'period_type' => 'monthly',
                'period_start' => $now->startOfMonth(),
                'period_end' => $now->endOfMonth(),
                'current_value' => 3,
                'last_reset_at' => now(),
            ],
        );

        $httpClient = $this->withSession(['backpack.language-switcher.locale' => 'cs']);
        $httpResponse = $httpClient->get('/admin/uels-dashboard');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSee(
            __('entity_limits.dashboard.recent.title'),
        );
        $httpResponse = $httpResponse->assertSee(
            __('entity_limits.entity_types.invoice.singular'),
        );
        $httpResponse = $httpResponse->assertSee(
            __('entity_limits.periods.monthly'),
        );
        $httpResponse = $httpResponse->assertSee(
            __('entity_limits.usages.labels.anonymous'),
        );
        $httpResponse = $httpResponse->assertDontSeeText('Recent Activity');
        $httpResponse->assertDontSeeText('Bulk Reset Usage');
    }
}
