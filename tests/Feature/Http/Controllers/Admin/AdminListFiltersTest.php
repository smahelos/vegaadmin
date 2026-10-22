<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

/**
 * Contract of the select2 and simple list filters: the template renders the filter and its
 * value travels as the GET parameter named after the filter.
 */
class AdminListFiltersTest extends TestCase
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
    public function select2_filter_renders_its_values_and_marks_the_active_one(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $owner = User::factory()->create(['name' => 'Owner ' . uniqid()]);

        $response = $this->get('/admin/client?user_id=' . $owner->id);

        $response->assertOk();
        $response->assertSee('data-select2-filter="user_id"', false);
        // the filters navbar only wires a filter that names its init function
        $response->assertSee('filter-init-function="billexInitSelect2Filter"', false);
        $response->assertSee('data-placeholder="' . __('admin.general.select') . '"', false);
        $response->assertSee('<option value="' . $owner->id . '" selected>' . $owner->name . '</option>', false);
    }

    #[Test]
    public function select2_filter_value_limits_the_search(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $owner = User::factory()->create();
        Client::factory()->create(['user_id' => $owner->id]);
        Client::factory()->create();

        $this->postJson('/admin/client/search?user_id=' . $owner->id, ['draw' => 1, 'start' => 0, 'length' => 10])
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);
    }

    #[Test]
    public function simple_filter_renders_as_a_toggle(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $response = $this->get('/admin/product?is_active=1');

        $response->assertOk();
        $response->assertSee('data-simple-filter="is_active"', false);
        $response->assertSee('filter-init-function="billexInitSimpleFilter"', false);
        $this->assertMatchesRegularExpression('/filter-name="is_active"\s+filter-type="simple"\s+class="nav-item active"/', $response->getContent());
    }

    #[Test]
    public function simple_filter_limits_the_search(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);

        $this->postJson('/admin/product/search?is_active=1', ['draw' => 1, 'start' => 0, 'length' => 10])
            ->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 1);
    }
}
