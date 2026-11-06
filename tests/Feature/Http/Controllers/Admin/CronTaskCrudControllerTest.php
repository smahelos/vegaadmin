<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\Shared\Console\Enums\ScheduledCommand;
use App\Models\CronTask;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

final class CronTaskCrudControllerTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();
    }

    #[Test]
    public function create_form_offers_only_the_scheduled_commands(): void
    {
        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->get('/admin/cron-task/create');

        $response->assertOk();
        foreach (ScheduledCommand::cases() as $command) {
            $response->assertSee($command->value);
        }
        $response->assertDontSee('db:wipe');
    }

    #[Test]
    public function store_persists_the_command_and_its_options(): void
    {
        $name = 'Monitor ' . uniqid();

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->post(
            '/admin/cron-task',
            [
                'name' => $name,
                'command' => 'db:monitor',
                'parameters' => '--metric=all --store',
                'frequency' => 'daily',
                'run_at' => '09:30',
                'is_active' => true,
            ],
        );
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'cron_tasks',
            [
                'name' => $name,
                'command' => 'db:monitor',
                'parameters' => '--metric=all --store',
                'frequency' => 'daily',
            ],
        );
    }

    #[Test]
    public function store_rejects_a_command_outside_the_scheduled_commands(): void
    {
        $name = 'Wipe ' . uniqid();

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->post(
            '/admin/cron-task',
            [
                'name' => $name,
                'command' => 'db:wipe',
                'frequency' => 'daily',
                'is_active' => true,
            ],
        );
        $response->assertSessionHasErrors(['command']);

        $this->assertDatabaseMissing('cron_tasks', ['name' => $name]);
    }

    #[Test]
    public function store_rejects_parameters_with_shell_characters(): void
    {
        $name = 'Injected ' . uniqid();

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->post(
            '/admin/cron-task',
            [
                'name' => $name,
                'command' => 'db:monitor',
                'parameters' => '--metric=all; touch /tmp/billex',
                'frequency' => 'daily',
                'is_active' => true,
            ],
        );
        $response->assertSessionHasErrors(['parameters']);

        $this->assertDatabaseMissing('cron_tasks', ['name' => $name]);
    }

    #[Test]
    public function validation_fails_for_invalid_custom_expression(): void
    {
        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->post(
            '/admin/cron-task',
            [
                'name' => 'Invalid Custom ' . uniqid(),
                'command' => 'db:monitor',
                'frequency' => 'custom',
                'custom_expression' => 'invalid expression',
                'is_active' => true,
            ],
        );
        $response->assertSessionHasErrors(['custom_expression']);
    }

    #[Test]
    public function run_executes_the_task_and_reports_success(): void
    {
        Mail::fake();
        Notification::fake();
        Queue::fake();
        $cronTaskFactory = CronTask::factory();
        $commandFactory = $cronTaskFactory->withCommand(ScheduledCommand::CHECK_INVOICE_PAYMENT_STATUS);
        $task = $commandFactory->create(['last_run' => null]);

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $httpClient = $httpClient->from('/admin/cron-task');
        $httpResponse = $httpClient->post(
            route('admin.cron-tasks.run', ['id' => $task->id]),
        );
        $httpResponse = $httpResponse->assertRedirect('/admin/cron-task');
        $httpResponse = $httpResponse->assertSessionHas(
            'alert_messages.success',
            [__('cron_tasks.messages.task_executed')],
        );
        $httpResponse->assertSessionMissing('alert_messages.error');

        $task->refresh();
        $this->assertNotNull($task->last_run);
    }

    #[Test]
    public function run_reports_a_failed_task_as_an_error(): void
    {
        $cronTaskFactory = CronTask::factory();
        $commandFactory = $cronTaskFactory->withCommand(
            ScheduledCommand::CHECK_INVOICE_PAYMENT_STATUS,
            '--days-before=soon',
        );
        $task = $commandFactory->create();

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $httpClient = $httpClient->from('/admin/cron-task');
        $httpResponse = $httpClient->post(
            route('admin.cron-tasks.run', ['id' => $task->id]),
        );
        $httpResponse = $httpResponse->assertRedirect('/admin/cron-task');
        $httpResponse = $httpResponse->assertSessionHas(
            'alert_messages.error',
            [__('cron_tasks.messages.execution_failed')],
        );
        $httpResponse->assertSessionMissing('alert_messages.success');
    }

    #[Test]
    public function run_is_not_reachable_over_get(): void
    {
        $cronTaskFactory = CronTask::factory();
        $task = $cronTaskFactory->create(['last_run' => null]);

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $httpResponse = $httpClient->get('/admin/cron-task/' . $task->id . '/run');
        $httpResponse->assertMethodNotAllowed();

        $task->refresh();
        $this->assertNull($task->last_run);
    }

    #[Test]
    public function run_requires_the_run_permission(): void
    {
        $cronTaskFactory = CronTask::factory();
        $task = $cronTaskFactory->create(['last_run' => null]);
        $user = $this->backendUser(
            [
                'can_view_cron_task',
                'can_create_cron_task',
                'can_update_cron_task',
                'can_delete_cron_task',
            ],
        );

        $httpClient = $this->actingAs($user, 'backpack');
        $httpResponse = $httpClient->post(
            route('admin.cron-tasks.run', ['id' => $task->id]),
        );
        $httpResponse->assertForbidden();

        $task->refresh();
        $this->assertNull($task->last_run);
    }

    #[Test]
    public function every_operation_is_denied_without_its_permission(): void
    {
        $cronTaskFactory = CronTask::factory();
        $task = $cronTaskFactory->create();
        $user = $this->backendUser([]);

        $httpClient = $this->actingAs($user, 'backpack');
        $response = $httpClient->get('/admin/cron-task');
        $response->assertForbidden();
        $response = $httpClient->get('/admin/cron-task/' . $task->id . '/show');
        $response->assertForbidden();
        $response = $httpClient->get('/admin/cron-task/create');
        $response->assertForbidden();
        $response = $httpClient->get('/admin/cron-task/' . $task->id . '/edit');
        $response->assertForbidden();
        $response = $httpClient->delete('/admin/cron-task/' . $task->id);
        $response->assertForbidden();

        $this->assertDatabaseHas('cron_tasks', ['id' => $task->id]);
    }

    #[Test]
    public function the_view_permission_opens_the_list_but_not_the_editing(): void
    {
        $cronTaskFactory = CronTask::factory();
        $task = $cronTaskFactory->create();
        $user = $this->backendUser(['can_view_cron_task']);

        $httpClient = $this->actingAs($user, 'backpack');
        $response = $httpClient->get('/admin/cron-task');
        $response->assertOk();
        $response = $httpClient->get('/admin/cron-task/' . $task->id . '/edit');
        $response->assertForbidden();
        $response = $httpClient->delete('/admin/cron-task/' . $task->id);
        $response->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function backendUser(array $permissions): User
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole(
            Role::findByName('backend_user', 'backpack'),
        );
        foreach (['backpack.access', ...$permissions] as $permission) {
            $user->givePermissionTo(
                Permission::findByName($permission, 'backpack'),
            );
        }

        return $user;
    }
}
