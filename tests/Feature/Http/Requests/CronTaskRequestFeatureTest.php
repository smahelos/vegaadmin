<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\CronTaskRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for CronTaskRequest
 * 
 * Tests complete validation flow with HTTP context and database interactions
 * Tests cron task validation scenarios, authorization, and validation with different frequencies
 */
class CronTaskRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected array $validCronTaskData;

    /**
     * Set up the test environment.
     * Creates test user and valid cron task data for request testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();
        
        // Set up valid cron task data
        $this->setupValidCronTaskData();
    }

    /**
     * Setup valid cron task data for testing
     */
    private function setupValidCronTaskData(): void
    {
        $this->validCronTaskData = [
            'name' => $this->faker->words(3, true),
            'command' => 'php artisan cache:clear',
            'frequency' => 'daily',
            'run_at' => '02:00',
            'is_active' => true,
            'description' => $this->faker->sentence,
        ];
    }

    #[Test]
    public function validation_passes_with_valid_daily_frequency()
    {
        $request = new CronTaskRequest();
        $validator = Validator::make($this->validCronTaskData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_passes_with_weekly_frequency()
    {
        $validData = $this->validCronTaskData;
        $validData['frequency'] = 'weekly';
        $validData['day_of_week'] = 1; // Monday

        $request = new CronTaskRequest();
        $validator = Validator::make($validData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_passes_with_monthly_frequency()
    {
        $validData = $this->validCronTaskData;
        $validData['frequency'] = 'monthly';
        $validData['day_of_month'] = 15;

        $request = new CronTaskRequest();
        $validator = Validator::make($validData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_passes_with_custom_frequency()
    {
        $validData = $this->validCronTaskData;
        $validData['frequency'] = 'custom';
        $validData['custom_expression'] = '0 */6 * * *'; // Every 6 hours

        $request = new CronTaskRequest();
        $validator = Validator::make($validData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_when_required_fields_missing()
    {
        $requiredFields = ['name', 'command', 'frequency'];
        
        foreach ($requiredFields as $field) {
            $invalidData = $this->validCronTaskData;
            unset($invalidData[$field]);

            $request = new CronTaskRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail when {$field} is missing");
            $this->assertArrayHasKey($field, $validator->errors()->toArray(), "Should have error for missing {$field}");
        }
    }

    #[Test]
    public function validation_fails_with_invalid_frequency()
    {
        $invalidData = $this->validCronTaskData;
        $invalidData['frequency'] = 'invalid-frequency';

        $request = new CronTaskRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('frequency', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_custom_expression_missing_for_custom_frequency()
    {
        $invalidData = $this->validCronTaskData;
        $invalidData['frequency'] = 'custom';
        unset($invalidData['custom_expression']);

        $request = new CronTaskRequest();
        $validator = Validator::make($invalidData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('custom_expression', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_invalid_day_of_week()
    {
        $invalidValues = [-1, 7, 10];
        
        foreach ($invalidValues as $value) {
            $invalidData = $this->validCronTaskData;
            $invalidData['day_of_week'] = $value;

            $request = new CronTaskRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail for day_of_week value: {$value}");
            $this->assertArrayHasKey('day_of_week', $validator->errors()->toArray());
        }
    }

    #[Test]
    public function validation_fails_with_invalid_day_of_month()
    {
        $invalidValues = [0, 32, 50];
        
        foreach ($invalidValues as $value) {
            $invalidData = $this->validCronTaskData;
            $invalidData['day_of_month'] = $value;

            $request = new CronTaskRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail for day_of_month value: {$value}");
            $this->assertArrayHasKey('day_of_month', $validator->errors()->toArray());
        }
    }

    #[Test]
    public function validation_fails_with_invalid_time_format()
    {
        $invalidTimes = ['25:00', '12:60', 'invalid', '2:30'];
        
        foreach ($invalidTimes as $time) {
            $invalidData = $this->validCronTaskData;
            $invalidData['run_at'] = $time;

            $request = new CronTaskRequest();
            $validator = Validator::make($invalidData, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Validation should fail for run_at time: {$time}");
            $this->assertArrayHasKey('run_at', $validator->errors()->toArray());
        }
    }

    #[Test]
    public function validation_passes_with_valid_time_formats()
    {
        $validTimes = ['00:00', '12:30', '23:59', '08:15'];
        
        foreach ($validTimes as $time) {
            $validData = $this->validCronTaskData;
            $validData['run_at'] = $time;

            $request = new CronTaskRequest();
            $validator = Validator::make($validData, $request->rules(), $request->messages());

            $this->assertFalse($validator->fails(), "Validation should pass for run_at time: {$time}");
        }
    }

    #[Test]
    public function authorization_passes_when_authenticated()
    {
        $this->actingAs($this->user);

        $request = new CronTaskRequest();
        
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function authorization_fails_when_not_authenticated()
    {
        $request = new CronTaskRequest();
        
        $this->assertFalse($request->authorize());
    }

    #[Test]
    public function request_has_custom_attributes()
    {
        $request = new CronTaskRequest();
        $attributes = $request->attributes();

        $expectedKeys = [
            'name', 'command', 'frequency', 'custom_expression',
            'run_at', 'day_of_week', 'day_of_month', 'is_active', 'description'
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $attributes, "Should have custom attribute for {$key}");
        }
    }

    #[Test]
    public function request_has_custom_validation_messages()
    {
        $request = new CronTaskRequest();
        $messages = $request->messages();

        $expectedKeys = [
            'name.required',
            'command.required',
            'frequency.required',
            'frequency.in',
            'custom_expression.required_if',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $messages, "Should have custom message for {$key}");
        }
    }

    #[Test]
    public function validation_passes_with_minimal_required_data()
    {
        $minimalData = [
            'name' => 'Test Task',
            'command' => 'php artisan test:command',
            'frequency' => 'daily',
        ];

        $request = new CronTaskRequest();
        $validator = Validator::make($minimalData, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_passes_with_all_frequencies()
    {
        $frequencies = ['daily', 'weekly', 'monthly', 'custom'];
        
        foreach ($frequencies as $frequency) {
            $validData = [
                'name' => "Test Task {$frequency}",
                'command' => 'php artisan cache:clear',
                'frequency' => $frequency,
            ];
            
            if ($frequency === 'custom') {
                $validData['custom_expression'] = '0 2 * * *';
            }

            $request = new CronTaskRequest();
            $validator = Validator::make($validData, $request->rules(), $request->messages());

            $this->assertFalse($validator->fails(), "Validation should pass for frequency: {$frequency}");
        }
    }
}
