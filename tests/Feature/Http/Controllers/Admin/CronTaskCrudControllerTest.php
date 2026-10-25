<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\Shared\Console\Contracts\ArtisanCommandsServiceInterface;
use App\Models\CronTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

// No Mockery needed; simple in-memory stub implements interface

// Lightweight stub for ArtisanCommandsServiceInterface to avoid mocking framework overhead
if (! class_exists(CronTaskCommandsServiceStub::class)) {
    class CronTaskCommandsServiceStub implements ArtisanCommandsServiceInterface
    {
        public function getAllCommands(bool $onlyNames = false): array
        {
            return ['inspire' => 'php artisan inspire'];
        }

        public function getCommandsByCategory(?string $categorySlug = null, bool $withoutCategory = false): array
        {
            return [
                'inspire' => 'php artisan inspire',
                'queue:work' => 'php artisan queue:work',
            ];
        }

        public function getAllCategories(bool $onlyActive = true): array
        {
            return ['cron' => 'Cron'];
        }

        public function clearCommandsCache(): void {}

        public function getAllCommandsWithDetails(): array
        {
            return [
                'inspire' => [
                    'name' => 'inspire',
                    'description' => 'Display an inspiring quote',
                    'signature' => 'inspire',
                ],
            ];
        }
    }
}

class CronTaskCrudControllerTest extends TestCase
{
    use CreatesAdminTestEnvironment;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();

        // Trait already ensures permission list including can_create_edit_cron_task for backpack guard
        if (! $this->adminUser->hasPermissionTo('can_create_edit_cron_task', 'backpack')) {
            $this->adminUser->givePermissionTo('can_create_edit_cron_task');
        }
    }

    #[Test]
    public function create_form_displays_commands_select_from_injected_service(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        // Mock service to ensure DI + usage in controller
        $this->app->instance(ArtisanCommandsServiceInterface::class, new CronTaskCommandsServiceStub);

        $response = $this->get('/admin/cron-task/create');
        $response->assertStatus(200);
        $response->assertSee('inspire');
        $response->assertSee('queue:work');
    }

    #[Test]
    public function store_persists_minimal_daily_cron_task(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $this->app->instance(ArtisanCommandsServiceInterface::class, new CronTaskCommandsServiceStub);

        $payload = [
            'name' => 'Daily Inspire ' . uniqid(),
            'base_command' => 'inspire',
            'command' => 'inspire',
            'command_params' => '',
            'frequency' => 'daily',
            'run_at' => '09:30',
            'is_active' => true,
        ];

        $response = $this->post('/admin/cron-task', $payload);
        $response->assertStatus(302); // Redirect after create

        $this->assertDatabaseHas('cron_tasks', [
            'name' => $payload['name'],
            'frequency' => 'daily',
        ]);

        $cronTask = CronTask::where('name', $payload['name'])->first();
        $this->assertNotNull($cronTask);
        $this->assertEquals('inspire', $cronTask->base_command); // accessor
    }

    #[Test]
    public function validation_fails_for_invalid_custom_expression(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $this->app->instance(ArtisanCommandsServiceInterface::class, new CronTaskCommandsServiceStub);

        $payload = [
            'name' => 'Invalid Custom ' . uniqid(),
            'base_command' => 'inspire',
            'command' => 'inspire',
            'command_params' => '',
            'frequency' => 'custom',
            'custom_expression' => 'invalid expression',
            'is_active' => true,
        ];

        $response = $this->post('/admin/cron-task', $payload);
        $response->assertStatus(302); // Validation redirect
        $response->assertSessionHasErrors(['custom_expression']);
    }

    #[Test]
    public function run_executes_the_task_and_reports_success(): void
    {
        $task = CronTask::factory()->create([
            'command' => 'inspire',
            'last_run' => null,
        ]);

        $this->actingAs($this->adminUser, 'backpack')
            ->from('/admin/cron-task')
            ->post(route('admin.cron-tasks.run', ['id' => $task->id]))
            ->assertRedirect('/admin/cron-task')
            ->assertSessionHas('alert_messages.success', [__('cron_tasks.messages.task_executed')])
            ->assertSessionMissing('alert_messages.error');

        $this->assertNotNull($task->fresh()->last_run);
    }

    #[Test]
    public function run_reports_a_failed_task_as_an_error(): void
    {
        $task = CronTask::factory()->create(['command' => 'billex:no-such-command']);

        $this->actingAs($this->adminUser, 'backpack')
            ->from('/admin/cron-task')
            ->post(route('admin.cron-tasks.run', ['id' => $task->id]))
            ->assertRedirect('/admin/cron-task')
            ->assertSessionHas('alert_messages.error', [__('cron_tasks.messages.execution_failed')])
            ->assertSessionMissing('alert_messages.success');
    }

    #[Test]
    public function run_is_not_reachable_over_get(): void
    {
        $task = CronTask::factory()->create([
            'command' => 'inspire',
            'last_run' => null,
        ]);

        $this->actingAs($this->adminUser, 'backpack')
            ->get('/admin/cron-task/' . $task->id . '/run')
            ->assertMethodNotAllowed();

        $this->assertNull($task->fresh()->last_run);
    }

    #[Test]
    public function run_requires_the_cron_task_permission(): void
    {
        $task = CronTask::factory()->create([
            'command' => 'inspire',
            'last_run' => null,
        ]);
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('backend_user', 'backpack'));
        $user->givePermissionTo(Permission::findByName('backpack.access', 'backpack'));

        $this->actingAs($user, 'backpack')
            ->post(route('admin.cron-tasks.run', ['id' => $task->id]))
            ->assertForbidden();

        $this->assertNull($task->fresh()->last_run);
    }
}
