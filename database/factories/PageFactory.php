<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\PageCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Page::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unique = $this->faker->unique();
        $optionalStart = $this->faker->optional(0.7);
        $optionalEnd = $this->faker->optional(0.3);

        return [
            'name' => $this->faker->sentence(4),
            'slug' => $unique->slug(),
            'content' => $this->faker->paragraphs(3, true),
            'description' => $this->faker->sentence(),
            'category_id' => PageCategory::factory(),
            'parent_id' => null,
            'published' => $this->faker->boolean(70), // 70% chance of being published
            'published_by' => User::factory(),
            'publishing_start' => $optionalStart->dateTimeBetween('-1 year', 'now'),
            'publishing_end' => $optionalEnd->dateTimeBetween('now', '+1 year'),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'main_image' => null,
            'images' => null,
        ];
    }

    /**
     * Indicate that the page is published.
     */
    public function published(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'published' => true,
                'publishing_start' => $this->faker->dateTimeBetween('-1 year', 'now'),
            ],
        );
    }

    /**
     * Indicate that the page is not published (draft).
     */
    public function draft(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'published' => false,
                'publishing_start' => null,
                'publishing_end' => null,
            ],
        );
    }

    /**
     * Indicate that the page has a parent.
     */
    public function withParent(?Page $parent = null): static
    {
        return $this->state(
            function () use ($parent): array {
                $parentId = $parent?->id;
                if ($parentId === null) {
                    $pageFactory = Page::factory();
                    $page = $pageFactory->create();
                    $parentId = $page->id;
                }

                return ['parent_id' => $parentId];
            },
        );
    }

    /**
     * Indicate that the page has no parent (root page).
     */
    public function root(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => null,
        ]);
    }

    /**
     * Indicate that the page belongs to a specific category.
     */
    public function inCategory(PageCategory $category): static
    {
        return $this->state(
            fn (array $attributes) => [
                'category_id' => $category->id,
            ],
        );
    }
}
