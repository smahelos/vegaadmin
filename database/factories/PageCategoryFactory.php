<?php

namespace Database\Factories;

use App\Models\PageCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PageCategory>
 */
class PageCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PageCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $locales = config('app.available_locales', ['cs', 'en', 'de', 'sk']);
        $baseName = $this->faker->words(2, true);
        $baseSlug = $this->faker->unique()->slug();
        $baseDescription = $this->faker->sentence();
        
        $nameData = [];
        $slugData = [];
        $descriptionData = [];
        
        // Generate data for each locale
        foreach ($locales as $locale) {
            $nameData[$locale] = $baseName . ' ' . strtoupper($locale);
            $slugData[$locale] = $baseSlug . '-' . $locale;
            $descriptionData[$locale] = $baseDescription . ' [' . strtoupper($locale) . ']';
        }
        
        return [
            'name' => $nameData,
            'slug' => $slugData,
            'description' => $descriptionData,
        ];
    }

    /**
     * Indicate that the category is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => 'Active category: ' . $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => 'Inactive category: ' . $this->faker->sentence(),
        ]);
    }
}
