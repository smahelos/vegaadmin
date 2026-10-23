<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class DatabaseDashboardControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesAdminTestEnvironment;

    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAdminTestEnvironment();
    }

    #[Test]
    public function dashboard_renders_in_the_admin_language(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $this->withSession(['backpack.language-switcher.locale' => 'cs'])
            ->get('/admin/database-dashboard')
            ->assertOk()
            ->assertSee(__('admin/database.dashboard.title'))
            ->assertSee(__('admin/database.dashboard.run_optimization'))
            ->assertDontSeeText('admin.database.');
    }
}
