<?php

namespace Tests\Feature\Models;

use App\Models\ArchivePolicy;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArchivePolicyFeatureTest extends TestCase
{
    #[Test]
    public function can_create_archive_policy_with_factory(): void
    {

        $archivePolicyFactory = ArchivePolicy::factory();
        $archivePolicy = $archivePolicyFactory->create();

        $this->assertDatabaseHas(
            'archive_policies',
            [
                'id' => $archivePolicy->id,
                'table_name' => $archivePolicy->table_name,
            ],
        );
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

        $archivePolicyFactory = ArchivePolicy::factory();
        $archivePolicy = $archivePolicyFactory->create(
            [
                'enabled' => 1,
                'retention_months' => '36',
                'records_archived' => '500',
            ],
        );

        $this->assertIsBool($archivePolicy->enabled);
        $this->assertIsInt($archivePolicy->retention_months);
        $this->assertIsInt($archivePolicy->records_archived);
        $this->assertInstanceOf(Carbon::class, $archivePolicy->created_at);
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

        $archivePolicyFactory = ArchivePolicy::factory();
        $policy = $archivePolicyFactory->create();

        $newData = [
            'table_name' => 'updated_table',
            'retention_months' => 48,
            'enabled' => false,
        ];

        $policy->update($newData);

        $this->assertDatabaseHas(
            'archive_policies',
            array_merge(
                ['id' => $policy->id],
                $newData,
            ),
        );
    }

    #[Test]
    public function model_can_be_deleted(): void
    {

        $archivePolicyFactory = ArchivePolicy::factory();
        $policy = $archivePolicyFactory->create();
        $policyId = $policy->id;

        $policy->delete();

        $this->assertDatabaseMissing('archive_policies', ['id' => $policyId]);
    }

    #[Test]
    public function datetime_cast_works_with_last_archived_at(): void
    {

        $archivePolicyFactory = ArchivePolicy::factory();
        $policy = $archivePolicyFactory->create(['last_archived_at' => '2024-01-15 10:30:00']);

        $this->assertInstanceOf(Carbon::class, $policy->last_archived_at);
        $this->assertEquals(
            '2024-01-15 10:30:00',
            $policy->last_archived_at->format('Y-m-d H:i:s'),
        );
    }

    #[Test]
    public function boolean_cast_works_with_enabled(): void
    {

        $archivePolicyFactory = ArchivePolicy::factory();
        $truePolicy = $archivePolicyFactory->create(['enabled' => 1]);
        $falsePolicy = $archivePolicyFactory->create(['enabled' => 0]);

        $this->assertTrue($truePolicy->enabled);
        $this->assertFalse($falsePolicy->enabled);
        $this->assertIsBool($truePolicy->enabled);
        $this->assertIsBool($falsePolicy->enabled);
    }
}
