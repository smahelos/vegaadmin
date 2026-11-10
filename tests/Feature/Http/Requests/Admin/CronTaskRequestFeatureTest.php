<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Domain\User\Enums\Permission;
use App\Http\Requests\Admin\CronTaskRequest;
use App\Models\Permission as PermissionModel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class CronTaskRequestFeatureTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up admin test environment with roles and permissions
        $this->setUpAdminTestEnvironment();
        // Set up test route
        $route = Route::post(
            '/test-cron-task',
            function (CronTaskRequest $request) {
                $responseFactory = response();

                return $responseFactory->json(['success' => true]);
            },
        );
        $route->middleware('web');
    }

    #[Test]
    public function authorize_returns_false_when_user_not_authenticated(): void
    {
        $request = new CronTaskRequest;
        $this->assertFalse($request->authorize());
    }

    #[Test]
    public function authorize_returns_true_when_user_authenticated_with_backpack(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $request = new CronTaskRequest;
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function validation_passes_with_valid_daily_data(): void
    {
        $data = [
            'name' => 'Test Cron Task',
            'command' => 'db:monitor',
            'parameters' => '--metric=all --store',
            'frequency' => 'daily',
            'run_at' => '09:00',
            'is_active' => true,
            'description' => 'Test description',
        ];

        $request = new CronTaskRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_valid_weekly_data(): void
    {
        $data = [
            'name' => 'Weekly Test Task',
            'command' => 'db:monitor',
            'frequency' => 'weekly',
            'day_of_week' => 1, // Monday
            'run_at' => '10:30',
            'is_active' => false,
        ];

        $request = new CronTaskRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_valid_monthly_data(): void
    {
        $data = [
            'name' => 'Monthly Test Task',
            'command' => 'db:monitor',
            'frequency' => 'monthly',
            'day_of_month' => 15,
            'run_at' => '14:00',
            'is_active' => true,
        ];

        $request = new CronTaskRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_valid_custom_cron_expression(): void
    {
        $data = [
            'name' => 'Custom Test Task',
            'command' => 'db:monitor',
            'frequency' => 'custom',
            'custom_expression' => '0 */6 * * *', // Every 6 hours
            'is_active' => true,
        ];

        $request = new CronTaskRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_when_name_is_missing(): void
    {
        $data = [
            'command' => 'db:monitor',
            'frequency' => 'daily',
        ];

        $request = new CronTaskRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertArrayHasKey('name', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_command_is_missing(): void
    {
        $data = [
            'name' => 'Test Task',
            'frequency' => 'daily',
        ];

        $request = new CronTaskRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertArrayHasKey('command', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_frequency_is_invalid(): void
    {
        $data = [
            'name' => 'Test Task',
            'command' => 'db:monitor',
            'frequency' => 'invalid_frequency',
        ];

        $request = new CronTaskRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertArrayHasKey('frequency', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_custom_expression_missing_for_custom_frequency(): void
    {
        $data = [
            'name' => 'Custom Task',
            'command' => 'db:monitor',
            'frequency' => 'custom',
            // custom_expression is missing
        ];

        $request = new CronTaskRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertArrayHasKey('custom_expression', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_custom_expression_is_invalid(): void
    {
        $data = [
            'name' => 'Custom Task',
            'command' => 'db:monitor',
            'frequency' => 'custom',
            'custom_expression' => 'invalid cron', // Too few parts - should fail
        ];

        // Create actual request instance with data to test real validation
        $request = CronTaskRequest::create(
            '/test',
            'POST',
            $data,
        );
        $request->setContainer(app());

        // Run validation through the request
        $validatorFactory = app('validator');
        $validator = $validatorFactory->make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertArrayHasKey('custom_expression', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_day_of_week_is_out_of_range(): void
    {
        $data = [
            'name' => 'Weekly Task',
            'command' => 'db:monitor',
            'frequency' => 'weekly',
            'day_of_week' => 7, // Invalid, should be 0-6
        ];

        $request = new CronTaskRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertArrayHasKey('day_of_week', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_day_of_month_is_out_of_range(): void
    {
        $data = [
            'name' => 'Monthly Task',
            'command' => 'db:monitor',
            'frequency' => 'monthly',
            'day_of_month' => 32, // Invalid, should be 1-31
        ];

        $request = new CronTaskRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertArrayHasKey('day_of_month', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_run_at_has_invalid_format(): void
    {
        $data = [
            'name' => 'Test Task',
            'command' => 'db:monitor',
            'frequency' => 'daily',
            'run_at' => '25:00', // Invalid time format
        ];

        $request = new CronTaskRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertArrayHasKey('run_at', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_name_exceeds_max_length(): void
    {
        $data = [
            'name' => str_repeat('a', 256), // Exceeds max 255 characters
            'command' => 'db:monitor',
            'frequency' => 'daily',
        ];

        $request = new CronTaskRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertArrayHasKey('name', $errors->toArray());
    }

    #[Test]
    public function http_request_with_valid_data_passes(): void
    {
        $this->withoutMiddleware();

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->postJson(
            '/test-cron-task',
            [
                'name' => 'HTTP Test Task',
                'command' => 'db:monitor',
                'frequency' => 'daily',
                'run_at' => '12:00',
                'is_active' => true,
            ],
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    #[Test]
    public function http_request_with_invalid_data_fails(): void
    {
        $this->withoutMiddleware();

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->postJson(
            '/test-cron-task',
            [
                // Missing required fields
                'description' => 'Task without required fields',
            ],
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name',
            'command',
            'frequency',
        ]);
    }

    #[Test]
    public function http_request_without_authentication_fails(): void
    {
        $this->withoutMiddleware();

        $response = $this->postJson(
            '/test-cron-task',
            [
                'name' => 'Unauthorized Test',
                'command' => 'db:monitor',
                'frequency' => 'daily',
            ],
        );

        $response->assertStatus(403);
    }

    #[Test]
    public function custom_frequency_without_expression_asks_for_the_expression(): void
    {
        $this->withoutMiddleware();

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->postJson(
            '/test-cron-task',
            [
                'name' => 'Custom Task',
                'command' => 'db:monitor',
                'frequency' => 'custom',
                'is_active' => true,
            ],
        );

        $response->assertStatus(422);
        $this->assertSame(
            [__(
                'validation.required',
                ['attribute' => __('cron_tasks.fields.custom_expression')],
            )],
            $response->json('errors.custom_expression'),
        );
    }

    #[Test]
    public function invalid_cron_expression_gets_the_format_message(): void
    {
        $this->withoutMiddleware();

        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $response = $httpClient->postJson(
            '/test-cron-task',
            [
                'name' => 'Custom Task',
                'command' => 'db:monitor',
                'frequency' => 'custom',
                'custom_expression' => 'every minute',
                'is_active' => true,
            ],
        );

        $response->assertStatus(422);
        $this->assertSame(
            [__('cron_tasks.validation.cron_expression')],
            $response->json('errors.custom_expression'),
        );
    }

    #[Test]
    public function validation_rejects_a_command_outside_the_scheduled_commands(): void
    {
        $data = [
            'name' => 'Wipe',
            'command' => 'db:wipe',
            'frequency' => 'daily',
        ];

        $request = new CronTaskRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertArrayHasKey('command', $errors->toArray());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsafeParameters(): array
    {
        return [
            'command separator' => ['--days=1; rm -rf /'],
            'command substitution' => ['--days=$(id)'],
            'backticks' => ['--days=`id`'],
            'pipe' => ['--days=1|id'],
            'quotes' => ['--table="a b"'],
            'redirect' => ['--days=1>/tmp/out'],
            'argument' => ['users'],
            'short option' => ['-v'],
        ];
    }

    #[Test]
    #[DataProvider('unsafeParameters')]
    public function validation_rejects_parameters_that_are_not_plain_options(string $parameters): void
    {
        $data = [
            'name' => 'Monitor',
            'command' => 'db:monitor',
            'parameters' => $parameters,
            'frequency' => 'daily',
        ];

        $request = new CronTaskRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertSame(
            [__('cron_tasks.validation.parameters')],
            $errors->get('parameters'),
        );
    }

    #[Test]
    public function creating_requires_the_create_permission(): void
    {
        $this->withoutMiddleware();
        $this->regularUser->givePermissionTo(
            PermissionModel::findByName(Permission::CRON_TASK_UPDATE->value, 'backpack'),
        );

        $httpClient = $this->actingAs($this->regularUser, 'backpack');
        $response = $httpClient->postJson(
            '/test-cron-task',
            [
                'name' => 'Monitor',
                'command' => 'db:monitor',
                'frequency' => 'daily',
            ],
        );
        $response->assertForbidden();

        $this->regularUser->givePermissionTo(
            PermissionModel::findByName(Permission::CRON_TASK_CREATE->value, 'backpack'),
        );
        $httpClient = $this->actingAs($this->regularUser, 'backpack');
        $response = $httpClient->postJson(
            '/test-cron-task',
            [
                'name' => 'Monitor',
                'command' => 'db:monitor',
                'frequency' => 'daily',
            ],
        );
        $response->assertOk();
    }
}
