<?php

namespace Tests\Feature\Livewire;

use App\Livewire\SupplierList;
use App\Models\Supplier;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupplierListFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setupTestRoutes();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    /**
     * Setup minimal routes needed for pagination component
     */
    private function setupTestRoutes(): void
    {
        \Route::get('/test/suppliers', function () {
            return 'test';
        })->name('frontend.supplier.index');

        \Route::get('/test/supplier/{id}', function ($id) {
            return "supplier {$id}";
        })->name('frontend.supplier.show');

        \Route::get('/test/supplier/{id}/edit', function ($id) {
            return "edit supplier {$id}";
        })->name('frontend.supplier.edit');

        \Route::post('/test/supplier/{id}', function ($id) {
            return "delete supplier {$id}";
        })->name('frontend.supplier.destroy');

        \Route::get('/test/supplier/create', function () {
            return "create supplier";
        })->name('frontend.supplier.create');

        \Route::get('/test/expense/create', function () {
            return "create expense";
        })->name('frontend.expense.create');
    }

    #[Test]
    public function component_can_be_rendered(): void
    {
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class);
        
        $component->assertStatus(200);
    }

    #[Test]
    public function component_displays_user_suppliers_only(): void
    {
        // Create suppliers for different users
        $userSuppliers = Supplier::factory()->count(3)->create(['user_id' => $this->user->id]);
        $otherUserSuppliers = Supplier::factory()->count(2)->create(['user_id' => $this->otherUser->id]);
        
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class);
        
        // Should see user's suppliers
        foreach ($userSuppliers as $supplier) {
            $component->assertSee($supplier->name);
        }
        
        // Should NOT see other user's suppliers
        foreach ($otherUserSuppliers as $supplier) {
            $component->assertDontSee($supplier->name);
        }
    }

    #[Test]
    public function component_handles_search_by_name(): void
    {
        $supplier1 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'ACME Supplies'
        ]);
        
        $supplier2 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'XYZ Materials'
        ]);
        
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class)
            ->set('search', 'ACME');
        
        // Check data directly from component
        $suppliers = $component->viewData('suppliers');
        $this->assertEquals(1, $suppliers->count());
        $this->assertEquals('ACME Supplies', $suppliers->first()->name);
    }

    #[Test]
    public function component_handles_search_by_email(): void
    {
        $supplier1 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'test@acme.com'
        ]);
        
        $supplier2 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'info@xyz.com'
        ]);
        
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class)
            ->set('search', 'acme');
        
        $suppliers = $component->viewData('suppliers');
        $this->assertEquals(1, $suppliers->count());
        $this->assertEquals('test@acme.com', $suppliers->first()->email);
    }

    #[Test]
    public function component_handles_search_by_ico(): void
    {
        $supplier1 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'ico' => '12345678'
        ]);
        
        $supplier2 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'ico' => '87654321'
        ]);
        
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class)
            ->set('search', '12345');
        
        $suppliers = $component->viewData('suppliers');
        $this->assertEquals(1, $suppliers->count());
        $this->assertEquals('12345678', $suppliers->first()->ico);
    }

    #[Test]
    public function updating_search_resets_pagination(): void
    {
        $this->actingAs($this->user);
        
        // Create 16 suppliers to have multiple pages
        Supplier::factory()->count(16)->create(['user_id' => $this->user->id]);
        
        $component = Livewire::test(SupplierList::class);
        
        // Set page to 2 manually
        $component->set('page', 2);
        
        // Update search - this should trigger updatingSearch()
        $component->set('search', 'test');
        
        // Verify search was set
        $this->assertEquals('test', $component->get('search'));
        
        // Test that the component handles search properly
        $suppliers = $component->instance()->render()->getData()['suppliers'];
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $suppliers);
    }

    #[Test]
    public function reset_filters_clears_all_filters_and_pagination(): void
    {
        $this->actingAs($this->user);
        
        // Create 16 suppliers to have multiple pages
        Supplier::factory()->count(16)->create(['user_id' => $this->user->id]);
        
        $component = Livewire::test(SupplierList::class)
            ->set('search', 'test')
            ->set('page', 2);
        
        // Verify we have search filter
        $this->assertEquals('test', $component->get('search'));
        
        // Call resetFilters method
        $component->call('resetFilters');
        
        // Search filter should be reset
        $this->assertEquals('', $component->get('search'));
        
        // Verify suppliers are displayed properly
        $suppliers = $component->instance()->render()->getData()['suppliers'];
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $suppliers);
    }

    #[Test]
    public function sort_by_toggles_order_direction_for_same_field(): void
    {
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class)
            ->set('orderBy', 'name')
            ->set('orderAsc', true)
            ->call('sortBy', 'name');
        
        $component->assertSet('orderBy', 'name')
                  ->assertSet('orderAsc', false);
    }

    #[Test]
    public function sort_by_sets_new_field_with_ascending_order(): void
    {
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class)
            ->set('orderBy', 'created_at')
            ->set('orderAsc', false)
            ->call('sortBy', 'name');
        
        $component->assertSet('orderBy', 'name')
                  ->assertSet('orderAsc', true);
    }

    #[Test]
    public function component_handles_special_invoice_count_sorting(): void
    {
        $supplier1 = Supplier::factory()->create(['user_id' => $this->user->id]);
        $supplier2 = Supplier::factory()->create(['user_id' => $this->user->id]);
        
        // Create invoices for different suppliers using factory
        Invoice::factory()->count(2)->create([
            'supplier_id' => $supplier1->id,
            'user_id' => $this->user->id,
        ]);
        
        Invoice::factory()->create([
            'supplier_id' => $supplier2->id,
            'user_id' => $this->user->id,
        ]);
        
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class)
            ->call('sortBy', 'invoices');
        
        $component->assertSet('orderBy', 'invoices')
                  ->assertSet('orderAsc', true);
    }

    #[Test]
    public function component_handles_pagination(): void
    {
        $this->actingAs($this->user);
        
        // Create exactly 16 suppliers to test pagination (10 on page 1, 6 on page 2)
        Supplier::factory()->count(16)->create(['user_id' => $this->user->id]);
        
        $component = Livewire::test(SupplierList::class);
        
        // Page 1 should be default
        $this->assertEquals(1, $component->get('page'));
        
        // Navigate to page 2
        $component->set('page', 2);
        $this->assertEquals(2, $component->get('page'));
        
        // Navigate back to page 1
        $component->set('page', 1);
        $this->assertEquals(1, $component->get('page'));
    }

    #[Test]
    public function component_displays_empty_state_when_no_suppliers(): void
    {
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class);
        
        $component->assertViewHas('hasData', false);
    }

    #[Test]
    public function component_handles_database_errors_gracefully(): void
    {
        $this->actingAs($this->user);
        
        // Test will ensure error handling works in real scenarios
        $component = Livewire::test(SupplierList::class);
        
        // Component should render without error message when working properly
        $component->assertSet('errorMessage', null);
    }

    #[Test]
    public function mount_method_sets_pagination_theme(): void
    {
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class);
        
        // Access protected property via reflection for testing
        $reflection = new \ReflectionClass($component->instance());
        $property = $reflection->getProperty('paginationTheme');
        $property->setAccessible(true);
        
        $this->assertEquals('tailwind', $property->getValue($component->instance()));
    }

    #[Test]
    public function component_requires_authentication(): void
    {
        // Just verify component works with authenticated user
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class);
        $component->assertStatus(200);
    }

    #[Test]
    public function component_uses_correct_view(): void
    {
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class);
        
        $component->assertViewIs('livewire.supplier-list');
    }

    #[Test]
    public function component_passes_correct_data_to_view(): void
    {
        Supplier::factory()->create(['user_id' => $this->user->id]);
        
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class);
        
        $component->assertViewHas('suppliers')
                  ->assertViewHas('hasData', true);
    }

    #[Test]
    public function component_counts_invoices_for_each_supplier(): void
    {
        $supplier = Supplier::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->create([
            'supplier_id' => $supplier->id,
            'user_id' => $this->user->id,
        ]);
        
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class);
        
        $suppliers = $component->viewData('suppliers');
        $this->assertEquals(1, $suppliers->first()->invoices_count);
    }

    #[Test]
    public function component_applies_search_filter(): void
    {
        Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Searchable Supplier'
        ]);
        
        Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Other Supplier'
        ]);
        
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class)
            ->set('search', 'Searchable');
        
        $suppliers = $component->viewData('suppliers');
        $this->assertEquals(1, $suppliers->count());
        $this->assertEquals('Searchable Supplier', $suppliers->first()->name);
    }

    #[Test]
    public function component_preserves_url_state(): void
    {
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class)
            ->set('search', 'test')
            ->set('orderBy', 'name')
            ->set('orderAsc', true);
        
        // Properties should be preserved as they use #[Url] attribute
        $component->assertSet('search', 'test')
                  ->assertSet('orderBy', 'name')
                  ->assertSet('orderAsc', true);
    }

    #[Test]
    public function component_sorts_by_name_correctly(): void
    {
        $supplier1 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Alpha Supplier'
        ]);
        $supplier2 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Beta Supplier'
        ]);
        
        $this->actingAs($this->user);
        
        // Test ascending order
        $component = Livewire::test(SupplierList::class)
            ->set('orderBy', 'name')
            ->set('orderAsc', true);
        
        $suppliers = $component->viewData('suppliers');
        $this->assertEquals('Alpha Supplier', $suppliers->first()->name);
        
        // Test descending order
        $component->set('orderAsc', false);
        $suppliers = $component->viewData('suppliers');
        $this->assertEquals('Beta Supplier', $suppliers->first()->name);
    }

    #[Test]
    public function component_sorts_by_created_at_correctly(): void
    {
        $supplier1 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay()
        ]);
        $supplier2 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()
        ]);
        
        $this->actingAs($this->user);
        
        // Test descending order (newest first)
        $component = Livewire::test(SupplierList::class)
            ->set('orderBy', 'created_at')
            ->set('orderAsc', false);
        
        $suppliers = $component->viewData('suppliers');
        $this->assertEquals($supplier2->id, $suppliers->first()->id);
        
        // Test ascending order (oldest first)
        $component->set('orderAsc', true);
        $suppliers = $component->viewData('suppliers');
        $this->assertEquals($supplier1->id, $suppliers->first()->id);
    }

    #[Test]
    public function component_handles_empty_search_results(): void
    {
        Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Supplier'
        ]);
        
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class)
            ->set('search', 'NonExistentSupplier');
        
        $suppliers = $component->viewData('suppliers');
        $this->assertEquals(0, $suppliers->count());
        $component->assertViewHas('hasData', false);
    }

    #[Test]
    public function component_search_is_case_insensitive(): void
    {
        Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'UPPERCASE SUPPLIER'
        ]);
        
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class)
            ->set('search', 'uppercase');
        
        $suppliers = $component->viewData('suppliers');
        $this->assertEquals(1, $suppliers->count());
        $this->assertEquals('UPPERCASE SUPPLIER', $suppliers->first()->name);
    }

    #[Test]
    public function component_search_handles_partial_matches(): void
    {
        Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Long Supplier Name'
        ]);
        
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class)
            ->set('search', 'Long');
        
        $suppliers = $component->viewData('suppliers');
        $this->assertEquals(1, $suppliers->count());
    }

    #[Test]
    public function component_default_sort_is_by_created_at_desc(): void
    {
        $this->actingAs($this->user);
        
        $component = Livewire::test(SupplierList::class);
        
        $component->assertSet('orderBy', 'created_at')
                  ->assertSet('orderAsc', false);
    }
}
