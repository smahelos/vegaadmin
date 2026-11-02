<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Status;
use App\Models\StatusCategory;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class StatusCategoryCrudControllerTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAdminTestEnvironment();
    }

    #[Test]
    public function show_lists_statuses_of_the_category(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $statusCategoryFactory = StatusCategory::factory();
        $category = $statusCategoryFactory->create();
        $statusFactory = Status::factory();
        $status = $statusFactory->create(
            ['category_id' => $category->id, 'name' => 'Status ' . uniqid()],
        );
        $otherStatus = $statusFactory->create(['name' => 'Other ' . uniqid()]);

        $response = $this->get("/admin/status-category/{$category->id}/show");

        $response->assertOk();
        $response->assertSee(
            __('statuses.categories.fields.statuses'),
        );
        $response->assertSeeInOrder(
            [
                '<th>' . __('statuses.fields.slug') . '</th>',
                $status->name,
                $status->slug,
            ],
            false,
        );
        $response->assertDontSee($otherStatus->name);
        $response->assertSeeInOrder(
            [
                __('statuses.categories.fields.statuses_count'),
                '<span>',
                '1',
            ],
            false,
        );
        $response->assertSee(
            __('admin.general.created_at'),
        );
    }

    #[Test]
    public function list_counts_statuses_in_the_list_query(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $statusCategoryFactory = StatusCategory::factory();
        $category = $statusCategoryFactory->create(['name' => 'Category ' . uniqid()]);
        $statusFactory = Status::factory();
        $statusFactory = $statusFactory->count(2);
        $statusFactory->create(['category_id' => $category->id]);
        $statusCategoryFactory->create();

        DB::enableQueryLog();
        $response = $this->postJson('/admin/status-category/search', [
            'draw' => 1,
            'start' => 0,
            'length' => 100,
        ]);
        $statusQueries = array_filter(
            DB::getQueryLog(),
            fn ($query) => str_starts_with($query['query'], 'select * from "statuses"'),
        );

        $response->assertOk();
        $collection = collect(
            $response->json('data'),
        );
        $row = $collection->first(
            fn ($row) => str_contains(
                implode('', $row),
                $category->name,
            ),
        );
        $this->assertMatchesRegularExpression(
            '/status\?category_id=' . $category->id . '"[^>]*>\s*2\s*</',
            implode('', $row),
        );
        $this->assertSame([], $statusQueries);
    }
}
