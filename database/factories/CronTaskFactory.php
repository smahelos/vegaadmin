<?php

namespace Database\Factories;

use App\Domain\Shared\Console\Enums\ScheduledCommand;
use App\Models\CronTask;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<CronTask>
 */
class CronTaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Model>
     */
    protected $model = CronTask::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $frequencies = [
            'daily',
            'weekly',
            'monthly',
            'custom',
        ];
        $optional = $this->faker->optional();

        return [
            'name' => $this->faker->words(3, true) . ' Task',
            'command' => $this->faker->randomElement(ScheduledCommand::cases()),
            'parameters' => null,
            'frequency' => $this->faker->randomElement($frequencies),
            'custom_expression' => $optional->regexify(
                '[0-9*]{1,2} [0-9*]{1,2} [0-9*]{1,2} [0-9*]{1,2} [0-9*]{1,2}',
            ),
            'run_at' => $optional->time(),
            'day_of_week' => $optional->numberBetween(0, 6),
            'day_of_month' => $optional->numberBetween(1, 31),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'description' => $optional->sentence(),
            'last_run' => $optional->dateTimeBetween('-1 month', 'now'),
            'last_output' => $optional->paragraph(),
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
        return $this->state(
            fn (array $attributes) => [
                'frequency' => 'daily',
                'run_at' => $this->faker->time(),
                'day_of_week' => null,
                'day_of_month' => null,
                'custom_expression' => null,
            ],
        );
    }

    /**
     * Create a weekly cron task.
     */
    public function weekly(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'frequency' => 'weekly',
                'day_of_week' => $this->faker->numberBetween(0, 6),
                'run_at' => $this->faker->time(),
                'day_of_month' => null,
                'custom_expression' => null,
            ],
        );
    }

    /**
     * Create a monthly cron task.
     */
    public function monthly(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'frequency' => 'monthly',
                'day_of_month' => $this->faker->numberBetween(1, 28),
                'run_at' => $this->faker->time(),
                'day_of_week' => null,
                'custom_expression' => null,
            ],
        );
    }

    /**
     * Create a custom cron task.
     */
    public function custom(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'frequency' => 'custom',
                'custom_expression' => '0 0 * * *', // Daily at midnight
                'run_at' => null,
                'day_of_week' => null,
                'day_of_month' => null,
            ],
        );
    }

    /**
     * Create a cron task with specific command.
     */
    public function withCommand(ScheduledCommand $command, ?string $parameters = null): static
    {
        return $this->state(
            fn (array $attributes) => [
                'command' => $command,
                'parameters' => $parameters,
            ],
        );
    }

    /**
     * Create a cron task that has run recently.
     */
    public function recentlyRun(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'last_run' => $this->faker->dateTimeBetween('-24 hours', 'now'),
                'last_output' => 'Task executed successfully',
            ],
        );
    }

    /**
     * Create a cron task with error output.
     */
    public function withError(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'last_run' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'last_output' => 'ERROR: ' . $this->faker->sentence(),
            ],
        );
    }
}
