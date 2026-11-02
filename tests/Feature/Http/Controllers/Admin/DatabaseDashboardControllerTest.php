<?php

namespace Tests\Feature\Http\Controllers\Admin;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class DatabaseDashboardControllerTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAdminTestEnvironment();
    }

    #[Test]
    public function dashboard_renders_in_the_admin_language(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $httpClient = $this->withSession(['backpack.language-switcher.locale' => 'cs']);
        $httpResponse = $httpClient->get('/admin/database-dashboard');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSee(
            __('admin/database.dashboard.title'),
        );
        $httpResponse = $httpResponse->assertSee(
            __('admin/database.dashboard.run_optimization'),
        );
        $httpResponse->assertDontSeeText('admin.database.');
    }
}
