<?php

namespace Tests\Feature\Domain\User\Services;

use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use App\Models\EntityLimit;
use App\Models\EntityLimitUsage;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithData;

/**
 * Additional behavior-focused tests for UniversalLimitService.
 * Covers best period resolution, implicit period selection in recordUsage,
 * and aggregated entity limit info including usage accumulation.
 */
class UniversalLimitServiceBestPeriodFeatureTest extends TestCase
{
    use RefreshDatabaseWithData;

    private UniversalLimitService $service;
    private User $user;

    protected bool $seedDatabase = false; // We'll create only what we need

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(UniversalLimitService::class);

        $this->user = User::create([
            'name' => 'Limit User '.uniqid(),
            'email' => 'limit_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create layered permissions with progressively higher limits
        $permissions = [
            'frontend.can_create_edit_invoice_basic' => 5,      // daily
            'frontend.can_create_edit_invoice_plus' => 20,     // monthly
            'frontend.can_create_edit_invoice_pro' => 100,     // yearly
            'frontend.can_create_edit_invoice_ultra' => 1000,  // lifetime
        ];

        foreach ($permissions as $perm => $limit) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $this->user->givePermissionTo($perm);
        }

        // Create limits for each permission across different periods
        EntityLimit::factory()->create([
            'permission_name' => 'frontend.can_create_edit_invoice_basic',
            'entity_type' => 'invoice',
            'metric_type' => 'count',
            'period_type' => 'daily',
            'limit_value' => 5,
            'is_active' => true,
        ]);
        EntityLimit::factory()->create([
            'permission_name' => 'frontend.can_create_edit_invoice_plus',
            'entity_type' => 'invoice',
            'metric_type' => 'count',
            'period_type' => 'monthly',
            'limit_value' => 20,
            'is_active' => true,
        ]);
        EntityLimit::factory()->create([
            'permission_name' => 'frontend.can_create_edit_invoice_pro',
            'entity_type' => 'invoice',
            'metric_type' => 'count',
            'period_type' => 'yearly',
            'limit_value' => 100,
            'is_active' => true,
        ]);
        EntityLimit::factory()->create([
            'permission_name' => 'frontend.can_create_edit_invoice_ultra',
            'entity_type' => 'invoice',
            'metric_type' => 'count',
            'period_type' => 'lifetime',
            'limit_value' => 1000,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function best_period_type_selects_highest_limit_and_convenience_method_uses_it(): void
    {
        $best = $this->service->getBestPeriodType($this->user->id, 'invoice', 'count');
        $this->assertEquals('lifetime', $best, 'Expected lifetime period chosen due to highest limit');

        // Use generic checkLimit with best period instead of convenience method (removed in refactor)
        $result = $this->service->checkLimit($this->user->id, 'invoice', 'count', $best);
        $this->assertEquals(1000, $result['limit']);
        $this->assertEquals('lifetime', $result['period_type']);
        $this->assertTrue($result['allowed']);
    }

    #[Test]
    public function record_usage_without_period_uses_best_period(): void
    {
        // Call recordUsage with null periodType to trigger automatic best period determination
        $this->assertTrue($this->service->recordUsage($this->user->id, 'invoice', 'count', null, 'web', 3));

        // Usage should be recorded under lifetime period (best period)
        $usage = EntityLimitUsage::where('user_id', $this->user->id)
            ->where('entity_type', 'invoice')
            ->where('metric_type', 'count')
            ->where('period_type', 'lifetime')
            ->first();

        $this->assertNotNull($usage, 'Usage record for lifetime period should exist');
        $this->assertEquals(3, (int)$usage->current_value);
    }

    #[Test]
    public function get_entity_limit_info_aggregates_limits_and_usage(): void
    {
        // Record some usage across two periods explicitly
        $this->service->recordUsage($this->user->id, 'invoice', 'count', 'daily', 'web', 2);
        $this->service->recordUsage($this->user->id, 'invoice', 'count', 'monthly', 'web', 4);

    $info = $this->service->getEntityLimitInfo($this->user->id, 'invoice');

    $this->assertEquals('invoice', $info['entity_type']);
    $this->assertTrue($info['can_create']);
        $this->assertArrayHasKey('count', $info['limits']);
        $this->assertArrayHasKey('daily', $info['limits']['count']);
        $this->assertArrayHasKey('monthly', $info['limits']['count']);
        $this->assertArrayHasKey('yearly', $info['limits']['count']);
        $this->assertArrayHasKey('lifetime', $info['limits']['count']);

        $this->assertEquals(5, $info['limits']['count']['daily']['limit']);
        $this->assertEquals(20, $info['limits']['count']['monthly']['limit']);
        $this->assertEquals(100, $info['limits']['count']['yearly']['limit']);
        $this->assertEquals(1000, $info['limits']['count']['lifetime']['limit']);

        $this->assertEquals(2, $info['limits']['count']['daily']['usage']['current_usage']);
        $this->assertEquals(4, $info['limits']['count']['monthly']['usage']['current_usage']);
        $this->assertEquals(0, $info['limits']['count']['yearly']['usage']['current_usage']);
        $this->assertEquals(0, $info['limits']['count']['lifetime']['usage']['current_usage']);
    }
}
