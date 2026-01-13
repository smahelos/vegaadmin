<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Http\Requests\Admin\SupplierRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Models\EntityLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class AdminSupplierLogoValidationTest extends TestCase
{
    use RefreshDatabase, CreatesAdminTestEnvironment;

    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up admin test environment with proper permissions
        $this->setUpAdminTestEnvironment();
        
        Storage::fake('public');
        
        // Create test routes for admin supplier operations
        Route::post('/admin/supplier-test', function (SupplierRequest $request) {
            try {
                // Check backpack authentication and permissions
                if (!backpack_auth()->check()) {
                    return response()->json(['error' => 'Unauthenticated'], 401);
                }
                
                if (!backpack_auth()->user()->can('can_create_edit_supplier')) {
                    return response()->json(['error' => 'Unauthorized'], 403);
                }
                
                $validatedData = $request->validated();
                
                // Debug
                logger('Validated data for supplier:', $validatedData);
                
                // Handle file upload if provided
                if ($request->hasFile('supplier_logo')) {
                    $file = $request->file('supplier_logo');
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('suppliers/logos', $filename, 'public');
                    $validatedData['supplier_logo'] = $path;
                }
                
                $supplier = Supplier::create($validatedData);
                logger('Created supplier:', ['supplier' => $supplier, 'exists' => $supplier->exists]);
                
                // Only redirect on successful creation
                if ($supplier && $supplier->exists) {
                    return response()->json(['success' => true, 'supplier' => $supplier]);
                } else {
                    return response()->json(['error' => 'Failed to create supplier'], 500);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        })->middleware('web');
    }

    #[Test] 
    public function admin_can_create_supplier_with_logo_validation(): void
    {
        $logo = UploadedFile::fake()->image('admin_supplier_logo.jpg', 200, 200);
        $testUser = User::factory()->create(['id' => intval(uniqid())]);
        
        // Create entity limit for suppliers
        EntityLimit::factory()->create([
            'entity_type' => 'supplier',
            'limit_value' => 10,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'permission_name' => 'can_create_edit_supplier',
            'is_active' => true,
        ]);

        $supplierData = [
            'name' => 'Admin Test Supplier',
            'email' => 'admin-test@supplier.com',
            'phone' => '987654321',
            'street' => 'Admin Test Street 456',
            'city' => 'Admin Test City',
            'zip' => '54321',
            'country' => 'Admin Test Country',
            'user_id' => $testUser->id,
            'supplier_logo' => $logo,
        ];

        $this->actingAs($this->adminUser, 'backpack');

        $response = $this->postJson('/admin/supplier-test', $supplierData);

        // Expect JSON response with success
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $supplier = Supplier::where('email', 'admin-test@supplier.com')->first();
        $this->assertNotNull($supplier);
        $this->assertNotNull($supplier->supplier_logo);
        $this->assertTrue(Storage::disk('public')->exists($supplier->supplier_logo));
    }

    #[Test]
    public function admin_logo_upload_validates_file_type(): void
    {
        $invalidFile = UploadedFile::fake()->create('admin_document.pdf', 1000);
        $testUser = User::factory()->create();
        
        // Create entity limit for suppliers
        EntityLimit::factory()->create([
            'entity_type' => 'supplier',
            'limit_value' => 10,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'permission_name' => 'can_create_edit_supplier',
            'is_active' => true,
        ]);
        
        $supplierData = [
            'name' => 'Admin Invalid File Test',
            'email' => 'admin-invalid@supplier.com',
            'phone' => '111222333',
            'street' => 'Invalid Street 789',
            'city' => 'Invalid City',
            'zip' => '99999',
            'country' => 'Invalid Country',
            'user_id' => $testUser->id,
            'supplier_logo' => $invalidFile,
        ];

        $this->actingAs($this->adminUser, 'backpack');
        
        $response = $this->postJson('/admin/supplier-test', $supplierData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('supplier_logo');
    }

    #[Test]
    public function admin_logo_upload_validates_file_size(): void
    {
        $largeLogo = UploadedFile::fake()->image('admin_large_logo.jpg')->size(3000); // 3MB
        $testUser = User::factory()->create();
        
        // Create entity limit for suppliers
        EntityLimit::factory()->create([
            'entity_type' => 'supplier',
            'limit_value' => 10,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'permission_name' => 'can_create_edit_supplier',
            'is_active' => true,
        ]);
        
        $supplierData = [
            'name' => 'Admin Large File Test',
            'email' => 'admin-large@supplier.com',
            'phone' => '444555666',
            'street' => 'Large Street 321',
            'city' => 'Large City',
            'zip' => '11111',
            'country' => 'Large Country',
            'user_id' => $testUser->id,
            'supplier_logo' => $largeLogo,
        ];

        $this->actingAs($this->adminUser, 'backpack');
        
        $response = $this->postJson('/admin/supplier-test', $supplierData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('supplier_logo');
    }

    #[Test]
    public function unauthorized_admin_cannot_create_supplier_with_logo(): void
    {
        $unauthorizedUser = User::factory()->create();
        $logo = UploadedFile::fake()->image('unauthorized_logo.jpg');
        $testUser = User::factory()->create();
        
        // Create entity limit for suppliers
        EntityLimit::factory()->create([
            'entity_type' => 'supplier',
            'limit_value' => 10,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'permission_name' => 'can_create_edit_supplier',
            'is_active' => true,
        ]);
        
        $supplierData = [
            'name' => 'Unauthorized Test',
            'email' => 'unauthorized@supplier.com',
            'phone' => '777888999',
            'street' => 'Unauthorized Street',
            'city' => 'Unauthorized City',
            'zip' => '22222',
            'country' => 'Unauthorized Country',
            'user_id' => $testUser->id,
            'supplier_logo' => $logo,
        ];

        $this->actingAs($unauthorizedUser, 'backpack');
        
        $response = $this->postJson('/admin/supplier-test', $supplierData);

        $response->assertStatus(403);
    }
}
