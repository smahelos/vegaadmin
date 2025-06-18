<?php

namespace Database\Factories;

use App\Models\CronTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CronTask>
 */
class CronTaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = CronTask::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $commands = [
            'cache:clear',
            'queue:work',
            'backup:run',
            'logs:clear',
            'optimize:clear',
            'route:cache',
            'config:cache',
            'view:cache',
        ];

        $frequencies = ['daily', 'weekly', 'monthly', 'custom'];

        return [
            'name' => $this->faker->words(3, true) . ' Task',
            'command' => $this->faker->randomElement($commands),
            'frequency' => $this->faker->randomElement($frequencies),
            'custom_expression' => $this->faker->optional()->regexify('[0-9*]{1,2} [0-9*]{1,2} [0-9*]{1,2} [0-9*]{1,2} [0-9*]{1,2}'),
            'run_at' => $this->faker->optional()->time(),
            'day_of_week' => $this->faker->optional()->numberBetween(0, 6),
            'day_of_month' => $this->faker->optional()->numberBetween(1, 31),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'description' => $this->faker->optional()->sentence(),
            'last_run' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'last_output' => $this->faker->optional()->paragraph(),
        ];
    }

    /**
     * Create an active cron task.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive cron task.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a daily cron task.
     */
    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'daily',
            'run_at' => $this->faker->time(),
            'day_of_week' => null,
            'day_of_month' => null,
            'custom_expression' => null,
        ]);
    }

    /**
     * Create a weekly cron task.
     */
    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'weekly',
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'run_at' => $this->faker->time(),
            'day_of_month' => null,
            'custom_expression' => null,
        ]);
    }

    /**
     * Create a monthly cron task.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'monthly',
            'day_of_month' => $this->faker->numberBetween(1, 28),
            'run_at' => $this->faker->time(),
            'day_of_week' => null,
            'custom_expression' => null,
        ]);
    }

    /**
     * Create a custom cron task.
     */
    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'custom',
            'custom_expression' => '0 0 * * *', // Daily at midnight
            'run_at' => null,
            'day_of_week' => null,
            'day_of_month' => null,
        ]);
    }

    /**
     * Create a cron task with specific command.
     */
    public function withCommand(string $command): static
    {
        return $this->state(fn (array $attributes) => [
            'command' => $command,
        ]);
    }

    /**
     * Create a cron task that has run recently.
     */
    public function recentlyRun(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_run' => $this->faker->dateTimeBetween('-24 hours', 'now'),
            'last_output' => 'Task executed successfully',
        ]);
    }

    /**
     * Create a cron task with error output.
     */
    public function withError(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_run' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'last_output' => 'ERROR: ' . $this->faker->sentence(),
        ]);
    }
}
