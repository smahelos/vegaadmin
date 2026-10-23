<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Infrastructure\Authorization\PermissionCatalog;
use App\Models\EntityLimitUsage;
use App\Models\PageCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Backpack's closure column escapes the closure's output unless the column sets escaped(false),
 * and shows its default ('-' unless set) for an empty value — 0 included.
 */
final class ClosureColumnTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::findByName(PermissionCatalog::ADMIN_ROLE, PermissionCatalog::BACKPACK));
    }

    #[Test]
    public function escapes_text_and_shows_zero_count(): void
    {
        $name = '<b>Category ' . uniqid() . '</b>';
        PageCategory::factory()->create(['name' => array_fill_keys([
            'cs',
            'de',
            'en',
            'sk',
        ], $name)]);

        $row = $this->listRow('/admin/page-category', e($name));

        $this->assertStringNotContainsString($name, $row);
        $this->assertMatchesRegularExpression('/<span>\s*0\s*<\/span>/', $row);
    }

    #[Test]
    public function renders_html_columns_with_escaped_values(): void
    {
        $user = User::factory()->create(['name' => '<i>User ' . uniqid() . '</i>']);
        EntityLimitUsage::create([
            'user_id' => $user->id,
            'entity_type' => 'client',
            'metric_type' => 'count',
            'period_type' => 'monthly',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'current_value' => 0,
            'last_reset_at' => now(),
        ]);

        $row = $this->listRow('/admin/entity-usage', e($user->name));

        $this->assertStringNotContainsString($user->name, $row);
        $this->assertStringContainsString('<span class="badge text-bg-secondary">Client</span>', $row);
        $this->assertMatchesRegularExpression('/<span>\s*0\s*<\/span>/', $row);
    }

    /**
     * The DataTables row (rendered cells joined) of the list view that contains the given text.
     */
    private function listRow(string $route, string $needle): string
    {
        $this->actingAs($this->admin, PermissionCatalog::BACKPACK);

        $response = $this->postJson($route . '/search', [
            'draw' => 1,
            'start' => 0,
            'length' => 100,
        ]);
        $response->assertOk();

        $row = collect($response->json('data'))->map(fn (array $cells) => implode('', $cells))
            ->first(fn (string $cells) => str_contains($cells, $needle));
        $this->assertNotNull($row, "No list row of {$route} contains {$needle}");

        return $row;
    }
}
