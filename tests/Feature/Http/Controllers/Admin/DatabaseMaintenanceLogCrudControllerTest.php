<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Infrastructure\Authorization\PermissionCatalog;
use App\Models\DatabaseMaintenanceLog;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class DatabaseMaintenanceLogCrudControllerTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $userFactory = User::factory();
        $this->admin = $userFactory->create();
        $this->admin->assignRole(
            Role::findByName(PermissionCatalog::ADMIN_ROLE, PermissionCatalog::BACKPACK),
        );
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function durations(): array
    {
        return [
            'seconds' => [
                '2026-11-01 10:00:30',
                '30 sec',
                '30 seconds',
            ],
            'minutes' => [
                '2026-11-01 10:05:07',
                '5m 7s',
                '5 minutes 7 seconds',
            ],
            'hours' => [
                '2026-11-01 12:05:07',
                '125m 7s',
                '2 hours 5 minutes',
            ],
        ];
    }

    #[Test]
    #[DataProvider('durations')]
    public function shows_the_duration_between_start_and_completion(
        string $completed,
        string $listed,
        string $shown,
    ): void {
        $log = DatabaseMaintenanceLog::create(
            [
                'task_type' => 'optimize',
                'table_name' => 'table_' . uniqid(),
                'status' => 'completed',
                'started_at' => '2026-11-01 10:00:00',
                'completed_at' => $completed,
            ],
        );

        $this->actingAs($this->admin, PermissionCatalog::BACKPACK);

        $this->assertStringContainsString(
            $listed,
            $this->listRow($log->table_name),
        );

        $response = $this->get('/admin/database-maintenance-log/' . $log->id . '/show');
        $response->assertOk();
        $response->assertSee($shown);
    }

    /**
     * The DataTables row (rendered cells joined) of the list view that contains the given text.
     */
    private function listRow(string $needle): string
    {
        $response = $this->postJson('/admin/database-maintenance-log/search', [
            'draw' => 1,
            'start' => 0,
            'length' => 100,
        ]);
        $response->assertOk();

        $collection = collect(
            $response->json('data'),
        );
        $mapped = $collection->map(
            fn (array $cells) => implode('', $cells),
        );
        $row = $mapped->first(
            fn (string $cells) => str_contains($cells, $needle),
        );
        $this->assertNotNull($row, "No list row contains {$needle}");

        return $row;
    }
}
