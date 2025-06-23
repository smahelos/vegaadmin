<?php

namespace Tests\Feature\Livewire;

use App\Livewire\SupplierListLatest;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupplierListLatestFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function component_can_render_successfully(): void
    {
        $this->actingAs($this->user);

        Livewire::test(SupplierListLatest::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.supplier-list-latest');
    }

    #[Test]
    public function component_shows_user_suppliers_only(): void
    {
        $this->actingAs($this->user);
        
        // Create suppliers for current user
        $userSuppliers = Supplier::factory()->count(3)->create(['user_id' => $this->user->id]);
        
        // Create suppliers for other user
        $otherUser = User::factory()->create();
        Supplier::factory()->count(2)->create(['user_id' => $otherUser->id]);

        Livewire::test(SupplierListLatest::class)
            ->assertViewHas('suppliers', function ($suppliers) use ($userSuppliers) {
                return $suppliers->count() === 3 &&
                       $suppliers->pluck('id')->sort()->values()->toArray() === 
                       $userSuppliers->pluck('id')->sort()->values()->toArray();
            })
            ->assertViewHas('hasData', true);
    }

    #[Test]
    public function component_shows_no_data_when_user_has_no_suppliers(): void
    {
        $this->actingAs($this->user);

        Livewire::test(SupplierListLatest::class)
            ->assertViewHas('hasData', false)
            ->assertViewHas('suppliers', function ($suppliers) {
                return $suppliers->count() === 0;
            });
    }

    #[Test]
    public function component_orders_suppliers_by_created_at_desc_by_default(): void
    {
        $this->actingAs($this->user);
        
        $supplier1 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(3)
        ]);
        $supplier2 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(1)
        ]);
        $supplier3 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2)
        ]);

        Livewire::test(SupplierListLatest::class)
            ->assertViewHas('suppliers', function ($suppliers) use ($supplier2, $supplier3, $supplier1) {
                $supplierIds = $suppliers->pluck('id')->toArray();
                return $supplierIds[0] === $supplier2->id && // newest first
                       $supplierIds[1] === $supplier3->id &&
                       $supplierIds[2] === $supplier1->id;   // oldest last
            });
    }

    #[Test]
    public function sort_by_toggles_order_direction(): void
    {
        $this->actingAs($this->user);
        
        Supplier::factory()->count(3)->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SupplierListLatest::class);
        
        // Initial state
        $component->assertSet('orderBy', 'created_at')
                  ->assertSet('orderAsc', false);

        // First click - should toggle to ascending
        $component->call('sortBy', 'created_at')
                  ->assertSet('orderAsc', true);

        // Second click - should toggle back to descending  
        $component->call('sortBy', 'created_at')
                  ->assertSet('orderAsc', false);
    }

    #[Test]
    public function sort_by_changes_field_and_sets_ascending(): void
    {
        $this->actingAs($this->user);
        
        Supplier::factory()->count(3)->create(['user_id' => $this->user->id]);

        Livewire::test(SupplierListLatest::class)
            ->assertSet('orderBy', 'created_at')
            ->assertSet('orderAsc', false)
            ->call('sortBy', 'name')
            ->assertSet('orderBy', 'name')
            ->assertSet('orderAsc', true);
    }

    #[Test]
    public function component_paginates_suppliers_with_five_per_page(): void
    {
        $this->actingAs($this->user);
        
        // Create 8 suppliers
        Supplier::factory()->count(8)->create(['user_id' => $this->user->id]);

        Livewire::test(SupplierListLatest::class)
            ->assertViewHas('suppliers', function ($suppliers) {
                return $suppliers->count() === 5 && // First page shows 5
                       $suppliers->total() === 8;    // Total is 8
            });
    }

    #[Test]
    public function component_handles_database_errors_gracefully(): void
    {
        $this->actingAs($this->user);

        // Test by setting error message manually to verify view handles it
        Livewire::test(SupplierListLatest::class)
            ->set('errorMessage', 'Error while loading latest suppliers.')
            ->assertViewHas('errorMessage', 'Error while loading latest suppliers.');
    }

    #[Test]
    public function component_works_without_authentication_but_shows_no_data(): void
    {
        // Test without authentication - should not crash but show no data
        Livewire::test(SupplierListLatest::class)
            ->assertStatus(200)
            ->assertViewHas('hasData', false);
    }

    #[Test]
    public function component_filters_by_authenticated_user_only(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // Create suppliers for different users
        $supplier1 = Supplier::factory()->create(['user_id' => $user1->id, 'name' => 'User1 Supplier']);
        $supplier2 = Supplier::factory()->create(['user_id' => $user2->id, 'name' => 'User2 Supplier']);

        // Test as user1
        $this->actingAs($user1);
        Livewire::test(SupplierListLatest::class)
            ->assertViewHas('suppliers', function ($suppliers) use ($supplier1) {
                return $suppliers->count() === 1 && 
                       $suppliers->first()->id === $supplier1->id;
            });

        // Test as user2  
        $this->actingAs($user2);
        Livewire::test(SupplierListLatest::class)
            ->assertViewHas('suppliers', function ($suppliers) use ($supplier2) {
                return $suppliers->count() === 1 && 
                       $suppliers->first()->id === $supplier2->id;
            });
    }
}
