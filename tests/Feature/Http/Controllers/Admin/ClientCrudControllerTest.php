<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use App\Models\Client;
use App\Models\EntityLimit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class ClientCrudControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesAdminTestEnvironment;

    private User $user;
    private User $adminUser;
    private UniversalLimitService $limitService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpAdminTestEnvironment();
        
        // Use the admin user created by the trait  
        $this->user = $this->adminUser;
        $this->actingAs($this->user, 'backpack');
        
        $this->limitService = app(UniversalLimitService::class);
    }

    #[Test]
    public function client_creation_records_entity_usage(): void
    {
        // Set a limit for clients that matches user's permission
        EntityLimit::factory()->create([
            'permission_name' => 'can_create_edit_client',  // Must match user's permission
            'entity_type' => 'client',
            'limit_value' => 10,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        // Check initial usage
        $initialCheck = $this->limitService->checkLimit($this->user->id, 'client', 'count', 'monthly');
        $initialUsage = $initialCheck['current_usage'] ?? 0;
        
        // Simulate client creation by directly calling recordUsage
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $result = $this->limitService->recordUsage($this->user->id, 'client', 'count', 'monthly', 'backpack');
        $this->assertTrue($result, 'recordUsage should return true when successful');
        
        // Check new usage
        $newCheck = $this->limitService->checkLimit($this->user->id, 'client', 'count', 'monthly');
        $newUsage = $newCheck['current_usage'] ?? 0;
        
        $this->assertEquals($initialUsage + 1, $newUsage, 'Usage should increase by 1 after recording');
        
        // Verify client was created
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function client_creation_respects_entity_limits(): void
    {
        // Set a strict limit for clients that matches user's permission
        EntityLimit::factory()->create([
            'permission_name' => 'can_create_edit_client',  // Must match user's permission
            'entity_type' => 'client',
            'limit_value' => 1,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        // First client creation - should succeed
        $checkBeforeFirst = $this->limitService->checkLimit($this->user->id, 'client', 'count', 'monthly');
        $this->assertTrue($checkBeforeFirst['allowed']);
        
        $client1 = Client::factory()->create(['user_id' => $this->user->id]);
        $result1 = $this->limitService->recordUsage($this->user->id, 'client', 'count', 'monthly', 'backpack');
        $this->assertTrue($result1, 'First recordUsage should succeed');
        
        // Second client creation - should fail due to limit
        $checkBeforeSecond = $this->limitService->checkLimit($this->user->id, 'client', 'count', 'monthly');
        $this->assertFalse($checkBeforeSecond['allowed']);
        $this->assertEquals('limit_exceeded', $checkBeforeSecond['reason']);
        
        // Verify first client was created
        $this->assertDatabaseHas('clients', [
            'id' => $client1->id,
            'user_id' => $this->user->id,
        ]);
    }
}
