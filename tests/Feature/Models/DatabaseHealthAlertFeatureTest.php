<?php

namespace Tests\Feature\Models;

use App\Models\DatabaseHealthAlert;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseHealthAlertFeatureTest extends TestCase
{
    #[Test]
    public function can_create_database_health_alert(): void
    {

        $databaseHealthAlertFactory = DatabaseHealthAlert::factory();
        $alert = $databaseHealthAlertFactory->create(
            [
                'alert_type' => 'memory',
                'severity' => 'warning',
                'message' => 'High memory usage detected',
                'resolved' => false,
            ],
        );

        $this->assertDatabaseHas(
            'database_health_alerts',
            [
                'id' => $alert->id,
                'alert_type' => 'memory',
                'severity' => 'warning',
                'message' => 'High memory usage detected',
                'resolved' => false,
            ],
        );
    }

    #[Test]
    public function scope_unresolved_filters_correctly(): void
    {

        $databaseHealthAlertFactory = DatabaseHealthAlert::factory();
        $resolvedFactory = $databaseHealthAlertFactory->resolved();
        $resolvedFactory->create();
        $unresolvedFactory = $databaseHealthAlertFactory->unresolved();
        $unresolvedAlert = $unresolvedFactory->create();

        $alertQuery = DatabaseHealthAlert::unresolved();
        $results = $alertQuery->get();

        $this->assertCount(1, $results);
        $this->assertEquals($unresolvedAlert->id, $results->first()->id);
        $this->assertFalse($results->first()->resolved);
    }

    #[Test]
    public function scope_severity_filters_correctly(): void
    {

        $databaseHealthAlertFactory = DatabaseHealthAlert::factory();
        $infoFactory = $databaseHealthAlertFactory->info();
        $infoFactory->create();
        $criticalFactory = $databaseHealthAlertFactory->critical();
        $criticalAlert = $criticalFactory->create();

        $alertQuery = DatabaseHealthAlert::severity('critical');
        $results = $alertQuery->get();

        $this->assertCount(1, $results);
        $this->assertEquals($criticalAlert->id, $results->first()->id);
        $this->assertEquals('critical', $results->first()->severity);
    }

    #[Test]
    public function casts_work_correctly(): void
    {

        $databaseHealthAlertFactory = DatabaseHealthAlert::factory();
        $alert = $databaseHealthAlertFactory->create(
            [
                'metric_data' => [
                    'value' => 85.5,
                    'threshold' => 80,
                    'unit' => '%',
                ],
                'resolved' => '1',
                'resolved_at' => '2024-01-01 10:00:00',
            ],
        );

        $this->assertIsArray($alert->metric_data);
        $this->assertEquals([
            'value' => 85.5,
            'threshold' => 80,
            'unit' => '%',
        ], $alert->metric_data);
        $this->assertIsBool($alert->resolved);
        $this->assertTrue($alert->resolved);
        $this->assertInstanceOf(Carbon::class, $alert->resolved_at);
    }

    #[Test]
    public function factory_states_work_correctly(): void
    {

        $databaseHealthAlertFactory = DatabaseHealthAlert::factory();
        $criticalFactory = $databaseHealthAlertFactory->critical();
        $criticalAlert = $criticalFactory->create();
        $warningFactory = $databaseHealthAlertFactory->warning();
        $warningAlert = $warningFactory->create();
        $infoFactory = $databaseHealthAlertFactory->info();
        $infoAlert = $infoFactory->create();
        $memoryFactory = $databaseHealthAlertFactory->memory();
        $memoryAlert = $memoryFactory->create();
        $diskFactory = $databaseHealthAlertFactory->disk();
        $diskAlert = $diskFactory->create();

        $this->assertEquals('critical', $criticalAlert->severity);
        $this->assertFalse($criticalAlert->resolved);

        $this->assertEquals('warning', $warningAlert->severity);

        $this->assertEquals('info', $infoAlert->severity);

        $this->assertEquals('memory', $memoryAlert->alert_type);
        $this->assertStringContainsString('memory usage', $memoryAlert->message);

        $this->assertEquals('disk', $diskAlert->alert_type);
        $this->assertStringContainsString('disk usage', $diskAlert->message);
    }

    #[Test]
    public function can_combine_multiple_scopes(): void
    {

        $databaseHealthAlertFactory = DatabaseHealthAlert::factory();
        $criticalFactory = $databaseHealthAlertFactory->critical();
        $resolvedFactory = $criticalFactory->resolved();
        $resolvedFactory->create();
        $infoFactory = $databaseHealthAlertFactory->info();
        $infoFactory = $infoFactory->unresolved();
        $infoFactory->create();
        $unresolvedFactory = $criticalFactory->unresolved();
        $targetAlert = $unresolvedFactory->create();

        $alertQuery = DatabaseHealthAlert::unresolved();
        $alertQuery = $alertQuery->severity('critical');
        $results = $alertQuery->get();

        $this->assertCount(1, $results);
        $this->assertEquals($targetAlert->id, $results->first()->id);
        $this->assertEquals('critical', $results->first()->severity);
        $this->assertFalse($results->first()->resolved);
    }
}
