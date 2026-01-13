<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Models\EntityLimit;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;

class SupplierLogoUploadTest extends TestCase
{
    use RefreshDatabase;
    use CreatesFrontendTestEnvironment;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpFrontendTestEnvironment();

        $permission = Permission::where('name', 'frontend.can_create_edit_supplier')
            ->where('guard_name', 'web')
            ->first();

        $this->user->givePermissionTo($permission);
        
        // Set fake storage for testing
        Storage::fake('public');
        
        // Create entity limit for suppliers
        $this->createSupplierEntityLimit();
    }

    /**
     * Create entity limit for suppliers to allow test operations
     */
    private function createSupplierEntityLimit(): void
    {
        EntityLimit::create([
            'permission_name' => 'frontend.can_create_edit_supplier',
            'entity_type' => 'supplier',
            'limit_value' => 100,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'description' => 'Supplier limit for frontend tests',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function user_can_upload_supplier_logo_when_creating_supplier(): void
    {
        $this->actingAs($this->user, 'web');

        $logo = UploadedFile::fake()->image('supplier_logo.jpg', 200, 200);
        
        $supplierData = [
            'name' => 'Test Supplier',
            'email' => 'test@supplier.com',
            'phone' => '123456789',
            'street' => 'Test Street 123',
            'city' => 'Test City',
            'zip' => '12345',
            'country' => 'Test Country',
            'supplier_logo' => $logo,
        ];

        $response = $this->post(route('frontend.supplier.store', ['locale' => 'en']), $supplierData);

        $response->assertRedirect(route('frontend.suppliers', ['locale' => 'en']));
        $response->assertSessionHas('success');

        $supplier = Supplier::where('email', 'test@supplier.com')->first();
        $this->assertNotNull($supplier);
        $this->assertNotNull($supplier->supplier_logo);
        $this->assertTrue(Storage::disk('public')->exists($supplier->supplier_logo));
    }

    #[Test]
    public function user_can_upload_supplier_logo_when_updating_supplier(): void
    {
        $this->actingAs($this->user, 'web');

        $supplier = Supplier::factory()->create(['user_id' => $this->user->id]);
        $logo = UploadedFile::fake()->image('new_logo.png', 150, 150);

        $updateData = [
            'name' => $supplier->name,
            'email' => $supplier->email,
            'phone' => $supplier->phone,
            'street' => $supplier->street,
            'city' => $supplier->city,
            'zip' => $supplier->zip,
            'country' => $supplier->country,
            'supplier_logo' => $logo,
        ];

        $response = $this->put(
            route('frontend.supplier.update', ['locale' => 'en', 'id' => $supplier->id]),
            $updateData
        );

        $response->assertRedirect(route('frontend.suppliers', ['locale' => 'en']));
        $response->assertSessionHas('success');

        $supplier->refresh();
        $this->assertNotNull($supplier->supplier_logo);
        $this->assertTrue(Storage::disk('public')->exists($supplier->supplier_logo));
    }

    #[Test]
    public function old_logo_is_deleted_when_uploading_new_logo(): void
    {
        $this->actingAs($this->user, 'web');
        
        // Create supplier with existing logo
        $oldLogo = UploadedFile::fake()->image('old_logo.jpg');
        $oldLogoPath = $oldLogo->store('suppliers/logos', 'public');
        
        $supplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'supplier_logo' => $oldLogoPath,
        ]);

        // Upload new logo
        $newLogo = UploadedFile::fake()->image('new_logo.png');
        
        $updateData = [
            'name' => $supplier->name,
            'email' => $supplier->email,
            'phone' => $supplier->phone,
            'street' => $supplier->street,
            'city' => $supplier->city,
            'zip' => $supplier->zip,
            'country' => $supplier->country,
            'supplier_logo' => $newLogo,
        ];

        $response = $this->put(
            route('frontend.supplier.update', ['locale' => 'en', 'id' => $supplier->id]),
            $updateData
        );

        $response->assertRedirect(route('frontend.suppliers', ['locale' => 'en']));
        
        $supplier->refresh();
        
        // Check old logo is deleted and new logo exists
        $this->assertFalse(Storage::disk('public')->exists($oldLogoPath));
        $this->assertTrue(Storage::disk('public')->exists($supplier->supplier_logo));
        $this->assertNotEquals($oldLogoPath, $supplier->supplier_logo);
    }

    #[Test]
    public function logo_upload_validates_file_type(): void
    {
        $this->actingAs($this->user, 'web');
        
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);
        
        $supplierData = [
            'name' => 'Test Supplier',
            'email' => 'test@supplier.com',
            'phone' => '123456789',
            'street' => 'Test Street 123',
            'city' => 'Test City',
            'zip' => '12345',
            'country' => 'Test Country',
            'supplier_logo' => $invalidFile,
        ];

        $response = $this->post(route('frontend.supplier.store', ['locale' => 'en']), $supplierData);

        $response->assertSessionHasErrors('supplier_logo');
    }

    #[Test]
    public function logo_upload_validates_file_size(): void
    {
        $this->actingAs($this->user, 'web');

        $largeLogo = UploadedFile::fake()->image('large_logo.jpg')->size(3000); // 3MB
        
        $supplierData = [
            'name' => 'Test Supplier',
            'email' => 'test@supplier.com',
            'phone' => '123456789',
            'street' => 'Test Street 123',
            'city' => 'Test City',
            'zip' => '12345',
            'country' => 'Test Country',
            'supplier_logo' => $largeLogo,
        ];

        $response = $this->post(route('frontend.supplier.store', ['locale' => 'en']), $supplierData);

        $response->assertSessionHasErrors('supplier_logo');
    }

    #[Test]
    public function supplier_can_be_created_without_logo(): void
    {
        $this->actingAs($this->user, 'web');

        $supplierData = [
            'name' => 'Test Supplier',
            'email' => 'test@supplier.com',
            'phone' => '123456789',
            'street' => 'Test Street 123',
            'city' => 'Test City',
            'zip' => '12345',
            'country' => 'Test Country',
        ];

        $response = $this->post(route('frontend.supplier.store', ['locale' => 'en']), $supplierData);

        $response->assertRedirect(route('frontend.suppliers', ['locale' => 'en']));
        $response->assertSessionHas('success');

        $supplier = Supplier::where('email', 'test@supplier.com')->first();
        $this->assertNotNull($supplier);
        $this->assertNull($supplier->supplier_logo);
    }
}
