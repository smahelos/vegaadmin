<?php

namespace Tests\Feature\Domain\Party\Events;

use App\Domain\Party\Services\InvoicePartyService;
use App\Domain\User\ValueObjects\UserId;
use App\Models\User;
use App\Models\EntityLimitUsage;
use App\Models\EntityLimit;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithData;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration test for Domain Events triggered by actual Party service calls
 */
class PartyServiceDomainEventsTest extends TestCase
{
    use RefreshDatabaseWithData;

    private InvoicePartyService $partyService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->partyService = app(InvoicePartyService::class);
        $this->user = User::factory()->create();
        
        // Create and give user permissions for client creation (needed for UELS to record usage)
        Permission::firstOrCreate(['name' => 'can_create_edit_client', 'guard_name' => 'web']);
        $this->user->givePermissionTo('can_create_edit_client');
        
        // Create EntityLimit for client permission so UELS can record usage
        EntityLimit::firstOrCreate([
            'permission_name' => 'can_create_edit_client',
            'entity_type' => 'client',
            'period_type' => 'daily',
        ], [
            'limit_value' => 100, // High enough for testing
            'metric_type' => 'count',
        ]);

        // Create EntityLimit for supplier permission as well
        Permission::firstOrCreate(['name' => 'can_create_edit_supplier', 'guard_name' => 'web']);
        $this->user->givePermissionTo('can_create_edit_supplier');
        EntityLimit::firstOrCreate([
            'permission_name' => 'can_create_edit_supplier',
            'entity_type' => 'supplier',
            'period_type' => 'daily',
        ], [
            'limit_value' => 100, // High enough for testing
            'metric_type' => 'count',
        ]);
    }

    #[Test]
    public function creating_client_via_service_dispatches_domain_events_and_records_usage(): void
    {
        // Get initial usage count
        $initialUsage = EntityLimitUsage::where([
            'user_id' => $this->user->id,
            'entity_type' => 'client',
            'metric_type' => 'count'
        ])->sum('current_value');
        
        // Create client via service (this should dispatch Domain Events)
        $result = $this->partyService->resolveOrCreateClientWithFlag(UserId::fromInt($this->user->id), [
            'name' => 'Test Client ' . uniqid(),
            'email' => 'client_' . uniqid() . '@example.com',
        ]);
        
        $this->assertTrue($result['created'], 'Client should be created, not resolved');
        $this->assertNotNull($result['client'], 'Client should be returned');
        
        // Check that usage was recorded (Domain Events → UsageRecordingHandler)
        $newUsage = EntityLimitUsage::where([
            'user_id' => $this->user->id,
            'entity_type' => 'client', 
            'metric_type' => 'count'
        ])->sum('current_value');
        
        $this->assertEquals(
            $initialUsage + 1, 
            $newUsage, 
            'Client usage should increase by 1 after Domain Events processing'
        );
    }

    #[Test] 
    public function creating_supplier_via_service_dispatches_domain_events_and_records_usage(): void
    {
        // Get initial usage count
        $initialUsage = EntityLimitUsage::where([
            'user_id' => $this->user->id,
            'entity_type' => 'supplier',
            'metric_type' => 'count'
        ])->sum('current_value');
        
        // Create supplier via service (this should dispatch Domain Events)
        $result = $this->partyService->resolveOrCreateSupplierWithFlag(UserId::fromInt($this->user->id), [
            'name' => 'Test Supplier ' . uniqid(),
            'email' => 'supplier_' . uniqid() . '@example.com',
        ]);
        
        $this->assertTrue($result['created'], 'Supplier should be created, not resolved');
        $this->assertNotNull($result['supplier'], 'Supplier should be returned');
        
        // Check that usage was recorded (Domain Events → UsageRecordingHandler)
        $newUsage = EntityLimitUsage::where([
            'user_id' => $this->user->id,
            'entity_type' => 'supplier',
            'metric_type' => 'count'
        ])->sum('current_value');
        
        $this->assertEquals(
            $initialUsage + 1,
            $newUsage,
            'Supplier usage should increase by 1 after Domain Events processing'
        );
    }
}
