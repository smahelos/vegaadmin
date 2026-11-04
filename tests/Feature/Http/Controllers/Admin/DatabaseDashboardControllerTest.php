<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\PerformanceMetric;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /** @return array<string, array{string, string}> */
    public static function routes(): array
    {
        return [
            'dashboard' => ['GET', '/admin/database-dashboard'],
            'run command' => ['POST', '/admin/database-dashboard/run-command'],
            'clean metrics' => ['POST', '/admin/database-dashboard/clean-metrics'],
            'metrics stats' => ['POST', '/admin/database-dashboard/metrics-stats'],
            'resolve alert' => ['POST', '/admin/database-dashboard/resolve-alert'],
        ];
    }

    #[Test]
    #[DataProvider('routes')]
    public function guest_is_refused(string $method, string $uri): void
    {
        $httpResponse = $this->json($method, $uri);
        $httpResponse->assertUnauthorized();
    }

    #[Test]
    #[DataProvider('routes')]
    public function admin_without_the_permission_is_refused(string $method, string $uri): void
    {
        $this->actingAs($this->regularUser, 'backpack');

        $httpResponse = $this->json($method, $uri);
        $httpResponse->assertForbidden();
    }

    #[Test]
    public function refused_cleanup_keeps_the_metrics(): void
    {
        $metricFactory = PerformanceMetric::factory();
        $metric = $metricFactory->create(['metadata' => ['generated' => true]]);
        $this->actingAs($this->regularUser, 'backpack');

        $this->postJson('/admin/database-dashboard/clean-metrics', ['cleanup_type' => 'sample']);

        $this->assertDatabaseHas('performance_metrics', ['id' => $metric->id]);
    }

    #[Test]
    public function refused_command_does_not_run(): void
    {
        $expectation = Artisan::shouldReceive('call');
        $expectation->never();
        $this->actingAs($this->regularUser, 'backpack');

        $this->postJson('/admin/database-dashboard/run-command', ['command' => 'optimize']);
    }

    #[Test]
    public function admin_with_the_permission_reaches_the_actions(): void
    {
        $metricFactory = PerformanceMetric::factory();
        $metric = $metricFactory->create(['metadata' => ['generated' => true]]);
        $this->actingAs($this->adminUser, 'backpack');

        $httpResponse = $this->postJson('/admin/database-dashboard/metrics-stats');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertJsonPath('success', true);
        $httpResponse = $this->postJson('/admin/database-dashboard/clean-metrics', ['cleanup_type' => 'sample']);
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertJsonPath('success', true);
        $this->assertDatabaseMissing('performance_metrics', ['id' => $metric->id]);
    }
}
