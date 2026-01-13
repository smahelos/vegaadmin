<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\PageCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Page>
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
        return [
            'name' => $this->faker->sentence(4),
            'slug' => $this->faker->unique()->slug(),
            'content' => $this->faker->paragraphs(3, true),
            'description' => $this->faker->sentence(),
            'category_id' => PageCategory::factory(),
            'parent_id' => null,
            'published' => $this->faker->boolean(70), // 70% chance of being published
            'published_by' => User::factory(),
            'publishing_start' => $this->faker->optional(0.7)->dateTimeBetween('-1 year', 'now'),
            'publishing_end' => $this->faker->optional(0.3)->dateTimeBetween('now', '+1 year'),
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
        return $this->state(fn (array $attributes) => [
            'published' => true,
            'publishing_start' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the page is not published (draft).
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'published' => false,
            'publishing_start' => null,
            'publishing_end' => null,
        ]);
    }

    /**
     * Indicate that the page has a parent.
     */
    public function withParent(?Page $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent?->id ?? Page::factory()->create()->id,
        ]);
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
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }

    /**
     * Indicate that the page has a specific author.
     */
    public function byAuthor(User $author): static
    {
        return $this->state(fn (array $attributes) => [
            'published_by' => $author->id,
        ]);
    }

    /**
     * Indicate that the page has a main image.
     */
    public function withMainImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'main_image' => 'uploads/pages/main-image-' . $this->faker->uuid() . '.jpg',
        ]);
    }

    /**
     * Indicate that the page has a gallery of images.
     */
    public function withImageGallery(int $count = 3): static
    {
        $images = [];
        for ($i = 0; $i < $count; $i++) {
            $images[] = 'uploads/pages/gallery-image-' . $this->faker->uuid() . '.jpg';
        }

        return $this->state(fn (array $attributes) => [
            'images' => json_encode($images),
        ]);
    }

    /**
     * Indicate that the page has both main image and gallery.
     */
    public function withAllImages(int $galleryCount = 3): static
    {
        return $this->withMainImage()->withImageGallery($galleryCount);
    }
}
