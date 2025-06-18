<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\CronTaskRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CronTaskRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create required permissions for 'backpack' guard
        Permission::firstOrCreate(['name' => 'backpack.access', 'guard_name' => 'backpack']);
        
        $this->user = User::factory()->create();

        // Set up test route
        Route::post('/admin/cron-task', function (CronTaskRequest $request) {
            return response()->json(['success' => true]);
        })->middleware('web');
    }

    #[Test]
    public function authorize_returns_false_when_user_not_authenticated(): void
    {
        $request = new CronTaskRequest();
        $this->assertFalse($request->authorize());
    }

    #[Test]
    public function authorize_returns_true_when_user_authenticated_with_backpack(): void
    {
        $this->actingAs($this->user, 'backpack');
        
        $request = new CronTaskRequest();
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function validation_passes_with_valid_daily_data(): void
    {
        $data = [
            'name' => 'Test Cron Task',
            'base_command' => 'test:command',
            'command_params' => '--param=value',
            'frequency' => 'daily',
            'run_at' => '09:00',
            'is_active' => true,
            'description' => 'Test description'
        ];

        $request = new CronTaskRequest();
        $validator = Validator::make($data, $request->rules());
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_valid_weekly_data(): void
    {
        $data = [
            'name' => 'Weekly Test Task',
            'base_command' => 'weekly:command',
            'frequency' => 'weekly',
            'day_of_week' => 1, // Monday
            'run_at' => '10:30',
            'is_active' => false
        ];

        $request = new CronTaskRequest();
        $validator = Validator::make($data, $request->rules());
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_valid_monthly_data(): void
    {
        $data = [
            'name' => 'Monthly Test Task',
            'base_command' => 'monthly:command',
            'frequency' => 'monthly',
            'day_of_month' => 15,
            'run_at' => '14:00',
            'is_active' => true
        ];

        $request = new CronTaskRequest();
        $validator = Validator::make($data, $request->rules());
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_valid_custom_cron_expression(): void
    {
        $data = [
            'name' => 'Custom Test Task',
            'base_command' => 'custom:command',
            'frequency' => 'custom',
            'custom_expression' => '0 */6 * * *', // Every 6 hours
            'is_active' => true
        ];

        $request = new CronTaskRequest();
        $validator = Validator::make($data, $request->rules());
        
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_when_name_is_missing(): void
    {
        $data = [
            'base_command' => 'test:command',
            'frequency' => 'daily'
        ];

        $request = new CronTaskRequest();
        $validator = Validator::make($data, $request->rules());
        
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_base_command_is_missing(): void
    {
        $data = [
            'name' => 'Test Task',
            'frequency' => 'daily'
        ];

        $request = new CronTaskRequest();
        $validator = Validator::make($data, $request->rules());
        
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('base_command', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_frequency_is_invalid(): void
    {
        $data = [
            'name' => 'Test Task',
            'base_command' => 'test:command',
            'frequency' => 'invalid_frequency'
        ];

        $request = new CronTaskRequest();
        $validator = Validator::make($data, $request->rules());
        
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('frequency', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_custom_expression_missing_for_custom_frequency(): void
    {
        $data = [
            'name' => 'Custom Task',
            'base_command' => 'custom:command',
            'frequency' => 'custom'
            // custom_expression is missing
        ];

        $request = new CronTaskRequest();
        $validator = Validator::make($data, $request->rules());
        
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('custom_expression', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_custom_expression_is_invalid(): void
    {
        $data = [
            'name' => 'Custom Task',
            'base_command' => 'custom:command',
            'frequency' => 'custom',
            'custom_expression' => 'invalid cron' // Too few parts - should fail
        ];

        // Create actual request instance with data to test real validation
        $request = CronTaskRequest::create('/test', 'POST', $data);
        $request->setContainer(app());
        
        // Run validation through the request
        $validator = app('validator')->make($data, $request->rules());
        
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('custom_expression', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_day_of_week_is_out_of_range(): void
    {
        $data = [
            'name' => 'Weekly Task',
            'base_command' => 'weekly:command',
            'frequency' => 'weekly',
            'day_of_week' => 7 // Invalid, should be 0-6
        ];

        $request = new CronTaskRequest();
        $validator = Validator::make($data, $request->rules());
        
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('day_of_week', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_day_of_month_is_out_of_range(): void
    {
        $data = [
            'name' => 'Monthly Task',
            'base_command' => 'monthly:command',
            'frequency' => 'monthly',
            'day_of_month' => 32 // Invalid, should be 1-31
        ];

        $request = new CronTaskRequest();
        $validator = Validator::make($data, $request->rules());
        
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('day_of_month', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_run_at_has_invalid_format(): void
    {
        $data = [
            'name' => 'Test Task',
            'base_command' => 'test:command',
            'frequency' => 'daily',
            'run_at' => '25:00' // Invalid time format
        ];

        $request = new CronTaskRequest();
        $validator = Validator::make($data, $request->rules());
        
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('run_at', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_name_exceeds_max_length(): void
    {
        $data = [
            'name' => str_repeat('a', 256), // Exceeds max 255 characters
            'base_command' => 'test:command',
            'frequency' => 'daily'
        ];

        $request = new CronTaskRequest();
        $validator = Validator::make($data, $request->rules());
        
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_base_command_exceeds_max_length(): void
    {
        $data = [
            'name' => 'Test Task',
            'base_command' => str_repeat('a', 101), // Exceeds max 100 characters
            'frequency' => 'daily'
        ];

        $request = new CronTaskRequest();
        $validator = Validator::make($data, $request->rules());
        
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('base_command', $validator->errors()->toArray());
    }

    #[Test]
    public function attributes_returns_expected_field_names(): void
    {
        $request = new CronTaskRequest();
        $attributes = $request->attributes();
        
        $this->assertIsArray($attributes);
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('base_command', $attributes);
        $this->assertArrayHasKey('command_params', $attributes);
        $this->assertArrayHasKey('frequency', $attributes);
        $this->assertArrayHasKey('custom_expression', $attributes);
        $this->assertArrayHasKey('run_at', $attributes);
        $this->assertArrayHasKey('day_of_week', $attributes);
        $this->assertArrayHasKey('day_of_month', $attributes);
        $this->assertArrayHasKey('is_active', $attributes);
        $this->assertArrayHasKey('description', $attributes);
    }

    #[Test]
    public function messages_returns_expected_error_messages(): void
    {
        $request = new CronTaskRequest();
        $messages = $request->messages();
        
        $this->assertIsArray($messages);
        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('command.required', $messages);
        $this->assertArrayHasKey('frequency.required', $messages);
    }

    #[Test]
    public function http_request_with_valid_data_passes(): void
    {
        $this->withoutMiddleware();
        
        $response = $this->actingAs($this->user, 'backpack')
            ->postJson('/admin/cron-task', [
                'name' => 'HTTP Test Task',
                'base_command' => 'test:http',
                'frequency' => 'daily',
                'run_at' => '12:00',
                'is_active' => true
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    #[Test]
    public function http_request_with_invalid_data_fails(): void
    {
        $this->withoutMiddleware();
        
        $response = $this->actingAs($this->user, 'backpack')
            ->postJson('/admin/cron-task', [
                // Missing required fields
                'description' => 'Task without required fields'
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'base_command', 'frequency']);
    }

    #[Test]
    public function http_request_without_authentication_fails(): void
    {
        $this->withoutMiddleware();
        
        $response = $this->postJson('/admin/cron-task', [
            'name' => 'Unauthorized Test',
            'base_command' => 'test:unauthorized',
            'frequency' => 'daily'
        ]);

        $response->assertStatus(403);
    }
}
