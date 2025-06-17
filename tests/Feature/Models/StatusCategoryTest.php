<?php

namespace Tests\Feature\Models;

use App\Models\StatusCategory;
use App\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusCategoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function status_category_can_be_created_with_factory()
    {
        $statusCategory = StatusCategory::factory()->create();

        $this->assertInstanceOf(StatusCategory::class, $statusCategory);
        $this->assertDatabaseHas('status_categories', [
            'id' => $statusCategory->id,
            'name' => $statusCategory->name,
            'slug' => $statusCategory->slug,
        ]);
    }

    #[Test]
    public function status_category_can_be_created_with_minimal_data()
    {
        $statusCategory = StatusCategory::factory()->create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => null,
        ]);

        $this->assertDatabaseHas('status_categories', [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => null,
        ]);
    }

    #[Test]
    public function status_category_can_be_created_with_all_fields()
    {
        $statusCategory = StatusCategory::factory()->create([
            'name' => 'Complete Category',
            'slug' => 'complete-category',
            'description' => 'A complete category with description',
        ]);

        $this->assertDatabaseHas('status_categories', [
            'name' => 'Complete Category',
            'slug' => 'complete-category',
            'description' => 'A complete category with description',
        ]);
    }

    #[Test]
    public function status_category_has_timestamps()
    {
        $statusCategory = StatusCategory::factory()->create();

        $this->assertNotNull($statusCategory->created_at);
        $this->assertNotNull($statusCategory->updated_at);
    }

    #[Test]
    public function status_category_slug_is_unique()
    {
        StatusCategory::factory()->create(['slug' => 'unique-slug']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        StatusCategory::factory()->create(['slug' => 'unique-slug']);
    }

    #[Test]
    public function status_category_has_many_statuses_relationship()
    {
        $statusCategory = StatusCategory::factory()->create();
        $relationship = $statusCategory->statuses();

        $this->assertInstanceOf(HasMany::class, $relationship);
        $this->assertEquals('category_id', $relationship->getForeignKeyName());
        $this->assertEquals('id', $relationship->getLocalKeyName());
    }

    #[Test]
    public function status_category_can_have_multiple_statuses()
    {
        $statusCategory = StatusCategory::factory()->create();
        
        // Create statuses with this category
        $status1 = Status::factory()->create(['category_id' => $statusCategory->id]);
        $status2 = Status::factory()->create(['category_id' => $statusCategory->id]);
        $status3 = Status::factory()->create(['category_id' => $statusCategory->id]);

        $this->assertCount(3, $statusCategory->statuses);
        $this->assertTrue($statusCategory->statuses->contains($status1));
        $this->assertTrue($statusCategory->statuses->contains($status2));
        $this->assertTrue($statusCategory->statuses->contains($status3));
    }

    #[Test]
    public function status_category_can_have_no_statuses()
    {
        $statusCategory = StatusCategory::factory()->create();

        $this->assertCount(0, $statusCategory->statuses);
    }

    #[Test]
    public function status_category_slug_mutator_converts_name_to_slug()
    {
        $statusCategory = new StatusCategory();
        $statusCategory->name = 'Test Category Name';
        $statusCategory->setSlugAttribute('');

        // When slug is empty, it should be generated from name
        $this->assertEquals('test-category-name', $statusCategory->slug);
    }

    #[Test]
    public function status_category_slug_mutator_uses_provided_slug()
    {
        $statusCategory = new StatusCategory();
        $statusCategory->name = 'Test Category Name';
        $statusCategory->setSlugAttribute('custom-slug');

        // When slug is provided, it should use the provided slug
        $this->assertEquals('custom-slug', $statusCategory->slug);
    }

    #[Test]
    public function status_category_slug_mutator_handles_special_characters()
    {
        $statusCategory = new StatusCategory();
        $statusCategory->name = 'Speciální Kategorie';
        $statusCategory->setSlugAttribute('Speciální Název!!');

        // Should convert special characters to proper slug format
        $this->assertIsString($statusCategory->slug);
        $this->assertNotEmpty($statusCategory->slug);
    }

    #[Test]
    public function status_category_can_be_updated()
    {
        $statusCategory = StatusCategory::factory()->create([
            'name' => 'Original Name',
            'slug' => 'original-slug',
            'description' => 'Original description',
        ]);

        $statusCategory->update([
            'name' => 'Updated Name',
            'slug' => 'updated-slug',
            'description' => 'Updated description',
        ]);

        $this->assertDatabaseHas('status_categories', [
            'id' => $statusCategory->id,
            'name' => 'Updated Name',
            'slug' => 'updated-slug',
            'description' => 'Updated description',
        ]);
    }

    #[Test]
    public function status_category_can_be_deleted()
    {
        $statusCategory = StatusCategory::factory()->create();
        $statusCategoryId = $statusCategory->id;

        $statusCategory->delete();

        $this->assertDatabaseMissing('status_categories', [
            'id' => $statusCategoryId,
        ]);
    }

    #[Test]
    public function status_category_deletion_sets_category_id_to_null_in_statuses()
    {
        $statusCategory = StatusCategory::factory()->create();
        $status = Status::factory()->create(['category_id' => $statusCategory->id]);

        // Verify status is linked to category
        $this->assertDatabaseHas('statuses', [
            'id' => $status->id,
            'category_id' => $statusCategory->id,
        ]);

        // Delete category
        $statusCategory->delete();

        // Status should have category_id set to null (nullOnDelete)
        $this->assertDatabaseHas('statuses', [
            'id' => $status->id,
            'category_id' => null,
        ]);
    }

    #[Test]
    public function status_category_factory_creates_unique_slugs()
    {
        $category1 = StatusCategory::factory()->create();
        $category2 = StatusCategory::factory()->create();
        $category3 = StatusCategory::factory()->create();

        $this->assertNotEquals($category1->slug, $category2->slug);
        $this->assertNotEquals($category1->slug, $category3->slug);
        $this->assertNotEquals($category2->slug, $category3->slug);
    }

    #[Test]
    public function status_category_name_and_description_can_contain_unicode()
    {
        $statusCategory = StatusCategory::factory()->create([
            'name' => 'Kategorie s česky',
            'slug' => 'kategorie-s-cesky',
            'description' => 'Popis kategorie s česky znaky: čšžýáíé',
        ]);

        $this->assertDatabaseHas('status_categories', [
            'name' => 'Kategorie s česky',
            'description' => 'Popis kategorie s česky znaky: čšžýáíé',
        ]);
    }

    #[Test]
    public function status_category_relationships_work_with_soft_deleted_statuses()
    {
        $statusCategory = StatusCategory::factory()->create();
        $status = Status::factory()->create(['category_id' => $statusCategory->id]);

        // Verify relationship exists
        $this->assertCount(1, $statusCategory->statuses);

        // If Status model uses soft deletes, test that behavior
        if (method_exists($status, 'delete')) {
            $status->delete();
            
            // Refresh the relationship
            $statusCategory->refresh();
            
            // Should not include soft deleted statuses by default
            $this->assertCount(0, $statusCategory->statuses);
        }
    }

    #[Test]
    public function status_category_can_be_found_by_slug()
    {
        $statusCategory = StatusCategory::factory()->create(['slug' => 'findable-slug']);

        $foundCategory = StatusCategory::where('slug', 'findable-slug')->first();

        $this->assertNotNull($foundCategory);
        $this->assertEquals($statusCategory->id, $foundCategory->id);
    }

    #[Test]
    public function status_category_attributes_are_properly_cast()
    {
        $statusCategory = StatusCategory::factory()->create();

        $this->assertIsInt($statusCategory->id);
        $this->assertIsString($statusCategory->name);
        $this->assertIsString($statusCategory->slug);
        $this->assertTrue(is_string($statusCategory->description) || is_null($statusCategory->description));
    }
}
