<?php

namespace Database\Factories;

use App\Models\ArtisanCommand;
use App\Models\ArtisanCommandCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArtisanCommand>
 */
class ArtisanCommandFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ArtisanCommand::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $commands = [
            'cache:clear' => 'Clear application cache',
            'migrate' => 'Run database migrations',
            'queue:work' => 'Start processing queue jobs',
            'config:cache' => 'Create cache file for faster configuration',
            'route:cache' => 'Create route cache file',
            'view:cache' => 'Compile all view files',
            'optimize' => 'Cache configuration, routes and views',
            'storage:link' => 'Create symbolic link from public/storage to storage/app/public',
            'backup:run' => 'Run application backup',
            'down' => 'Put application into maintenance mode',
        ];
        
        $commandName = $this->faker->randomElement(array_keys($commands));
        $description = $commands[$commandName];
        
        return [
            'name' => ucfirst(str_replace(':', ' ', $commandName)),
            'description' => $description,
            'category_id' => ArtisanCommandCategory::factory(),
            'is_active' => $this->faker->boolean(85), // 85% chance of being active
            'command' => "php artisan {$commandName}",
            'parameters_description' => $this->faker->optional(0.6)->sentence(),
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * Indicate that the command is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the command is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a command for a specific category.
     */
    public function forCategory(ArtisanCommandCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }

    /**
     * Create a cache command.
     */
    public function cacheCommand(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Cache Clear',
            'description' => 'Clear application cache',
            'command' => 'php artisan cache:clear',
            'parameters_description' => 'Optional: --tags for specific cache tags',
            'is_active' => true,
        ]);
    }

    /**
     * Create a migration command.
     */
    public function migrationCommand(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Database Migration',
            'description' => 'Run database migrations',
            'command' => 'php artisan migrate',
            'parameters_description' => 'Optional: --force for production',
            'is_active' => true,
        ]);
    }

    /**
     * Create a command with specific details.
     */
    public function withCommand(string $name, string $command, string $description = null): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'command' => $command,
            'description' => $description ?? "Execute {$command}",
        ]);
    }
}
