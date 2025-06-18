<?php

namespace Tests\Feature\Repositories;

use App\Models\Supplier;
use App\Models\User;
use App\Repositories\SupplierRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupplierRepositoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private SupplierRepository $repository;
    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new SupplierRepository();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        
        // Set authenticated user for testing
        Auth::login($this->user);
    }

    #[Test]
    public function get_suppliers_for_dropdown_returns_current_user_suppliers_only(): void
    {
        // Create suppliers for current user
        $userSupplier1 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'User Supplier 1'
        ]);
        $userSupplier2 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'User Supplier 2'
        ]);
        
        // Create supplier for other user (should not be included)
        Supplier::factory()->create([
            'user_id' => $this->otherUser->id,
            'name' => 'Other User Supplier'
        ]);

        $result = $this->repository->getSuppliersForDropdown();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey($userSupplier1->id, $result);
        $this->assertArrayHasKey($userSupplier2->id, $result);
        $this->assertEquals('User Supplier 1', $result[$userSupplier1->id]);
        $this->assertEquals('User Supplier 2', $result[$userSupplier2->id]);
    }

    #[Test]
    public function get_suppliers_for_dropdown_returns_empty_array_when_no_suppliers(): void
    {
        $result = $this->repository->getSuppliersForDropdown();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function get_default_supplier_returns_default_supplier_for_current_user(): void
    {
        // Create non-default supplier
        Supplier::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false
        ]);
        
        // Create default supplier for current user
        $defaultSupplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true
        ]);
        
        // Create default supplier for other user (should not be returned)
        Supplier::factory()->create([
            'user_id' => $this->otherUser->id,
            'is_default' => true
        ]);

        $result = $this->repository->getDefaultSupplier();

        $this->assertInstanceOf(Supplier::class, $result);
        $this->assertEquals($defaultSupplier->id, $result->id);
        $this->assertEquals($this->user->id, $result->user_id);
        $this->assertTrue($result->is_default);
    }

    #[Test]
    public function get_default_supplier_returns_first_supplier_when_no_default(): void
    {
        // Create non-default suppliers (first one should be returned as fallback)
        $firstSupplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false
        ]);
        Supplier::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false
        ]);

        $result = $this->repository->getDefaultSupplier();

        $this->assertInstanceOf(Supplier::class, $result);
        $this->assertEquals($firstSupplier->id, $result->id);
        $this->assertEquals($this->user->id, $result->user_id);
        $this->assertFalse($result->is_default);
    }

    #[Test]
    public function get_default_supplier_returns_null_when_no_suppliers(): void
    {
        // Don't create any suppliers
        $result = $this->repository->getDefaultSupplier();

        $this->assertNull($result);
    }

    #[Test]
    public function find_by_id_returns_supplier_when_belongs_to_current_user(): void
    {
        $supplier = Supplier::factory()->create([
            'user_id' => $this->user->id
        ]);

        $result = $this->repository->findById($supplier->id);

        $this->assertInstanceOf(Supplier::class, $result);
        $this->assertEquals($supplier->id, $result->id);
        $this->assertEquals($this->user->id, $result->user_id);
    }

    #[Test]
    public function find_by_id_returns_null_when_supplier_belongs_to_other_user(): void
    {
        $otherUserSupplier = Supplier::factory()->create([
            'user_id' => $this->otherUser->id
        ]);

        $result = $this->repository->findById($otherUserSupplier->id);

        $this->assertNull($result);
    }

    #[Test]
    public function find_by_id_returns_null_when_supplier_does_not_exist(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    #[Test]
    public function create_saves_supplier_with_current_user_id_when_not_provided(): void
    {
        $data = [
            'name' => 'Test Supplier',
            'email' => 'test@example.com',
            'phone' => '+420123456789',
            'street' => 'Test Street 123',
            'city' => 'Test City',
            'zip' => '12345',
            'country' => 'CZ',
            'is_default' => false
        ];

        $result = $this->repository->create($data);

        $this->assertInstanceOf(Supplier::class, $result);
        $this->assertEquals($this->user->id, $result->user_id);
        $this->assertEquals($data['name'], $result->name);
        $this->assertEquals($data['email'], $result->email);
        $this->assertEquals($data['phone'], $result->phone);
        $this->assertEquals($data['street'], $result->street);
        $this->assertEquals($data['is_default'], $result->is_default);
        
        // Verify it was saved to database
        $this->assertDatabaseHas('suppliers', [
            'id' => $result->id,
            'user_id' => $this->user->id,
            'name' => $data['name'],
            'email' => $data['email']
        ]);
    }

    #[Test]
    public function create_preserves_user_id_when_provided_in_data(): void
    {
        $data = [
            'name' => 'Test Supplier',
            'email' => 'test@example.com',
            'street' => 'Test Street 123',
            'city' => 'Test City',
            'zip' => '12345',
            'country' => 'CZ',
            'user_id' => $this->otherUser->id, // This should be preserved
            'is_default' => false
        ];

        $result = $this->repository->create($data);

        $this->assertInstanceOf(Supplier::class, $result);
        $this->assertEquals($this->otherUser->id, $result->user_id);
        $this->assertNotEquals($this->user->id, $result->user_id);
    }

    #[Test]
    public function create_uses_current_user_id_when_user_id_is_null_in_data(): void
    {
        $data = [
            'name' => 'Test Supplier',
            'email' => 'test@example.com',
            'street' => 'Test Street 123',
            'city' => 'Test City',
            'zip' => '12345',
            'country' => 'CZ',
            'user_id' => null, // This should trigger fallback to Auth::id()
            'is_default' => false
        ];

        $result = $this->repository->create($data);

        $this->assertInstanceOf(Supplier::class, $result);
        $this->assertEquals($this->user->id, $result->user_id);
    }

    #[Test]
    public function repository_methods_work_with_different_authenticated_users(): void
    {
        // Create supplier for first user
        $supplier1 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'User 1 Supplier'
        ]);

        // Switch to other user
        Auth::login($this->otherUser);

        // Create supplier for second user
        $supplier2 = Supplier::factory()->create([
            'user_id' => $this->otherUser->id,
            'name' => 'User 2 Supplier'
        ]);

        // Test dropdown only shows current user's suppliers
        $dropdown = $this->repository->getSuppliersForDropdown();
        $this->assertCount(1, $dropdown);
        $this->assertArrayHasKey($supplier2->id, $dropdown);
        $this->assertArrayNotHasKey($supplier1->id, $dropdown);

        // Test findById only finds current user's suppliers
        $foundSupplier = $this->repository->findById($supplier2->id);
        $this->assertNotNull($foundSupplier);
        $this->assertEquals($supplier2->id, $foundSupplier->id);

        $notFoundSupplier = $this->repository->findById($supplier1->id);
        $this->assertNull($notFoundSupplier);
    }

    #[Test]
    public function get_default_supplier_fallback_behavior_works_correctly(): void
    {
        // Test with multiple suppliers but no default
        $supplier1 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
            'created_at' => now()->subHours(2)
        ]);
        
        $supplier2 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
            'created_at' => now()->subHours(1)
        ]);

        // Should return the first supplier found (likely the first created)
        $result = $this->repository->getDefaultSupplier();
        
        $this->assertInstanceOf(Supplier::class, $result);
        $this->assertEquals($this->user->id, $result->user_id);
        $this->assertFalse($result->is_default);
        // The exact supplier returned depends on database ordering, 
        // but it should be one of the user's suppliers
        $this->assertContains($result->id, [$supplier1->id, $supplier2->id]);
    }
}
