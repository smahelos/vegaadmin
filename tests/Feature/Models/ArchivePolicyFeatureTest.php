<?php

namespace Tests\Feature\Models;

use App\Models\ArchivePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArchivePolicyFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_create_archive_policy_with_factory(): void
    {
        $archivePolicy = ArchivePolicy::factory()->create();

        $this->assertDatabaseHas('archive_policies', [
            'id' => $archivePolicy->id,
            'table_name' => $archivePolicy->table_name,
        ]);
    }

    #[Test]
    public function fillable_attributes_can_be_mass_assigned(): void
    {
        $data = [
            'table_name' => 'test_table',
            'retention_months' => 24,
            'date_column' => 'created_at',
            'enabled' => true,
            'last_archived_at' => now(),
            'records_archived' => 100,
        ];

        $archivePolicy = ArchivePolicy::create($data);

        $this->assertDatabaseHas('archive_policies', $data);
        $this->assertEquals($data['table_name'], $archivePolicy->table_name);
        $this->assertEquals($data['retention_months'], $archivePolicy->retention_months);
    }

    #[Test]
    public function casts_work_correctly(): void
    {
        $archivePolicy = ArchivePolicy::factory()->create([
            'enabled' => 1,
            'retention_months' => '36',
            'records_archived' => '500',
        ]);

        $this->assertIsBool($archivePolicy->enabled);
        $this->assertIsInt($archivePolicy->retention_months);
        $this->assertIsInt($archivePolicy->records_archived);
        $this->assertInstanceOf(\Carbon\Carbon::class, $archivePolicy->created_at);
    }

    #[Test]
    public function get_enabled_badge_attribute_returns_correct_html(): void
    {
        $enabledPolicy = ArchivePolicy::factory()->enabled()->create();
        $disabledPolicy = ArchivePolicy::factory()->disabled()->create();

        $this->assertStringContainsString('badge-success', $enabledPolicy->enabled_badge);
        $this->assertStringContainsString('Enabled', $enabledPolicy->enabled_badge);
        
        $this->assertStringContainsString('badge-secondary', $disabledPolicy->enabled_badge);
        $this->assertStringContainsString('Disabled', $disabledPolicy->enabled_badge);
    }

    #[Test]
    public function get_retention_period_attribute_formats_correctly(): void
    {
        $policy = ArchivePolicy::factory()->create(['retention_months' => 24]);
        
        $expected = '24 months (2 years)';
        $this->assertEquals($expected, $policy->retention_period);
    }

    #[Test]
    public function get_retention_period_attribute_handles_fractional_years(): void
    {
        $policy = ArchivePolicy::factory()->create(['retention_months' => 18]);
        
        $expected = '18 months (1.5 years)';
        $this->assertEquals($expected, $policy->retention_period);
    }

    #[Test]
    public function get_last_archived_formatted_attribute_with_date(): void
    {
        $date = now()->subDays(5);
        $policy = ArchivePolicy::factory()->create(['last_archived_at' => $date]);
        
        $expected = $date->format('d.m.Y H:i');
        $this->assertEquals($expected, $policy->last_archived_formatted);
    }

    #[Test]
    public function get_last_archived_formatted_attribute_without_date(): void
    {
        $policy = ArchivePolicy::factory()->neverRun()->create();
        
        $this->assertEquals('Never', $policy->last_archived_formatted);
    }

    #[Test]
    public function get_records_to_archive_attribute_returns_zero_when_disabled(): void
    {
        $policy = ArchivePolicy::factory()->disabled()->create();
        
        $this->assertEquals(0, $policy->records_to_archive);
    }

    #[Test]
    public function get_records_to_archive_attribute_handles_nonexistent_table(): void
    {
        $policy = ArchivePolicy::factory()->enabled()->create([
            'table_name' => 'nonexistent_table',
        ]);
        
        $this->assertEquals(0, $policy->records_to_archive);
    }

    #[Test]
    public function scope_enabled_filters_correctly(): void
    {
        // Get initial count of enabled policies (from migrations)
        $initialEnabledCount = ArchivePolicy::enabled()->count();
        
        ArchivePolicy::factory()->enabled()->count(3)->create();
        ArchivePolicy::factory()->disabled()->count(2)->create();
        
        $enabledPolicies = ArchivePolicy::enabled()->get();
        
        // Should have initial + 3 new enabled policies
        $this->assertCount($initialEnabledCount + 3, $enabledPolicies);
        foreach ($enabledPolicies as $policy) {
            $this->assertTrue($policy->enabled);
        }
    }

    #[Test]
    public function factory_states_work_correctly(): void
    {
        $enabledPolicy = ArchivePolicy::factory()->enabled()->create();
        $disabledPolicy = ArchivePolicy::factory()->disabled()->create();
        $neverRunPolicy = ArchivePolicy::factory()->neverRun()->create();
        $recentlyRunPolicy = ArchivePolicy::factory()->recentlyRun()->create();

        $this->assertTrue($enabledPolicy->enabled);
        $this->assertFalse($disabledPolicy->enabled);
        $this->assertNull($neverRunPolicy->last_archived_at);
        $this->assertEquals(0, $neverRunPolicy->records_archived);
        $this->assertNotNull($recentlyRunPolicy->last_archived_at);
        $this->assertGreaterThan(0, $recentlyRunPolicy->records_archived);
    }

    #[Test]
    public function model_can_be_updated(): void
    {
        $policy = ArchivePolicy::factory()->create();
        
        $newData = [
            'table_name' => 'updated_table',
            'retention_months' => 48,
            'enabled' => false,
        ];
        
        $policy->update($newData);
        
        $this->assertDatabaseHas('archive_policies', array_merge(
            ['id' => $policy->id],
            $newData
        ));
    }

    #[Test]
    public function model_can_be_deleted(): void
    {
        $policy = ArchivePolicy::factory()->create();
        $policyId = $policy->id;
        
        $policy->delete();
        
        $this->assertDatabaseMissing('archive_policies', ['id' => $policyId]);
    }

    #[Test]
    public function datetime_cast_works_with_last_archived_at(): void
    {
        $policy = ArchivePolicy::factory()->create(['last_archived_at' => '2024-01-15 10:30:00']);
        
        $this->assertInstanceOf(\Carbon\Carbon::class, $policy->last_archived_at);
        $this->assertEquals('2024-01-15 10:30:00', $policy->last_archived_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function boolean_cast_works_with_enabled(): void
    {
        $truePolicy = ArchivePolicy::factory()->create(['enabled' => 1]);
        $falsePolicy = ArchivePolicy::factory()->create(['enabled' => 0]);
        
        $this->assertTrue($truePolicy->enabled);
        $this->assertFalse($falsePolicy->enabled);
        $this->assertIsBool($truePolicy->enabled);
        $this->assertIsBool($falsePolicy->enabled);
    }
}
