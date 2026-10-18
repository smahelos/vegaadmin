<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Status;
use App\Models\StatusCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class StatusCategoryCrudControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesAdminTestEnvironment;

    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAdminTestEnvironment();
    }

    #[Test]
    public function show_lists_statuses_of_the_category(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $category = StatusCategory::factory()->create();
        $status = Status::factory()->create(['category_id' => $category->id, 'name' => 'Status ' . uniqid()]);
        $otherStatus = Status::factory()->create(['name' => 'Other ' . uniqid()]);

        $response = $this->get("/admin/status-category/{$category->id}/show");

        $response->assertOk();
        $response->assertSee(__('admin.status_categories.statuses_tab'));
        $response->assertSeeInOrder(['<th>' . __('admin.statuses.slug') . '</th>', $status->name, $status->slug], false);
        $response->assertDontSee($otherStatus->name);
        $response->assertSeeInOrder([__('admin.status_categories.statuses_count'), '<span>', '1'], false);
        $response->assertSee(__('admin.general.created_at'));
    }
}
